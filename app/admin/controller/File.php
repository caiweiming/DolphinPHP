<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\File as FileModel;
use app\common\attribute\Permission;
use app\common\helper\Logger;
use app\common\model\User as UserModel;
use Exception;
use think\Collection;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 附件管理控制器
 */
#[Permission('附件管理', icon: 'ti ti-files', sort: 90)]
class File extends Auth
{
    /**
     * 附件模型
     * @var FileModel
     */
    protected FileModel $model;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['status'];

    /**
     * 删除前的附件快照
     * @var array
     */
    protected array $deleteSnapshots = [];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model = new FileModel();
    }

    /**
     * 附件列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        $drivers = dp_arr2kv($this->model->column('driver'));

        $this->table
            ->search([
                ['name' => 'name', 'placeholder' => '文件名', 'op' => 'like'],
                ['name' => 'driver', 'placeholder' => '驱动', 'type' => 'select', 'options' => $drivers],
                'status',
                'ext'
            ])
            ->columns([
                ['id', 'ID', '', [], ['width' => 70]],
                ['url', '预览', 'preview', [], ['width' => 48]],
                ['name', '文件名'],
                ['ext', '后缀', 'callback', static fn($value): string => strtoupper((string)$value), ['width' => 90]],
                ['mime', 'MIME', '', [], ['minWidth' => 200]],
                ['size', '大小', 'callback', static fn($value): string => dp_format_size((int)$value), ['width' => 110]],
                ['driver', '驱动', '', [], ['width' => 110]],
                ['uploader_name', '上传者', '', [], ['minWidth' => 120]],
                ['status', '状态', 'switch', ['1' => '启用', '0' => '禁用'], ['width' => 90]],
                ['create_time', '上传时间'],
                ['right_button', '操作', 'actions', [
                    [
                        'title'  => '查看',
                        'url'    => '__preview_url__',
                        'target' => '_blank',
                        'class'  => 'layui-btn layui-btn-xs layui-btn-default',
                    ],
                    [
                        'title'  => '下载',
                        'url'    => '__download_url__',
                        'target' => '_blank',
                        'class'  => 'layui-btn layui-btn-xs layui-bg-blue',
                    ],
                    'delete',
                ], ['width' => 180]],
            ])
            ->toolbar([
                [
                    'title' => '上传附件',
                    'name'  => 'upload',
                    'event' => 'upload',
                    'icon'  => 'ti ti-upload',
                    'url'   => dp_url('create'),
                    'pop'   => [
                        'title' => '上传附件',
                        'area'  => ['720px', '380px'],
                    ],
                ],
                'enable',
                'disable',
                'delete',
            ])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 上传附件
     * @return string|Json
     * @throws Exception
     */
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $attachmentIds = array_values(array_unique(array_filter(
                array_map('intval', (array)$this->request->post('attachments/a', []))
            )));

            if ($attachmentIds === []) {
                $this->error('请先上传至少一个附件');
            }

            $this->success('上传完成', '', 'reload-table');
        }

        $this->form->items([
            [
                'type'    => 'file',
                'name'    => 'attachments',
                'label'   => '上传附件',
                'browser' => false,
                'tips'    => $this->buildAttachmentUploadTip(),
                'options' => [
                    'multiple'     => 20,
                    'allowedTypes' => ['*'],
                    'maxFileSize'  => 0,
                ],
            ],
        ]);

        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 列表数据
     * @return Paginator
     */
    protected function data(): Paginator
    {
        $query = $this->resolveCrudDb();
        $query->where($this->getSearchWhere());

        $type = (string)$this->getSearchValue('type', '');
        $exts = (array)config('upload.allowed_ext.' . $type, []);
        if ($type !== '' && $exts !== []) {
            $query->whereIn('ext', $exts);
        }

        $users    = UserModel::field('id,username,nickname')->select();
        $uploader = $this->buildUploaderMap($users);

        return $query
            ->order('id', 'desc')
            ->paginate(dp_get_list_rows())
            ->each(function (FileModel $item) use ($uploader) {
                $url         = (string)$item->getAttr('url');
                $downloadUrl = $this->buildDownloadUrl($item);
                $currentUid  = (int)$item->getAttr('uid');

                $item->setAttr('download_url', $downloadUrl !== '' ? $downloadUrl : $url);
                $item->setAttr('uploader_name', $uploader[$currentUid] ?? ('用户#' . $currentUid));

                return $item;
            });
    }

    /**
     * 通过 CRUD 查询对象应用数据权限
     * @return mixed
     */
    protected function resolveCrudDb(): mixed
    {
        $query = FileModel::where([]);

        if (!dp_is_super_admin($this->getCurrentUserId())) {
            $query->where('uid', $this->getCurrentUserId());
        }

        return $query;
    }

    /**
     * 删除前校验并保留快照
     * @param array $ids
     * @return bool
     */
    protected function beforeDelete(array $ids): bool
    {
        $ids     = array_values(array_unique(array_map('intval', $ids)));
        $records = $this->resolveCrudDb()->whereIn('id', $ids)->select();

        if ($records->isEmpty()) {
            $this->error('未找到可删除的附件');
        }

        if ($records->count() !== count($ids)) {
            $this->error('存在无权删除或不存在的附件');
        }

        $this->deleteSnapshots = $records->toArray();
        return true;
    }

    /**
     * 删除后清理缓存、文件和日志
     * @return void
     * @throws Throwable
     */
    protected function afterDelete(): void
    {
        foreach ($this->deleteSnapshots as $snapshot) {
            $fileDeleted  = $this->cleanupLocalFile((string)($snapshot['url'] ?? ''));
            $thumbDeleted = $this->cleanupLocalFile((string)($snapshot['thumb'] ?? ''));

            dp_log('删除附件')
                ->type(Logger::TYPE_FILE_OPERATION)
                ->context([
                    'file_id'       => (int)($snapshot['id'] ?? 0),
                    'file_name'     => (string)($snapshot['name'] ?? ''),
                    'file_path'     => (string)($snapshot['url'] ?? ''),
                    'driver'        => (string)($snapshot['driver'] ?? ''),
                    'deleted_file'  => $fileDeleted,
                    'deleted_thumb' => $thumbDeleted,
                    'operator_id'   => $this->getCurrentUserId(),
                    'deleted_at'    => date('Y-m-d H:i:s'),
                ])
                ->info();
        }

        $this->deleteSnapshots = [];
    }

    /**
     * 清理附件缓存
     * @param int|string $id
     * @return void
     */
    protected function clearCache(int|string $id): void
    {
        cache('dp_file:' . $id, null);
    }

    /**
     * 构建附件上传提示文案
     * @return string
     */
    private function buildAttachmentUploadTip(): string
    {
        $limits   = config('upload.size_limit', []);
        $template = (string)dp_setting(
            'upload.attachment_tip',
            '图片 {image_limit}，视频 {video_limit}，音频 {audio_limit}，文件 {file_limit}。文件上传成功后会自动写入附件表。'
        );

        $replacements = [
            '{image_limit}' => dp_format_size((int)($limits['image'] ?? 0)),
            '{video_limit}' => dp_format_size((int)($limits['video'] ?? 0)),
            '{audio_limit}' => dp_format_size((int)($limits['audio'] ?? 0)),
            '{file_limit}'  => dp_format_size((int)($limits['file'] ?? $limits['default'] ?? 0)),
        ];

        return strtr($template, $replacements);
    }

    /**
     * 构建上传者映射
     * @param Collection $users
     * @return array
     */
    private function buildUploaderMap(Collection $users): array
    {
        $map = [];
        foreach ($users as $user) {
            $map[(int)$user->getAttr('id')] = (string)($user->getAttr('nickname') ?: $user->getAttr('username'));
        }

        return $map;
    }

    /**
     * 构建下载地址
     * @param FileModel $file
     * @return string
     */
    private function buildDownloadUrl(FileModel $file): string
    {
        if ((string)$file->getAttr('driver') === 'local') {
            return (string)dp_url('admin/api/download', ['hash' => (string)$file->getAttr('sha1')]);
        }

        return (string)$file->getAttr('url');
    }

    /**
     * 删除本地文件
     * @param string $url
     * @return bool
     */
    private function cleanupLocalFile(string $url): bool
    {
        $path = $this->resolveLocalUploadPath($url);
        if ($path === '') {
            return false;
        }

        if (FileModel::where('url', $url)->count() > 0 || FileModel::where('thumb', $url)->count() > 0) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * 解析本地上传文件物理路径
     * @param string $url
     * @return string
     */
    private function resolveLocalUploadPath(string $url): string
    {
        $url = trim($url);
        if (
            $url === ''
            || preg_match('/^(https?:)?\/\//i', $url)
            || !str_starts_with($url, '/uploads/')
        ) {
            return '';
        }

        $fullPath   = public_path() . ltrim($url, '/');
        $realPath   = realpath($fullPath);
        $uploadsDir = realpath(public_path() . 'uploads');

        if ($realPath === false || $uploadsDir === false) {
            return '';
        }

        if ($realPath !== $uploadsDir && !str_starts_with($realPath, $uploadsDir . DIRECTORY_SEPARATOR)) {
            return '';
        }

        return $realPath;
    }
}
