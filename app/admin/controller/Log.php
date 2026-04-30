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
use app\common\model\Log as LogModel;
use app\common\model\User as UserModel;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 系统日志控制器
 */
#[Permission('系统日志', icon: 'ti ti-file-text', sort: 120)]
class Log extends Auth
{
    /**
     * 日志模型
     * @var LogModel
     */
    protected LogModel $model;

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model = new LogModel();
    }

    /**
     * 日志列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        $this->table
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '标题 / URL / IP',
                    'fields'      => ['title', 'url', 'ip'],
                ],
                [
                    'name'        => 'uid',
                    'type'        => 'select',
                    'placeholder' => '操作用户',
                    'options'     => $this->buildUserOptions(),
                ],
                [
                    'name'        => 'status',
                    'type'        => 'select',
                    'placeholder' => '日志级别',
                    'options'     => $this->getStatusOptions(),
                ],
                [
                    'name'        => 'create_time',
                    'type'        => 'date',
                    'placeholder' => '记录时间',
                    'range'       => true,
                ],
            ])
            ->checkbox(false)
            ->columns([
                ['id', 'ID', '', [], ['width' => 40]],
                ['title', '标题', '', [], ['minWidth' => 180]],
                ['type_text', '类型', '', [], ['width' => 120]],
                ['operator_name', '操作用户', '', [], ['width' => 80]],
                ['url', '请求地址', '', [], ['minWidth' => 220]],
                ['ip', 'IP', '', [], ['width' => 130]],
                ['status', '级别', 'status', $this->getStatusBadgeOptions(), ['width' => 100]],
                ['create_time', '记录时间', '', [], ['width' => 170]],
                ['right_button', '操作', 'actions', [
                    [
                        'title' => '详情',
                        'url'   => dp_url('detail', ['id' => '__id__']),
                        'pop'   => [
                            'title' => '日志详情',
                            'area'  => ['900px', '720px'],
                        ],
                    ],
                ], ['width' => 100]],
            ])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 日志数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        $userMap = $this->buildUserMap();

        return $this->model
            ->where($this->getSearchWhere())
            ->order('id', 'desc')
            ->paginate(dp_get_list_rows())
            ->each(function (LogModel $item) use ($userMap) {
                $uid = (int)$item->getAttr('uid');

                $item->setAttr('operator_name', $this->resolveOperatorName($uid, $userMap));
                $item->setAttr('type_text', $this->normalizeTypeText($item));
                $item->setAttr('content_preview', $this->buildContentPreview((string)$item->getAttr('content')));

                return $item;
            });
    }

    /**
     * 日志详情
     * @param int $id
     * @return string
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function detail(int $id): string
    {
        $log = $this->model->find($id);
        if (!$log) {
            $this->error('日志不存在');
        }

        $userMap = $this->buildUserMap();
        $content = (string)$log->getAttr('content');
        $detail  = $this->buildDetailViewData($log, $userMap, $content);

        $this->assign($detail);
        return $this->fetch('detail');
    }

    /**
     * 获取状态选项
     * @return array
     */
    private function getStatusOptions(): array
    {
        return [
            0 => 'Debug',
            1 => 'Info',
            2 => 'Warning',
            3 => 'Error',
        ];
    }

    /**
     * 获取状态标签选项
     * @return array
     */
    private function getStatusBadgeOptions(): array
    {
        return [
            0 => 'Debug:default',
            1 => 'Info:blue',
            2 => 'Warning:orange',
            3 => 'Error:danger',
        ];
    }

    /**
     * 构建用户选项
     * @return array
     */
    private function buildUserOptions(): array
    {
        return [0 => '系统/游客'] + UserModel::order('id', 'asc')->column('nickname', 'id');
    }

    /**
     * 构建用户映射
     * @return array
     */
    private function buildUserMap(): array
    {
        $map = [0 => '系统/游客'];
        foreach (UserModel::field('id,username,nickname')->select() as $user) {
            $nickname                       = trim((string)$user->getAttr('nickname'));
            $username                       = trim((string)$user->getAttr('username'));
            $map[(int)$user->getAttr('id')] = $nickname !== '' ? $nickname : $username;
        }

        return $map;
    }

    /**
     * 解析操作人名称
     * @param int $uid
     * @param array $userMap
     * @return string
     */
    private function resolveOperatorName(int $uid, array $userMap): string
    {
        return $userMap[$uid] ?? ('用户#' . $uid);
    }

    /**
     * 规范化日志类型文案
     * @param LogModel $log
     * @return string
     */
    private function normalizeTypeText(LogModel $log): string
    {
        $type = trim((string)($log->getData('type') ?? ''));
        return $type !== '' ? $type : '-';
    }

    /**
     * 构建内容预览
     * @param string $content
     * @return string
     */
    private function buildContentPreview(string $content): string
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $fragments = [];
            foreach ($decoded as $key => $value) {
                $fragments[] = $key . '=' . $this->stringifyLogValue($value);
                if (count($fragments) >= 3) {
                    break;
                }
            }

            $summary = implode(' | ', $fragments);
            return mb_strlen($summary) > 120 ? mb_substr($summary, 0, 120) . '...' : $summary;
        }

        $text = trim(strip_tags($content));
        return mb_strlen($text) > 120 ? mb_substr($text, 0, 120) . '...' : $text;
    }

    /**
     * 构建详情概览 HTML
     * @param LogModel $log
     * @param array $userMap
     * @param string $content
     * @return array
     */
    private function buildDetailViewData(LogModel $log, array $userMap, string $content): array
    {
        $uid            = (int)$log->getAttr('uid');
        $operatorName   = $this->resolveOperatorName($uid, $userMap);
        $createdAt      = $log->getAttr('create_time');
        $typeText       = $this->normalizeTypeText($log);
        $statusText     = $this->getStatusOptions()[(int)$log->getAttr('status')] ?? 'Unknown';
        $statusClass    = match ((int)$log->getAttr('status')) {
            1 => 'info',
            2 => 'warning',
            3 => 'error',
            default => 'debug',
        };
        $decodedContent = json_decode($content, true);
        $title          = trim((string)$log->getAttr('title'));
        $subtitleParts  = array_filter([
            $typeText !== '-' ? '类型：' . $typeText : '',
            '操作人：' . $operatorName,
            '时间：' . $createdAt,
        ]);
        $detailRows     = [
            ['label' => '日志ID', 'value' => (string)$log->getAttr('id')],
            ['label' => '标题', 'value' => (string)$log->getAttr('title')],
            ['label' => '类型', 'value' => $typeText],
            ['label' => '级别', 'value' => $statusText],
            ['label' => '操作用户', 'value' => $operatorName],
            ['label' => '用户ID', 'value' => (string)$uid],
            ['label' => '请求地址', 'value' => (string)$log->getAttr('url')],
            ['label' => 'IP 地址', 'value' => (string)$log->getAttr('ip')],
            ['label' => '记录时间', 'value' => $createdAt],
        ];
        $structuredRows = [];
        if (is_array($decodedContent)) {
            foreach ($decodedContent as $key => $value) {
                $structuredRows[] = [
                    'label' => (string)$key,
                    'value' => is_scalar($value) || is_null($value)
                        ? (string)var_export($value, true)
                        : (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                ];
            }
        }

        return [
            'log'            => $log,
            'logTitle'       => $title !== '' ? $title : '系统日志 #' . $log->getAttr('id'),
            'logSubtitle'    => implode(' · ', $subtitleParts),
            'contentTypeText' => $structuredRows !== [] ? 'JSON 内容' : '文本内容',
            'statusText'     => $statusText,
            'statusClass'    => $statusClass,
            'prettyContent'  => $this->formatContentForDisplay($content),
            'structuredRows' => $structuredRows,
            'isJsonContent'  => $structuredRows !== [],
            'detailRows'     => $detailRows,
        ];
    }

    /**
     * 格式化日志内容
     * @param string $content
     * @return string
     */
    private function formatContentForDisplay(string $content): string
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($pretty) ? $pretty : $content;
        }

        return $content;
    }

    /**
     * 日志值转字符串
     * @param mixed $value
     * @return string
     */
    private function stringifyLogValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value), $value === null => (string)var_export($value, true),
            default => '[complex]',
        };
    }
}
