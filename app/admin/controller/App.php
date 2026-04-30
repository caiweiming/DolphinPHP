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
use app\common\application\AppPackageSourceManager;
use app\common\application\AppLifecycleManager;
use app\common\application\AppPackageBuilder;
use app\common\application\AppPackageImporter;
use app\common\interface\PermissionService as PermissionServiceInterface;
use app\common\model\App as AppModel;
use app\common\model\AppLog;
use app\common\service\AppService;
use Exception;
use app\common\service\PermissionSyncService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\file\UploadedFile;
use think\Paginator;
use think\response\File;
use think\response\Json;
use Throwable;

/**
 * 应用管理控制器
 */
#[Permission('应用管理', icon: 'ti ti-components', sort: 70)]
class App extends Auth
{
    /**
     * 应用模型
     * @var AppModel
     */
    protected AppModel $model;

    /**
     * 应用服务
     * @var AppService
     */
    protected AppService $appService;

    /**
     * 应用分发包构建器
     * @var AppPackageBuilder
     */
    protected AppPackageBuilder $packageBuilder;

    /**
     * 应用分发包导入器
     * @var AppPackageImporter
     */
    protected AppPackageImporter $packageImporter;

    /**
     * 应用生命周期管理器
     * @var AppLifecycleManager
     */
    protected AppLifecycleManager $lifecycleManager;

