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

use app\common\attribute\Permission;
use app\common\model\IconLibrary as IconLibraryModel;
use app\common\service\IconLibraryService;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 图标管理控制器
 */
#[Permission('图标管理', icon: 'ti ti-icons', sort: 80)]
class Icon extends Auth
{
    /**
     * 图标库模型
     * @var IconLibraryModel
     */
    protected IconLibraryModel $model;

    /**
     * 图标库服务
     * @var IconLibraryService
     */
    protected IconLibraryService $iconLibraryService;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['status', 'autoload'];

    /**
     * 删除前的图标库快照
     * @var array
     */
    protected array $deleteLogSnapshot = [];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model              = new IconLibraryModel();
        $this->iconLibraryService = app(IconLibraryService::class);
    }

    /**
     * 图标库列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        $this->table
            ->search('lib_id,label,source_type,status,sync_status')
            ->columns([
                ['id', 'ID', '', [], ['width' => 40]],
                ['lib_id', '标识', '', [], ['minWidth' => 130]],
                ['label', '名称', '', [], ['minWidth' => 140]],
                ['source_type', '来源', IconLibraryModel::getSourceTypeList(), '', ['width' => 120]],
                ['icon_count', '图标数', '', [], ['width' => 90]],
                ['sync_status', '同步状态', 'yes_no'],
                ['autoload', '自动加载 CSS', 'switch', ['1' => '开', '0' => '关'], ['width' => 120]],
                ['status', '状态', 'switch', ['1' => '启用', '0' => '禁用']],
                ['last_sync_time', '最近同步', 'datetime', [], ['width' => 180]],
                ['last_error', '最近错误'],
                ['right_button', '操作', 'actions', [
                    'edit',
                    [
                        'title'   => '刷新',
                        'url'     => dp_url('refresh', ['id' => '__id__']),
                        'class'   => 'layui-btn layui-btn-xs layui-bg-blue',
                        'ajax'    => 'post',
                        'confirm' => '确定要刷新该图标库吗？',
                        'when'    => [
                            'state'  => 'disabled',
                            'field'  => 'status',
                            'op'     => '=',
                            'value'  => 0,
                            'remark' => '图标库已禁用，请先启用后再刷新',
                        ],
                    ],
                    'delete' => [
                        'when' => [
                            'callback' => static function (array $row) {
                                if ((int)($row['autoload'] ?? 0) === 1) {
                                    return [
                                        'state'  => 'disabled',
                                        'remark' => '当前图标库已开启自动加载，请先关闭自动加载后再删除',
                                    ];
                                }

                                return false;
                            },
                        ],
                    ],
                ], ['width' => 210]],
            ])
            ->toolbar(['add', 'enable', 'disable', 'delete'])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 图标库数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        return $this->model
            ->where($this->getSearchWhere())
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate(dp_get_list_rows());
    }

    /**
     * 新增图标库
     * @return string|Json
     * @throws Throwable
     */
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');
            $this->autoValidate('Icon.create', $data);
            try {
                $library = $this->iconLibraryService->createOnlineLibrary($data);
                dp_log_user_action('新增图标库', [
                    'library' => $this->buildLibraryLogData($library),
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '新增图标库', [
                    'submitted_data' => $this->buildRequestLogData($data),
                ]);
                $this->error($e->getMessage());
            }
            $this->success('新增成功', '', 'reload-table');
        }

        $this->buildForm();
        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 编辑图标库
     * @param int $id
     * @return string|Json
     * @throws Throwable
     */
    public function edit(int $id): string|Json
    {
        $library = $this->getLibraryOrFail($id);

        if ($this->request->isPost()) {
            $data       = $this->request->post('', null, 'trim');
            $data['id'] = $id;
            $this->autoValidate('Icon.edit', $data);
            $before = $this->buildLibraryLogData($library);
            try {
                $updated = $this->iconLibraryService->updateOnlineLibrary($library, $data);
                dp_log_user_action('编辑图标库', [
                    'id'     => $id,
                    'before' => $before,
                    'after'  => $this->buildLibraryLogData($updated),
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '编辑图标库', [
                    'id'             => $id,
                    'before'         => $before,
                    'submitted_data' => $this->buildRequestLogData($data),
                ]);
                $this->error($e->getMessage());
            }
            $this->success('编辑成功', '', 'reload-table');
        }

        $this->buildForm();
        $this->form->data($library->toArray());
        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 刷新图标库
     * @param int $id
     * @return Json
     * @throws Throwable
     */
    public function refresh(int $id): Json
    {
        $this->validateCrudToken('refresh');
        $library = $this->getLibraryOrFail($id);
        $before  = $this->buildLibraryLogData($library);
        try {
            $updated = $this->iconLibraryService->refreshOnlineLibrary($library);
            dp_log_user_action('刷新图标库', [
                'id'     => $id,
                'before' => $before,
                'after'  => $this->buildLibraryLogData($updated),
            ]);
        } catch (Exception $e) {
            dp_log_exception($e, '刷新图标库', [
                'id'     => $id,
                'before' => $before,
            ]);
            $this->error($e->getMessage());
        }
        $this->success('同步成功');
    }

    /**
     * 启用后清理缓存
     * @param array $ids
     * @param int|false $count
     * @return void
     * @throws Throwable
     */
    protected function afterEnable(array $ids, int|false $count): void
    {
        $this->iconLibraryService->clearCache();

        if (false === $count) {
            dp_log_user_action('批量启用图标库失败', [
                'target_ids'      => $ids,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败',
            ], 'error');
            return;
        }

        if ($count > 0) {
            dp_log_user_action('批量启用图标库', [
                'target_ids'      => $ids,
                'libraries'       => $this->buildLibrariesLogData($ids),
                'affected_count'  => $count,
                'total_requested' => count($ids),
            ]);
        }
    }

    /**
     * 禁用后清理缓存
     * @param array $ids
     * @param int|false $count
     * @return void
     * @throws Throwable
     */
    protected function afterDisable(array $ids, int|false $count): void
    {
        $this->iconLibraryService->clearCache();

        if (false === $count) {
            dp_log_user_action('批量禁用图标库失败', [
                'target_ids'      => $ids,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败',
            ], 'error');
            return;
        }

        if ($count > 0) {
            dp_log_user_action('批量禁用图标库', [
                'target_ids'      => $ids,
                'libraries'       => $this->buildLibrariesLogData($ids),
                'affected_count'  => $count,
                'total_requested' => count($ids),
            ], 'warning');
        }
    }

    /**
     * 删除前记录快照
     * @param array $ids
     * @return bool
     */
    protected function beforeDelete(array $ids): bool
    {
        $this->deleteLogSnapshot = $this->buildLibrariesLogData($ids);
        return true;
    }

    /**
     * 删除后清理缓存
     * @param array $ids
     * @param mixed $count
     * @return void
     * @throws Throwable
     */
    protected function afterDelete(array $ids, mixed $count): void
    {
        $this->iconLibraryService->clearCache();

        if (false === $count) {
            dp_log_user_action('删除图标库失败', [
                'target_ids'      => $ids,
                'libraries'       => $this->deleteLogSnapshot,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败',
            ], 'error');
            return;
        }

        dp_log_user_action('删除图标库', [
            'target_ids'      => $ids,
            'libraries'       => $this->deleteLogSnapshot,
            'affected_count'  => is_numeric($count) ? (int)$count : count($ids),
            'total_requested' => count($ids),
        ], 'warning');
    }

    /**
     * 快速编辑后清理缓存
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @param mixed $oldValue
     * @param mixed $result
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    protected function afterQuickEdit(
        int    $id,
        string $field,
        mixed  $value,
        mixed  $oldValue,
        mixed  $result
    ): void
    {
        if (in_array($field, ['status', 'autoload'], true)) {
            $this->iconLibraryService->clearCache();
        }

        if (false !== $result) {
            $library = $this->model->find($id);
            dp_log_user_action('快速编辑图标库', [
                'id'          => $id,
                'library'     => $library ? $this->buildLibraryLogData($library) : ['id' => $id],
                'field'       => $field,
                'field_label' => $this->getFieldLabel($field),
                'value'       => $value,
                'old_value'   => $oldValue,
                'description' => sprintf(
                    '修改图标库「%s」的%s',
                    $library ? (string)$library['label'] : 'ID:' . $id,
                    $this->getFieldLabel($field)
                ),
            ]);
        }
    }

    /**
     * 构建表单
     * @return void
     * @throws Exception
     */
    protected function buildForm(): void
    {
        $this->form->items([
            [
                'type'     => 'text',
                'name'     => 'lib_id',
                'label'    => '图标库标识',
                'tips'     => '唯一标识，例如 iconfont-default',
                'required' => true,
            ],
            [
                'type'     => 'text',
                'name'     => 'label',
                'label'    => '图标库名称',
                'tips'     => '用于图标选择器中的 Tab 名称',
                'required' => true,
            ],
            [
                'type'     => 'select',
                'name'     => 'source_type',
                'label'    => '来源类型',
                'tips'     => '当前仅支持阿里 Iconfont 在线图标库',
                'options'  => IconLibraryModel::getSourceTypeList(),
                'value'    => IconLibraryModel::SOURCE_TYPE_ICONFONT,
                'required' => true,
            ],
            [
                'type'     => 'text',
                'name'     => 'source_url',
                'label'    => 'CSS 链接',
                'tips'     => '请输入阿里 Iconfont 生成的 at.alicdn.com CSS 链接，例如 //at.alicdn.com/t/c/font_xxx.css',
                'required' => true,
            ],
            [
                'type'     => 'text',
                'name'     => 'prefix',
                'label'    => '图标类前缀',
                'tips'     => '用于过滤图标类，阿里 Iconfont 常见值为 icon-',
                'value'    => 'icon-',
                'required' => true,
            ],
            [
                'type'  => 'text',
                'name'  => 'base_class',
                'label' => '基础类名',
                'tips'  => '渲染时会拼接到最终 class 前，阿里 Iconfont 常见值为 iconfont',
                'value' => 'iconfont',
            ],
            [
                'type'  => 'number',
                'name'  => 'sort',
                'label' => '排序',
                'tips'  => '数值越小越靠前',
                'value' => 100,
            ],
            [
                'type'  => 'switch',
                'name'  => 'autoload',
                'label' => '自动加载 CSS',
                'tips'  => '开启后图标选择器会自动注入该图标库的 CSS 链接',
                'value' => 1,
            ],
            [
                'type'  => 'switch',
                'name'  => 'status',
                'label' => '状态',
                'tips'  => '禁用后图标选择器不再显示该图标库',
                'value' => 1,
            ],
        ]);
    }

    /**
     * 获取图标库
     * @param int $id
     * @return IconLibraryModel
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function getLibraryOrFail(int $id): IconLibraryModel
    {
        $library = $this->model->find($id);
        if (!$library) {
            $this->error('图标库不存在');
        }

        return $library;
    }

    /**
     * 构建单个图标库日志数据
     * @param IconLibraryModel|array $library
     * @return array
     */
    protected function buildLibraryLogData(IconLibraryModel|array $library): array
    {
        $data = $library instanceof IconLibraryModel ? $library->toArray() : $library;

        return [
            'id'             => (int)($data['id'] ?? 0),
            'lib_id'         => (string)($data['lib_id'] ?? ''),
            'label'          => (string)($data['label'] ?? ''),
            'source_type'    => (string)($data['source_type'] ?? ''),
            'source_url'     => (string)($data['source_url'] ?? ''),
            'prefix'         => (string)($data['prefix'] ?? ''),
            'base_class'     => (string)($data['base_class'] ?? ''),
            'autoload'       => (int)($data['autoload'] ?? 0),
            'status'         => (int)($data['status'] ?? 0),
            'sort'           => (int)($data['sort'] ?? 0),
            'icon_count'     => (int)($data['icon_count'] ?? 0),
            'sync_status'    => (int)($data['sync_status'] ?? 0),
            'last_sync_time' => (int)($data['last_sync_time'] ?? 0),
        ];
    }

    /**
     * 构建多个图标库日志数据
     * @param array $ids
     * @return array
     */
    protected function buildLibrariesLogData(array $ids): array
    {
        $rows = $this->model
            ->whereIn('id', $ids)
            ->select()
            ->toArray();

        return array_map(fn(array $item) => $this->buildLibraryLogData($item), $rows);
    }

    /**
     * 构建请求日志数据
     * @param array $data
     * @return array
     */
    protected function buildRequestLogData(array $data): array
    {
        return [
            'id'          => isset($data['id']) ? (int)$data['id'] : 0,
            'lib_id'      => (string)($data['lib_id'] ?? ''),
            'label'       => (string)($data['label'] ?? ''),
            'source_type' => (string)($data['source_type'] ?? ''),
            'source_url'  => (string)($data['source_url'] ?? ''),
            'prefix'      => (string)($data['prefix'] ?? ''),
            'base_class'  => (string)($data['base_class'] ?? ''),
            'autoload'    => (int)($data['autoload'] ?? 0),
            'status'      => (int)($data['status'] ?? 0),
            'sort'        => (int)($data['sort'] ?? 0),
        ];
    }

    /**
     * 获取字段显示名
     * @param string $field
     * @return string
     */
    protected function getFieldLabel(string $field): string
    {
        return match ($field) {
            'status' => '状态',
            'autoload' => '自动加载 CSS',
            default => $field,
        };
    }
}