    /**
     * 应用包源管理器
     * @var AppPackageSourceManager
     */
    protected AppPackageSourceManager $sourceManager;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['sort', 'show_in_config'];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model            = new AppModel();
        $this->appService       = app(AppService::class);
        $this->packageBuilder   = app(AppPackageBuilder::class);
        $this->packageImporter  = app(AppPackageImporter::class);
        $this->lifecycleManager = app(AppLifecycleManager::class);
        $this->sourceManager    = app(AppPackageSourceManager::class);
        $this->appService->syncInstalledApps();
    }

    /**
     * 应用注册表列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        $this->table
            ->alert([
                '上传应用包只会把文件导入到标准应用目录，并登记为“已导入”，不会自动安装或启用。',
                '应用安装会串联权限同步、菜单同步和静态资源发布。',
                '只有具备合法 `app.json` 的应用才支持标准包导出。',
            ], '应用分发说明', 'info:icon,close')
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '应用标识 / 名称 / 描述',
                    'op'          => 'like',
                    'fields'      => ['name', 'title', 'description'],
                ],
                [
                    'name'        => 'status',
                    'type'        => 'select',
                    'placeholder' => '状态',
                    'options'     => [1 => '启用', 0 => '禁用'],
                ],
                [
                    'name'        => 'is_system',
                    'type'        => 'select',
                    'placeholder' => '内置应用',
                    'options'     => [1 => '系统内置', 0 => '第三方'],
                ],
            ])
            ->columns([
                ['id', 'ID', '', [], ['width' => 70]],
                ['icon', '图标', 'icon', [], ['width' => 80, 'style' => 'font-size:18px;']],
                ['name', '应用标识', '', [], ['width' => 120]],
                ['title', '应用名称', '', [], ['minWidth' => 140]],
                ['description', '说明', '', [], ['minWidth' => 180]],
                ['version_display', '版本', '', [], ['width' => 130]],
                ['lifecycle_state_text', '状态', 'status', [
                    '待安装' => '待安装:orange',
                    '已启用' => '已启用:green',
                    '已禁用' => '已禁用:default',
                    '可升级' => '可升级:blue',
                    '异常'   => '异常:danger',
                ], ['width' => 110]],
                ['author', '作者', '', [], ['minWidth' => 140]],
                ['show_in_config', '配置中心', 'switch', ['1' => '是', '0' => '否'], ['width' => 120]],
                ['is_system', '内置', 'yes_no', [], ['width' => 90]],
                ['last_error', '错误信息', '', [], ['minWidth' => 180]],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'actions', $this->buildActionButtons(), ['minWidth' => 250]],
            ])
            ->toolbar([
                [
                    'title' => '上传应用',
                    'name'  => 'upload_app',
                    'event' => 'upload',
                    'icon'  => 'ti ti-upload',
                    'url'   => dp_url('import'),
                    'pop'   => [
                        'title' => '上传应用',
                        'area'  => ['760px', '450px'],
                    ],
                ],
                [
                    'title' => '官方商店',
                    'name'  => 'official_store',
                    'event' => 'download',
                    'icon'  => 'ti ti-shopping-bag-search',
                    'url'   => dp_url('admin/store_client/index'),
                    'pop'   => [
                        'title' => '官方商店',
                        'area'  => ['820px', '600px'],
                    ],
                ],
                [
                    'title' => '导入日志',
                    'name'  => 'import_logs',
                    'event' => 'download',
                    'icon'  => 'ti ti-file-search',
                    'url'   => dp_url('importLogs'),
                    'pop'   => [
                        'title' => '应用导入日志',
                        'area'  => ['980px', '760px'],
                    ],
                ],
            ])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 列表数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        $apps = [];
        foreach ($this->appService->getApps() as $app) {
            $name = (string)($app['name'] ?? '');
            if ($name !== '') {
                $apps[$name] = $app;
            }
        }

        $names = array_keys($apps);
        $query = $this->model->where($this->getSearchWhere());

        if ($names === []) {
            $query->where('id', 0);
        } else {
            $query->whereIn('name', $names);
        }

        return $query
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->paginate(dp_get_list_rows())
            ->each(function (AppModel $item) use ($apps) {
                $name = (string)$item->getAttr('name');
                $meta = $apps[$name] ?? [];

                $item->setAttr('author', (string)($meta['author'] ?? ''));
                $item->setAttr('show_in_config', !empty($meta['can_show_in_config']) ? (int)($meta['show_in_config'] ?? 0) : 0);
                $item->setAttr('can_show_in_config', (int)($meta['can_show_in_config'] ?? 0));
                $item->setAttr('lifecycle_status', (string)($meta['lifecycle_status'] ?? ''));
                $item->setAttr('lifecycle_status_text', (string)($meta['lifecycle_status_text'] ?? '未知'));
                $item->setAttr('lifecycle_state_text', (string)($meta['lifecycle_state_text'] ?? '未知'));
                $item->setAttr('lifecycle_state_key', (string)($meta['lifecycle_state_key'] ?? ''));
                $item->setAttr('distribution_ready', (int)($meta['distribution_ready'] ?? 0));
                $item->setAttr('distribution_error', (string)($meta['distribution_error'] ?? ''));
                $item->setAttr('distribution_protocol_version', (string)($meta['distribution_protocol_version'] ?? ''));
                $item->setAttr('installed_version', (string)($meta['installed_version'] ?? ''));
                $item->setAttr('version_display', (string)($meta['version_display'] ?? ''));
                $item->setAttr('upgrade_available', (int)($meta['upgrade_available'] ?? 0));
                $item->setAttr('last_error', (string)($meta['last_error'] ?? ''));
                $item->setAttr('last_operation', (string)($meta['last_operation'] ?? ''));

                return $item;
            });
    }

    /**
     * 打包下载应用
     * @return File
     * @throws Throwable
     */
    public function package(): File
    {
        $app = $this->findAppByNameOrFail(trim((string)$this->request->param('name', '')));

        try {
            $archive = $this->packageBuilder->build((string)$app->getAttr('name'));
            dp_log_user_action('打包下载应用', [
                'name'    => (string)$app->getAttr('name'),
                'title'   => (string)$app->getAttr('title'),
                'version' => (string)$app->getAttr('version'),
                'archive' => $archive['name'],
            ]);
            return download($archive['path'], $archive['name']);
        } catch (Throwable $e) {
            dp_log_exception($e, '打包下载应用', [
                'name' => (string)$app->getAttr('name'),
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 导入应用包
     * @return string|Json
     * @throws Exception|Throwable
     */
    public function import(): string|Json
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('package');
            if (is_array($file)) {
                $file = reset($file) ?: null;
            }

            if (!$file instanceof UploadedFile) {
                $this->error('请选择需要上传的 ZIP 应用包');
            }

            $originalName = $file->getOriginalName();
            $fileSize     = (int)$file->getSize();

            try {
                $result = $this->packageImporter->import($file);
            } catch (Throwable $e) {
                dp_log_exception($e, '导入应用包', [
                    'original_name' => $originalName,
                    'size'          => $fileSize,
                ]);
                $this->error($e->getMessage());
            }

            dp_log_user_action('导入应用包', [
                'name'          => $result['name'],
                'title'         => $result['title'],
                'path'          => $result['path'],
                'original_name' => $originalName,
                'size'          => $fileSize,
            ]);

            $message = '应用包导入成功：' . $result['name'] . '，已写入应用目录并登记为待安装状态';
            $this->success($message, '', 'reload-table');
        }

        $allowedExtensions = array_map(
            static fn(mixed $item): string => strtoupper(trim((string)$item)),
            (array)config('app_package.import.allowed_extensions', ['zip'])
        );
        $allowedExtensions = array_values(array_filter($allowedExtensions));
        $this->assign([
            'maxSizeText'           => $this->formatFileSize((int)config('app_package.import.max_size', 0)),
            'maxSizeBytes'          => (int)config('app_package.import.max_size', 0),
            'allowedExtensions'     => implode(' / ', array_filter($allowedExtensions)),
            'allowedExtensionsJson' => json_encode($allowedExtensions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'importLogsUrl'         => (string)dp_url('importLogs'),
        ]);

        return $this->fetch('import');
    }

    /**
     * 商店接入预留入口
     * @return string
     * @throws Exception
     */
    public function store(): string
    {
        $sources = $this->sourceManager->listSources();
        $this->assign([
            'storeSources'       => $sources,
            'storeSourceCount'   => count($sources),
            'officialStoreReady' => false,
        ]);

        return $this->fetch('store');
    }

    /**
     * 获取商店源包列表
     * @return Json
     */
    public function storePackages(): Json
    {
        return json([
            'code'    => 0,
            'message' => '官网商店接口尚未开放，当前版本仅保留接入预留，不提供在线包列表。',
            'data'    => [],
        ]);
    }

    /**
     * 从商店安装应用
     * @return Json
     */
    public function installFromStore(): Json
    {
        $this->error('官网商店接口尚未开放，当前版本仅保留接入预留，不支持直接安装');
    }

    /**
     * 应用日志
     * @return string
     * @throws Exception
     */
    public function logs(): string
    {
        $appName = trim((string)$this->request->param('name', ''));
        $this->findAppByNameOrFail($appName);

        $this->page
            ->preTitle($appName)
            ->title('应用操作日志')
            ->action('clear_logs', [
                'title'   => '清日志',
                'url'     => dp_url('clearLogs', ['name' => $appName, 'refresh' => 'self']),
                'class'   => 'btn btn-danger',
                'icon'    => 'ti ti-trash',
                'ajax'    => 'post',
                'confirm' => [
                    'title'       => '确认清理日志',
                    'text'        => '确定要清理该应用的全部生命周期日志吗？此操作不可恢复。',
                    'type'        => 'warning',
                    'confirmText' => '确认',
                    'cancelText'  => '取消',
                ],
            ]);

        $this->table
            ->checkbox(false)
            ->search([
                [
                    'name'        => 'log_status',
                    'type'        => 'select',
                    'placeholder' => '执行状态',
                    'options'     => $this->getAppLogStatusOptions(),
                    'col_class'   => 'layui-col-md3',
                ],
            ], [], [
                'layout' => [
                    'field_col_class'  => 'layui-col-md3',
                    'button_col_class' => 'layui-col-xs12',
                ],
            ])
            ->columns([
                ['operation', '操作', '', [], ['width' => 120]],
                ['status', '状态', 'status', [
                    'success' => '成功:green',
                    'failed'  => '失败:danger',
                    'unknown' => '未知:default',
                ], ['width' => 90]],
                ['message', '消息', '', [], ['minWidth' => 260]],
                ['create_time', '时间', '', [], ['width' => 170]],
                ['right_button', '操作', 'actions', [
                    [
                        'title' => '详情',
                        'url'   => dp_url('logDetail', ['id' => '__id__']),
                        'pop'   => [
                            'title' => '日志详情',
                            'area'  => ['820px', '680px'],
                        ],
                        'class' => 'layui-btn layui-btn-xs layui-btn-primary',
                    ],
                ], ['width' => 90]],
            ])
            ->data(fn() => $this->buildAppLogRows($appName))
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 导入日志总览
     * @return string
     * @throws Exception
     */
    public function importLogs(): string
    {
        $this->page
            ->title('应用导入日志')
            ->action('clear_import_logs', [
                'title'   => '清日志',
                'url'     => dp_url('clearImportLogs', ['refresh' => 'self']),
                'class'   => 'btn btn-danger',
                'icon'    => 'ti ti-trash',
                'ajax'    => 'post',
                'confirm' => [
                    'title'       => '确认清理导入日志',
                    'text'        => '确定要清理全部应用导入日志吗？此操作不可恢复。',
                    'type'        => 'warning',
                    'confirmText' => '确认',
                    'cancelText'  => '取消',
                ],
            ]);

        $this->table
            ->checkbox(false)
            ->search([
                [
                    'name'        => 'log_keyword',
                    'placeholder' => '应用标识 / 文件名 / 消息',
                    'col_class'   => 'layui-col-md4',
                ],
                [
                    'name'        => 'log_status',
                    'type'        => 'select',
                    'placeholder' => '执行状态',
                    'options'     => $this->getAppLogStatusOptions(),
                    'col_class'   => 'layui-col-md3',
                ],
            ], [], [
                'layout' => [
                    'field_col_class'  => 'layui-col-md4',
                    'button_col_class' => 'layui-col-xs12',
                ],
            ])
            ->columns([
                ['app_name_display', '应用标识', '', [], ['width' => 160]],
                ['status', '状态', 'status', [
                    'success' => '成功:green',
                    'failed'  => '失败:danger',
                    'unknown' => '未知:default',
                ], ['width' => 90]],
                ['message', '消息', '', [], ['minWidth' => 320]],
                ['operator_id', '操作人', '', [], ['width' => 90]],
                ['create_time', '时间', '', [], ['width' => 170]],
                ['right_button', '操作', 'actions', [
                    [
                        'title' => '详情',
                        'url'   => dp_url('logDetail', ['id' => '__id__']),
                        'pop'   => [
                            'title' => '日志详情',
                            'area'  => ['820px', '680px'],
                        ],
                        'class' => 'layui-btn layui-btn-xs layui-btn-primary',
                    ],
                ], ['width' => 90]],
            ])
            ->data(fn() => $this->buildImportLogRows())
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 日志详情
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function logDetail(): string
    {
        $id = (int)$this->request->param('id/d', 0);
        if ($id <= 0) {
            $this->error('日志不存在');
        }

        $log = AppLog::find($id);
        if (!$log instanceof AppLog) {
            $this->error('日志不存在');
        }

        $this->assign($this->buildAppLogDetailViewData($log));
        return $this->fetch('log_detail');
    }

    /**
     * 清理日志
     * @return Json
     */
    public function clearLogs(): Json
    {
        $name    = trim((string)$this->request->param('name', ''));
        $refresh = trim((string)$this->request->param('refresh', ''));
        if ($name === '') {
            $this->error('缺少应用标识');
        }

        try {
            $deleted = $this->lifecycleManager->clearLogs($name);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $message        = $deleted > 0 ? '已清理 ' . $deleted . ' 条应用日志' : '没有可清理的应用日志';
        $responseAction = $refresh === 'self' ? '' : 'reload-table';
        $this->success($message, '', $responseAction, 1);
    }

    /**
     * 清理导入日志
     * @return Json
     */
    public function clearImportLogs(): Json
    {
        $refresh = trim((string)$this->request->param('refresh', ''));

        try {
            $deleted = Db::name((string)config('app_package.storage.log_table', 'admin_app_log'))
                ->where('operation', 'import')
                ->delete();
        } catch (Throwable $e) {
            $this->error('清理应用导入日志失败：' . $e->getMessage());
        }

        $message        = $deleted > 0 ? '已清理 ' . $deleted . ' 条导入日志' : '没有可清理的导入日志';
        $responseAction = $refresh === 'self' ? '' : 'reload-table';
        $this->success($message, '', $responseAction, 1);
    }

    /**
     * 安装应用
     * @return Json
     * @throws Throwable
     */
    public function install(): Json
    {
        return $this->executeLifecycleAction('install');
    }

    /**
     * 启用应用
     * @return Json
     * @throws Throwable
     */
    public function enable(): Json
    {
        return $this->executeLifecycleAction('enable');
    }

    /**
     * 禁用应用
     * @return Json
     * @throws Throwable
     */
    public function disable(): Json
    {
        return $this->executeLifecycleAction('disable');
    }

    /**
     * 卸载应用
     * @return Json
     * @throws Throwable
     */
    public function uninstall(): Json
    {
        return $this->executeLifecycleAction('uninstall');
    }

    /**
     * 升级应用
     * @return Json
     * @throws Throwable
     */
    public function upgrade(): Json
    {
        return $this->executeLifecycleAction('upgrade');
    }

    /**
     * 编辑应用
     * @param int $id
     * @return string|Json
     * @throws Throwable
     */
    public function edit(int $id): string|Json
    {
        $app = $this->getAppOrFail($id);

        if ($this->request->isPost()) {
            $data         = $this->request->post('', null, 'trim');
            $data['id']   = $id;
            $data['sort'] = $data['sort'] ?? 0;

            $this->autoValidate('App.edit', $data);

            $before = $this->buildLogData($app);

            try {
                Db::transaction(function () use ($app, $data): void {
                    $app->save([
                        'icon' => trim((string)($data['icon'] ?? '')),
                        'sort' => (int)($data['sort'] ?? 0),
                    ]);

                    if ($this->shouldSyncAppMenu($app)) {
                        app(PermissionSyncService::class)->syncAppMenu((string)$app->getAttr('name'));
                    }
                });

                $this->clearCache($id);
                $app->refresh();

                dp_log_user_action('编辑应用注册表', [
                    'id'     => $id,
                    'before' => $before,
                    'after'  => $this->buildLogData($app),
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '编辑应用注册表', [
                    'id'             => $id,
                    'before'         => $before,
                    'submitted_data' => [
                        'icon' => trim((string)($data['icon'] ?? '')),
                        'sort' => (int)($data['sort'] ?? 0),
                    ],
                ]);
                $this->error($e->getMessage());
            }

            $this->success('编辑成功', '', 'reload-parent');
        }

        $this->buildForm($app);
        $this->form->data($app->toArray());
        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 快速编辑前校验
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @return bool
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function beforeQuickEdit(int $id, string $field, mixed $value): bool
    {
        if ($field === 'sort' && !is_numeric($value)) {
            $this->error('排序必须为数字');
        }

        if ($field === 'status' && !in_array((string)$value, ['0', '1'], true)) {
            $this->error('状态值不正确');
        }

        if ($field === 'status') {
            $record = $this->model->find($id);
            if (!$record) {
                $this->error('应用不存在');
            }

            $this->assertAppStatusCanChange($record, (int)$value);
        }

        if ($field === 'show_in_config') {
            if (!dp_is_super_admin($this->getCurrentUserId())) {
                $this->error('仅超级管理员可修改 Config 白名单');
            }

            if (!in_array((string)$value, ['0', '1'], true)) {
                $this->error('Config 白名单值不正确');
            }

            $record = $this->model->find($id);
            if (!$record) {
                $this->error('应用不存在');
            }

            if ((int)$record->getAttr('is_system') !== 1) {
                $this->error('第三方应用不允许进入 Config');
            }
        }

        return true;
    }

    /**
     * 启用前校验
     * @param array $ids
     * @return bool
     */
    protected function beforeEnable(array $ids): bool
    {
        $records = $this->model->whereIn('id', $ids)->select();
        if ($records->isEmpty()) {
            $this->error('应用不存在');
        }

        if ($records->count() !== count(array_unique(array_map('intval', $ids)))) {
            $this->error('存在不存在的应用记录');
        }

        foreach ($records as $record) {
            $this->assertAppStatusCanChange($record, 1);
        }

        return true;
    }

    /**
     * 禁用前校验
     * @param array $ids
     * @return bool
     */
    protected function beforeDisable(array $ids): bool
    {
        $records = $this->model->whereIn('id', $ids)->select();
        if ($records->isEmpty()) {
            $this->error('应用不存在');
        }

        if ($records->count() !== count(array_unique(array_map('intval', $ids)))) {
            $this->error('存在不存在的应用记录');
        }

        foreach ($records as $record) {
            $this->assertAppStatusCanChange($record, 0);
        }

        return true;
    }

    /**
     * 快速编辑后日志
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @param mixed $oldValue
     * @param mixed $result
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    protected function afterQuickEdit(int $id, string $field, mixed $value, mixed $oldValue, mixed $result): void
    {
        if ($result === false) {
            return;
        }

        if ($field === 'sort') {
            $record = $this->model->find($id);
            if ($record && $this->shouldSyncAppMenu($record)) {
                try {
                    app(PermissionSyncService::class)->syncAppMenu((string)$record->getAttr('name'));
                } catch (Throwable $e) {
                    dp_log_exception($e, '同步应用菜单排序', [
                        'id'    => $id,
                        'field' => $field,
                        'value' => $value,
                    ]);
                }
            }
        }

        dp_log_user_action('快速编辑应用注册表', [
            'id'        => $id,
            'field'     => $field,
            'old_value' => $oldValue,
            'new_value' => $value,
        ]);

        if (in_array($field, ['sort', 'status', 'show_in_config'], true)) {
            $this->clearCache($id);
            $this->success('修改成功', '', 'reload-parent');
        }
    }

    /**
     * 启用后处理
     * @param array $ids
     * @param int|false $count
     * @return void
     */
    protected function afterEnable(array $ids, int|false $count): void
    {
        if ($count !== false) {
            $this->success('启用成功', '', 'reload-parent');
        }
    }

    /**
     * 禁用后处理
     * @param array $ids
     * @param int|false $count
     * @return void
     */
    protected function afterDisable(array $ids, int|false $count): void
    {
        if ($count !== false) {
            $this->success('禁用成功', '', 'reload-parent');
        }
    }

    /**
     * 清理缓存
     * @param int $id
     * @return void
     * @throws Throwable
     */
    protected function clearCache(int $id = 0): void
    {
        $this->appService->clearCache();

        try {
            app(PermissionServiceInterface::class)->clearAllCache();
        } catch (Throwable $e) {
            dp_log_exception($e, '清理应用菜单权限缓存', ['id' => $id]);
        }
    }


    /**
     * 构建表单
     * @param AppModel $app
     * @return void
     * @throws Exception
     */
    protected function buildForm(AppModel $app): void
    {
        $this->form
            ->alert([
                '应用图标保存的是字体图标 class，例如 `ti ti-settings` 或 `fa fa-cubes`。',
                '当前表单默认仅提供框架内置的 Tabler Icons 与 Font Awesome 两套图标库。',
                '修改后会同步更新对应应用的顶级菜单图标。',
            ], '应用图标说明', 'info:icon,close')
            ->items([
                [
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => '应用标识',
                    'tips'     => '应用目录名，仅用于识别，不能修改',
                    'readonly' => true,
                ],
                [
                    'type'     => 'text',
                    'name'     => 'title',
                    'label'    => '应用名称',
                    'tips'     => '此字段来自应用目录下的 app.json 声明',
                    'readonly' => true,
                ],
                [
                    'type'     => 'text',
                    'name'     => 'version',
                    'label'    => '版本',
                    'tips'     => '此字段来自应用目录下的 app.json 声明',
                    'readonly' => true,
                ],
                [
                    'type'     => 'text',
                    'name'     => 'author',
                    'label'    => '作者',
                    'tips'     => '此字段来自应用目录下的 app.json 声明',
                    'readonly' => true,
                ],
                [
                    'type'         => 'icon',
                    'name'         => 'icon',
                    'label'        => '应用图标',
                    'tips'         => '为空时沿用应用自身声明的图标；保存后会优先使用这里的配置',
                    'builtin_only' => true,
                    'options'      => [
                        'placeholder'  => '请选择应用图标',
                        'default_icon' => 'ti ti-apps',
                    ],
                ],
                [
                    'type'  => 'number',
                    'name'  => 'sort',
                    'label' => '排序',
                    'tips'  => '数值越小越靠前',
                    'value' => (int)$app->getAttr('sort'),
                ],
                [
                    'type'  => 'static',
                    'name'  => 'lifecycle_state_text',
                    'label' => '生命周期',
                    'tips'  => '启用、禁用、安装、卸载、升级请回到列表页通过显式生命周期按钮执行，避免绕过日志与编排流程。',
                    'value' => (string)$app->getAttr('lifecycle_state_text'),
                ],
            ]);
    }

    /**
     * 获取应用记录
     * @param int $id
     * @return AppModel
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getAppOrFail(int $id): AppModel
    {
        $app = $this->model->find($id);
        if (!$app) {
            $this->error('应用不存在');
        }

        return $app;
    }

    /**
     * 构建日志数据
     * @param AppModel|array $app
     * @return array
     */
    protected function buildLogData(AppModel|array $app): array
    {
        $data = $app instanceof AppModel ? $app->toArray() : $app;

        return [
            'id'             => (int)($data['id'] ?? 0),
            'name'           => (string)($data['name'] ?? ''),
            'title'          => (string)($data['title'] ?? ''),
            'icon'           => (string)($data['icon'] ?? ''),
            'sort'           => (int)($data['sort'] ?? 0),
            'status'         => (int)($data['status'] ?? 0),
            'show_in_config' => (int)($data['show_in_config'] ?? 0),
            'is_system'      => (int)($data['is_system'] ?? 0),
            'lifecycle'      => (string)($data['lifecycle_status'] ?? ''),
        ];
    }

    /**
     * 校验应用状态是否允许修改
     * @param AppModel $app
     * @param int $status
     * @return void
     */
    protected function assertAppStatusCanChange(AppModel $app, int $status): void
    {
        if ((int)$app->getAttr('is_system') === 1 && $status !== 1) {
            $this->error('内置应用不允许禁用');
        }

        if (
            $status === 1
            && (string)$app->getAttr('lifecycle_status') !== (string)config('app_package.lifecycle.installed', 'installed')
        ) {
            $this->error('应用尚未安装，当前不能启用');
        }
    }

    /**
     * 构建操作按钮
     * @return array<int, array<string, mixed>>
     */
    protected function buildActionButtons(): array
    {
        return [
            [
                'title' => '编辑',
                'url'   => dp_url('edit', ['id' => '__id__']),
                'pop'   => [
                    'title' => '编辑应用',
                    'area'  => ['720px', '620px'],
                ],
                'class' => 'layui-btn layui-btn-xs layui-btn-default',
            ],
            [
                'title' => '下载',
                'url'   => dp_url('package', ['name' => '__name__']),
                'class' => 'layui-btn layui-btn-xs layui-btn-normal',
                'when'  => [
                    [
                        'callback' => fn(array $data): array => $this->resolvePackageActionState($data),
                    ],
                ],
            ],
            [
                'title' => '日志',
                'url'   => dp_url('logs', ['name' => '__name__']),
                'pop'   => [
                    'title' => '应用操作日志',
                    'area'  => ['980px', '760px'],
                ],
                'class' => 'layui-btn layui-btn-xs layui-btn-default',
            ],
            [
                'title'   => '安装',
                'url'     => dp_url('install', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-blue',
                'ajax'    => 'post',
                'confirm' => '确定要安装该应用吗？安装会同步权限、菜单和静态资源，但不会自动启用。',
                'when'    => [
                    [
                        'callback' => fn(array $data): array => $this->resolveLifecycleActionState($data, 'install'),
                    ],
                ],
            ],
            [
                'title'   => '启用',
                'url'     => dp_url('enable', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-green',
                'ajax'    => 'post',
                'confirm' => '确定要启用该应用吗？',
                'when'    => [
                    [
                        'callback' => fn(array $data): array => $this->resolveLifecycleActionState($data, 'enable'),
                    ],
                ],
            ],
            [
                'title'   => '禁用',
                'url'     => dp_url('disable', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-orange',
                'ajax'    => 'post',
                'confirm' => '确定要禁用该应用吗？',
                'when'    => [
                    [
                        'callback' => fn(array $data): array => $this->resolveLifecycleActionState($data, 'disable'),
                    ],
                ],
            ],
            [
                'title'   => '升级',
                'url'     => dp_url('upgrade', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-btn-warm',
                'ajax'    => 'post',
                'confirm' => '确定要升级该应用吗？升级会按当前磁盘版本刷新安装状态、权限与静态资源。',
                'when'    => [
                    [
                        'callback' => fn(array $data): array => $this->resolveLifecycleActionState($data, 'upgrade'),
                    ],
                ],
            ],
            [
                'title'   => '卸载',
                'url'     => dp_url('uninstall', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-btn-danger',
                'ajax'    => 'post',
                'confirm' => '确定要卸载该应用吗？默认仅移除运行时接入，不会自动删除业务数据。',
                'when'    => [
                    [
                        'callback' => fn(array $data): array => $this->resolveLifecycleActionState($data, 'uninstall'),
                    ],
                ],
            ],
        ];
    }

    /**
     * 解析打包下载按钮状态
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    protected function resolvePackageActionState(array $data): array
    {
        if ((int)($data['is_system'] ?? 0) === 1) {
            return [
                'state'  => 'hidden',
                'remark' => '内置应用不提供标准分发包下载',
            ];
        }

        if (!empty($data['distribution_ready'])) {
            return [];
        }

        $remark = trim((string)($data['distribution_error'] ?? ''));
        return [
            'state'  => 'disabled',
            'remark' => $remark !== '' ? $remark : '应用缺少合法的 app.json，无法打包下载',
        ];
    }

    /**
     * 解析生命周期按钮状态
     * @param array<string, mixed> $data
     * @param string $action
     * @return array<string, string>
     */
    protected function resolveLifecycleActionState(array $data, string $action): array
    {
        $isSystem        = (int)($data['is_system'] ?? 0) === 1;
        $status          = (int)($data['status'] ?? 0);
        $lifecycleStatus = trim((string)($data['lifecycle_status'] ?? ''));
        $isInstalled     = $lifecycleStatus === (string)config('app_package.lifecycle.installed', 'installed');
        $isImported      = $lifecycleStatus === (string)config('app_package.lifecycle.imported', 'imported');
        $isBroken        = $lifecycleStatus === (string)config('app_package.lifecycle.broken', 'broken');
        $upgradeable     = !empty($data['upgrade_available']);

        return match ($action) {
            'install' => ($isInstalled && !$isBroken)
                ? ['state' => 'hidden']
                : [],
            'enable' => $isInstalled && $status !== 1
                ? []
                : ['state' => 'hidden'],
            'disable' => (!$isSystem && $isInstalled && $status === 1)
                ? []
                : ['state' => 'hidden'],
            'upgrade' => $upgradeable
                ? []
                : ['state' => 'hidden'],
            'uninstall' => (!$isSystem && ($isInstalled || $isBroken))
                ? ($status === 1 ? ['state' => 'disabled', 'remark' => '请先禁用应用后再卸载'] : [])
                : ['state' => 'hidden'],
            default => $isImported ? [] : ['state' => 'hidden'],
        };
    }

    /**
     * 是否应同步应用菜单
     * @param AppModel $app
     * @return bool
     */
    protected function shouldSyncAppMenu(AppModel $app): bool
    {
        return (string)$app->getAttr('lifecycle_status')
            === (string)config('app_package.lifecycle.installed', 'installed');
    }

    /**
     * 执行生命周期动作
     * @param string $action
     * @return Json
     * @throws Throwable
     */
    protected function executeLifecycleAction(string $action): Json
    {
        $name = trim((string)$this->request->param('name', ''));
        if ($name === '') {
            $this->error('缺少应用标识');
        }

        try {
            match ($action) {
                'install' => $this->lifecycleManager->install($name, $this->getCurrentUserId()),
                'enable' => $this->lifecycleManager->enable($name, $this->getCurrentUserId()),
                'disable' => $this->lifecycleManager->disable($name, $this->getCurrentUserId()),
                'uninstall' => $this->lifecycleManager->uninstall(
                    $name,
                    $this->getCurrentUserId(),
                    (bool)$this->request->param('cleanup_data/d', 0)
                ),
                'upgrade' => $this->lifecycleManager->upgrade($name, $this->getCurrentUserId()),
                default => throw new Exception('不支持的应用生命周期操作'),
            };

            $this->clearCache();
        } catch (Throwable $e) {
            dp_log_exception($e, '应用生命周期操作失败', [
                'name'      => $name,
                'operation' => $action,
            ]);
            $this->error($e->getMessage());
        }

        $this->success('操作成功', '', 'reload-table');
    }

    /**
     * 构建应用日志行
     * @param string $appName
     * @return Paginator
     * @throws DbException
     */
    protected function buildAppLogRows(string $appName): Paginator
    {
        $searchParam = (string)config('table.search.param', '_s');
        $search      = $this->request->param($searchParam, []);
        $status      = is_array($search) ? trim((string)($search['log_status'] ?? '')) : '';

        $query = AppLog::where('app_name', $appName)
            ->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate(dp_get_list_rows());
    }

    /**
     * 构建导入日志行
     * @return Paginator
     * @throws DbException
     */
    protected function buildImportLogRows(): Paginator
    {
        $searchParam = (string)config('table.search.param', '_s');
        $search      = $this->request->param($searchParam, []);
        $status      = is_array($search) ? trim((string)($search['log_status'] ?? '')) : '';
        $keyword     = is_array($search) ? trim((string)($search['log_keyword'] ?? '')) : '';

        $query = AppLog::where('operation', 'import')
            ->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->whereLike('app_name', '%' . $keyword . '%')
                    ->whereOrLike('message', '%' . $keyword . '%')
                    ->whereOrLike('context_json', '%' . $keyword . '%');
            });
        }

        return $query
            ->paginate(dp_get_list_rows())
            ->each(function (AppLog $log) {
                $appName = trim((string)$log->getAttr('app_name'));
                $log->setAttr('app_name_display', $appName !== '' ? $appName : '未识别应用');
                return $log;
            });
    }

    /**
     * 构建应用日志详情视图数据
     * @param AppLog $log
     * @return array<string, mixed>
     */
    protected function buildAppLogDetailViewData(AppLog $log): array
    {
        $status         = trim((string)$log->getAttr('status'));
        $statusText     = match ($status) {
            'success' => '成功',
            'failed' => '失败',
            default => $status !== '' ? $status : '未知',
        };
        $appName        = trim((string)$log->getAttr('app_name'));
        $appNameDisplay = $appName !== '' ? $appName : '未识别应用';

        $context          = trim((string)$log->getAttr('context_json'));
        $formattedContext = $context !== '' ? $this->formatJsonText($context) : '{}';

        return [
            'appLogTitle' => $appNameDisplay . ' · ' . $log->getAttr('operation'),
            'statusText'  => $statusText,
            'statusClass' => $status === 'success' ? 'success' : ($status === 'failed' ? 'danger' : 'default'),
            'detailRows'  => [
                ['label' => '应用标识', 'value' => $appNameDisplay],
                ['label' => '操作类型', 'value' => (string)$log->getAttr('operation')],
                ['label' => '执行状态', 'value' => $statusText],
                ['label' => '操作人ID', 'value' => (string)$log->getAttr('operator_id')],
                ['label' => '发生时间', 'value' => date('Y-m-d H:i:s', (int)$log->getAttr('create_time'))],
            ],
            'messageText' => trim((string)$log->getAttr('message')),
            'contextText' => $formattedContext,
        ];
    }

    /**
     * 获取日志状态选项
     * @return array<string, string>
     */
    protected function getAppLogStatusOptions(): array
    {
        return [
            ''        => '全部状态',
            'success' => '成功',
            'failed'  => '失败',
        ];
    }

    /**
     * 格式化 JSON 文本
     * @param string $json
     * @return string
     */
    protected function formatJsonText(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $json;
        }

        $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $formatted === false ? $json : $formatted;
    }

    /**
     * 根据名称获取应用记录
     * @param string $name
     * @return AppModel
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function findAppByNameOrFail(string $name): AppModel
    {
        $name = trim($name);
        if ($name === '') {
            $this->error('应用标识不能为空');
        }

        $app = $this->model->where('name', $name)->find();
        if (!$app) {
            $this->error('应用不存在');
        }

        return $app;
    }

    /**
     * 格式化文件大小
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '不限';
        }

        return dp_format_size($bytes);
    }
}
