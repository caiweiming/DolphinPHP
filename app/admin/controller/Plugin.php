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
use app\common\model\PluginLog;
use app\common\plugin\PluginDescriptor;
use app\common\plugin\PluginManager;
use app\common\plugin\PluginPackageBuilder;
use app\common\plugin\PluginPackageImporter;
use app\common\plugin\PluginReadmeRenderer;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\file\UploadedFile;
use think\Paginator;
use think\response\File;
use think\response\Json;
use Throwable;

/**
 * 插件管理控制器
 */
#[Permission('插件管理', icon: 'ti ti-plug', sort: 100)]
class Plugin extends Auth
{
    /**
     * 插件管理器
     * @var PluginManager
     */
    protected PluginManager $pluginManager;

    /**
     * 插件分发包构建器
     * @var PluginPackageBuilder
     */
    protected PluginPackageBuilder $packageBuilder;

    /**
     * 插件分发包导入器
     * @var PluginPackageImporter
     */
    protected PluginPackageImporter $packageImporter;

    /**
     * README 渲染器
     * @var PluginReadmeRenderer
     */
    protected PluginReadmeRenderer $readmeRenderer;

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->pluginManager   = app(PluginManager::class);
        $this->packageBuilder  = app(PluginPackageBuilder::class);
        $this->packageImporter = app(PluginPackageImporter::class);
        $this->readmeRenderer  = app(PluginReadmeRenderer::class);
    }

    /**
     * 插件列表
     * @return string
     * @throws Exception
     */
    public function index(): string
    {
        if (!$this->pluginManager->storageReady()) {
            $this->table->alert(
                [
                    '插件数据表尚未创建，当前页面仅展示磁盘扫描结果。'
                ],
                '插件系统未完成初始化',
                'warning:icon,close'
            );
        }

        $this->table
            ->alert([
                'Composer 安装或上传插件包不会自动安装或启用插件。',
                '点击“安装”仅登记插件生命周期状态，不会默认发布静态资源。',
                '点击“启用”后系统才会接入运行时能力，并自动发布插件 `public/` 下的静态资源。',
                '“发布”按钮用于资源重发，适合静态文件更新、软链接异常或手动修复后再次发布。'
            ], '插件使用说明', 'info:icon,close')
            ->checkbox(false)
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '插件标识 / 名称 / 作者',
                ],
                [
                    'name'        => 'plugin_state',
                    'type'        => 'select',
                    'placeholder' => '插件状态',
                    'options'     => $this->getPluginStateOptions(),
                ],
            ])
            ->columns([
                ['name', '插件标识', '', [], ['minWidth' => 180]],
                ['title_display', '插件名称', '', [], ['minWidth' => 170]],
                ['version', '版本', '', [], ['width' => 110]],
                ['author', '作者', '', [], ['minWidth' => 120]],
                ['status_text', '状态', 'status', [
                    '未安装' => '未安装:default',
                    '已安装' => '已安装:blue',
                    '已启用' => '已启用:green',
                    '已禁用' => '已禁用:orange',
                    '无效'   => '无效:danger',
                    '异常'   => '异常:danger',
                ], ['width' => 120]],
                ['enabled', '已启用', 'yes_no', [], ['width' => 90]],
                ['path_short', '目录', '', [], ['minWidth' => 240]],
                ['error_display', '错误信息'],
                ['right_button', '操作', 'actions', $this->buildActionButtons(), ['minWidth' => 320]],
            ])
            ->toolbar([
                [
                    'title' => '上传插件',
                    'name'  => 'upload_plugin',
                    'event' => 'upload',
                    'icon'  => 'ti ti-upload',
                    'url'   => dp_url('import'),
                    'pop'   => [
                        'title' => '上传插件',
                        'area'  => ['760px', '420px'],
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
            ])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 插件列表数据
     * @return array
     */
    protected function data(): array
    {
        return $this->applyPluginFilters($this->pluginManager->getDisplayRows());
    }

    /**
     * 插件详情
     * @return string
     * @throws Exception
     */
    public function detail(): string
    {
        $descriptor = $this->findDescriptorOrFail(trim((string)$this->request->param('name', '')));
        $this->assign($this->buildDetailViewData($descriptor));

        return $this->fetch('detail');
    }

    /**
     * 打包下载插件
     * @return File
     */
    public function package(): File
    {
        $descriptor = $this->findDescriptorOrFail(trim((string)$this->request->param('name', '')));

        try {
            $archive = $this->packageBuilder->build($descriptor);
            return download($archive['path'], $archive['name']);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 导入插件包
     * @return string|Json
     * @throws Exception
     */
    public function import(): string|Json
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('package');
            if (is_array($file)) {
                $file = reset($file) ?: null;
            }

            if (!$file instanceof UploadedFile) {
                $this->error('请选择需要上传的 ZIP 插件包');
            }

            try {
                $result = $this->packageImporter->import($file);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            $message = '插件包导入成功：' . $result['name'] . '，已写入插件目录，请按需安装或启用';
            $this->success($message, '', 'reload-table');
        }

        $allowedExtensions = array_map(
            static fn(mixed $item): string => strtoupper(trim((string)$item)),
            (array)config('plugin.import.allowed_extensions', ['zip'])
        );
        $allowedExtensions = array_values(array_filter($allowedExtensions));
        $this->assign([
            'maxSizeText'           => $this->formatFileSize((int)config('plugin.import.max_size', 0)),
            'maxSizeBytes'          => (int)config('plugin.import.max_size', 0),
            'allowedExtensions'     => implode(' / ', array_filter($allowedExtensions)),
            'allowedExtensionsJson' => json_encode($allowedExtensions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return $this->fetch('import');
    }

    /**
     * 插件日志
     * @return string
     * @throws Exception
     */
    public function logs(): string
    {
        $pluginName = trim((string)$this->request->param('name', ''));
        $this->findDescriptorOrFail($pluginName);

        $this->page
            ->preTitle($pluginName)
            ->title('插件操作日志');

        if ($this->pluginManager->storageReady()) {
            $this->page->action('clear_logs', [
                'title'   => '清日志',
                'url'     => dp_url('clearLogs', ['name' => $pluginName, 'refresh' => 'self']),
                'class'   => 'btn btn-danger',
                'icon'    => 'ti ti-trash',
                'ajax'    => 'post',
                'confirm' => [
                    'title'       => '确认清理日志',
                    'text'        => '确定要清理该插件的全部操作日志吗？此操作不可恢复。',
                    'type'        => 'warning',
                    'confirmText' => '确认',
                    'cancelText'  => '取消',
                ],
            ]);
        }

        $this->table
            ->checkbox(false)
            ->search([
                [
                    'name'        => 'log_status',
                    'type'        => 'select',
                    'placeholder' => '执行状态',
                    'options'     => $this->getPluginLogStatusOptions(),
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
                    'succeed' => '成功:green',
                    'ok'      => '成功:green',
                    'failed'  => '失败:danger',
                    'fail'    => '失败:danger',
                    'error'   => '失败:danger',
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
            ->data(fn() => $this->buildPluginLogRows($pluginName))
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 插件日志详情
     * @return string
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function logDetail(): string
    {
        $id = (int)$this->request->param('id/d', 0);
        if ($id <= 0) {
            $this->error('日志不存在');
        }

        $log = PluginLog::find($id);
        if (!$log instanceof PluginLog) {
            $this->error('日志不存在');
        }

        $this->assign($this->buildPluginLogDetailViewData($log));
        return $this->fetch('log_detail');
    }

    /**
     * 安装插件
     * @return Json
     */
    public function install(): Json
    {
        return $this->executePluginAction('install');
    }

    /**
     * 启用插件
     * @return Json
     */
    public function enable(): Json
    {
        return $this->executePluginAction('enable');
    }

    /**
     * 禁用插件
     * @return Json
     */
    public function disable(): Json
    {
        return $this->executePluginAction('disable');
    }

    /**
     * 发布插件静态资源
     * @return Json
     */
    public function publish(): Json
    {
        return $this->executePluginAction('publish');
    }

    /**
     * 清理插件日志
     * @return Json
     */
    public function clearLogs(): Json
    {
        $name    = trim((string)$this->request->param('name', ''));
        $refresh = trim((string)$this->request->param('refresh', ''));
        if ($name === '') {
            $this->error('缺少插件标识');
        }

        try {
            $deleted = $this->pluginManager->clearLogs($name);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $message        = $deleted > 0 ? '已清理 ' . $deleted . ' 条插件日志' : '没有可清理的插件日志';
        $responseAction = $refresh === 'self' ? '' : 'reload-table';
        $this->success($message, '', $responseAction, 1);
    }

    /**
     * 卸载插件
     * @return Json
     */
    public function uninstall(): Json
    {
        return $this->executePluginAction('uninstall');
    }

    /**
     * 执行插件动作
     * @param string $action
     * @return Json
     */
    private function executePluginAction(string $action): Json
    {
        $name = trim((string)$this->request->param('name', ''));
        if ($name === '') {
            $this->error('缺少插件标识');
        }

        try {
            match ($action) {
                'install' => $this->pluginManager->install($name),
                'enable' => $this->pluginManager->enable($name),
                'disable' => $this->pluginManager->disable($name),
                'publish' => $this->pluginManager->publishAssets($name),
                'uninstall' => $this->pluginManager->uninstall($name),
                default => throw new RuntimeException('不支持的插件操作'),
            };
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $responseAction = match ($action) {
            'enable', 'disable', 'publish', 'uninstall' => 'reload-parent',
            default => 'reload-table',
        };

        $this->success('操作成功', '', $responseAction);
    }

    /**
     * 查找插件描述对象
     * @param string $name
     * @return PluginDescriptor
     */
    private function findDescriptorOrFail(string $name): PluginDescriptor
    {
        if ($name === '') {
            $this->error('缺少插件标识');
        }

        $descriptor = $this->pluginManager->all()[$name] ?? null;
        if (!$descriptor instanceof PluginDescriptor) {
            $this->error('插件不存在');
        }

        return $descriptor;
    }

    /**
     * 构建详情页数据
     * @param PluginDescriptor $descriptor
     * @return array<string, mixed>
     */
    private function buildDetailViewData(PluginDescriptor $descriptor): array
    {
        $path       = $descriptor->getPath();
        $readmePath = $this->locateReadmePath($path);
        $readme     = $this->readmeRenderer->renderFile($readmePath);

        return [
            'pluginTitle'       => $descriptor->getTitle(),
            'pluginName'        => $descriptor->getName(),
            'pluginDescription' => $descriptor->getDescription() !== '' ? $descriptor->getDescription() : '插件未提供描述信息。',
            'statusText'        => $descriptor->getStatusText(),
            'statusClass'       => $this->resolveStatusClass($descriptor->getStatus()),
            'packageUrl'        => dp_url('package', ['name' => $descriptor->getName()]),
            'packageAvailable'  => $this->canPackage($descriptor),
            'packageRemark'     => $this->resolvePackageRemarkByDescriptor($descriptor),
            'detailRows'        => [
                ['label' => '插件标识', 'value' => $descriptor->getName()],
                ['label' => '插件名称', 'value' => $descriptor->getTitle()],
                ['label' => '版本', 'value' => $descriptor->getVersion() !== '' ? $descriptor->getVersion() : '-'],
                ['label' => '作者', 'value' => $descriptor->getAuthor() !== '' ? $descriptor->getAuthor() : '-'],
                ['label' => '当前状态', 'value' => $descriptor->getStatusText()],
                ['label' => '协议版本', 'value' => $descriptor->getPluginApiVersion() !== '' ? $descriptor->getPluginApiVersion() : '-'],
                ['label' => '插件目录', 'value' => $path !== '' ? str_replace(root_path(), '', $path) : '-'],
                ['label' => '主类', 'value' => $descriptor->getMainClass() !== '' ? $descriptor->getMainClass() : '-'],
                ['label' => '服务提供者', 'value' => $descriptor->getProviderClass() !== '' ? $descriptor->getProviderClass() : '-'],
                ['label' => '依赖插件', 'value' => $this->formatDependencies($descriptor->getDependencies())],
                ['label' => '运行要求', 'value' => $this->formatAssocMap($descriptor->getRequire())],
                ['label' => '说明文档', 'value' => $readmePath !== '' ? str_replace(root_path(), '', $readmePath) : '未提供 README.md'],
            ],
            'capabilityRows'    => $this->detectCapabilities($path),
            'readmeHtml'        => $readme['html'],
            'readmeContent'     => $readme['raw'],
            'readmeRendered'    => $readme['rendered'],
            'hasReadme'         => $readme['raw'] !== '',
        ];
    }

    /**
     * 定位插件 README
     * @param string $pluginPath
     * @return string
     */
    private function locateReadmePath(string $pluginPath): string
    {
        if ($pluginPath === '') {
            return '';
        }

        foreach (['README.md', 'readme.md', 'Readme.md'] as $filename) {
            $path = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * 检测插件能力骨架
     * @param string $pluginPath
     * @return array<int, array<string, string>>
     */
    private function detectCapabilities(string $pluginPath): array
    {
        if ($pluginPath === '') {
            return [];
        }

        $definitions = [
            ['label' => '控制器', 'path' => 'src/Controller', 'type' => 'dir'],
            ['label' => '表单项', 'path' => 'src/Form', 'type' => 'dir'],
            ['label' => '表格列', 'path' => 'src/Table', 'type' => 'dir'],
            ['label' => '图表扩展', 'path' => 'src/Chart', 'type' => 'dir'],
            ['label' => '上传驱动', 'path' => 'src/Upload', 'type' => 'dir'],
            ['label' => '命令', 'path' => 'src/Command', 'type' => 'dir'],
            ['label' => '中间件', 'path' => 'src/Middleware', 'type' => 'dir'],
            ['label' => '组件处理', 'path' => 'src/Component', 'type' => 'dir'],
            ['label' => '配置文件', 'path' => 'config', 'type' => 'dir'],
            ['label' => '视图模板', 'path' => 'view', 'type' => 'dir'],
            ['label' => '语言包', 'path' => 'lang', 'type' => 'dir'],
            ['label' => '静态资源', 'path' => 'public', 'type' => 'dir'],
            ['label' => '路由声明', 'path' => 'routes.php', 'type' => 'file'],
            ['label' => '权限声明', 'path' => 'permissions.php', 'type' => 'file'],
        ];

        $capabilities = [];
        foreach ($definitions as $definition) {
            $fullPath = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $definition['path'];
            $exists   = $definition['type'] === 'dir' ? is_dir($fullPath) : is_file($fullPath);
            if (!$exists) {
                continue;
            }

            $capabilities[] = [
                'label' => $definition['label'],
                'value' => $definition['type'] === 'dir'
                    ? $this->countPhpFiles($fullPath) . ' 个文件'
                    : basename($fullPath),
            ];
        }

        return $capabilities;
    }

    /**
     * 统计目录内 PHP/模板文件数量
     * @param string $directory
     * @return int
     */
    private function countPhpFiles(string $directory): int
    {
        $count    = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $extension = strtolower($item->getExtension());
            if (in_array($extension, ['php', 'html', 'js', 'css'], true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 格式化依赖列表
     * @param array<int, mixed> $dependencies
     * @return string
     */
    private function formatDependencies(array $dependencies): string
    {
        if ($dependencies === []) {
            return '无';
        }

        $items = [];
        foreach ($dependencies as $dependency) {
            if (is_string($dependency) && $dependency !== '') {
                $items[] = $dependency;
                continue;
            }

            if (is_array($dependency)) {
                $name    = trim((string)($dependency['name'] ?? ''));
                $version = trim((string)($dependency['version'] ?? ''));
                if ($name !== '') {
                    $items[] = $version !== '' ? $name . ' (' . $version . ')' : $name;
                }
            }
        }

        return $items !== [] ? implode('、', $items) : '无';
    }

    /**
     * 格式化键值映射
     * @param array<string, mixed> $map
     * @return string
     */
    private function formatAssocMap(array $map): string
    {
        if ($map === []) {
            return '无';
        }

        $items = [];
        foreach ($map as $key => $value) {
            $items[] = $key . ': ' . (is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return implode('、', $items);
    }

    /**
     * 状态样式
     * @param string $status
     * @return string
     */
    private function resolveStatusClass(string $status): string
    {
        return match ($status) {
            'enabled' => 'success',
            'installed' => 'info',
            'disabled' => 'warning',
            'invalid', 'broken' => 'danger',
            default => 'default',
        };
    }

    /**
     * 获取状态筛选项
     * @return array<string, string>
     */
    private function getPluginStateOptions(): array
    {
        return [
            ''         => '全部状态',
            'enabled'  => '已启用',
            'disabled' => '已禁用',
            'issue'    => '异常/无效',
        ];
    }

    /**
     * 格式化文件大小
     * @param int $size
     * @return string
     */
    private function formatFileSize(int $size): string
    {
        if ($size <= 0) {
            return '不限';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float)$size;
        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return rtrim(rtrim(number_format($value, $index === 0 ? 0 : 2, '.', ''), '0'), '.') . ' ' . $units[$index];
    }

    /**
     * 应用插件筛选
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyPluginFilters(array $rows): array
    {
        $searchParam = (string)config('table.search.param', '_s');
        $search      = $this->request->param($searchParam, []);
        $state       = '';
        $keyword     = '';
        if (is_array($search)) {
            $state   = trim((string)($search['plugin_state'] ?? ''));
            $keyword = trim((string)($search['keyword'] ?? ''));
        }

        if ($state === '' && $keyword === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($state, $keyword): bool {
            $rowStatus    = (string)($row['status'] ?? '');
            $matchesState = match ($state) {
                'enabled' => $rowStatus === 'enabled',
                'disabled' => $rowStatus === 'disabled',
                'issue' => in_array($rowStatus, ['invalid', 'broken'], true),
                default => true,
            };

            if (!$matchesState) {
                return false;
            }

            if ($keyword === '') {
                return true;
            }

            $keyword   = mb_strtolower($keyword);
            $haystacks = [
                (string)($row['name'] ?? ''),
                (string)($row['title'] ?? ''),
                (string)($row['author'] ?? ''),
                (string)($row['description'] ?? ''),
            ];

            foreach ($haystacks as $value) {
                if ($value !== '' && mb_stripos($value, $keyword) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * 构建操作按钮
     * @return array<int, array<string, mixed>>
     */
    private function buildActionButtons(): array
    {
        return [
            [
                'title' => '详情',
                'url'   => dp_url('detail', ['name' => '__name__']),
                'pop'   => [
                    'title' => '插件详情',
                    'area'  => ['960px', '760px'],
                ],
                'class' => 'layui-btn layui-btn-xs layui-btn-primary',
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
                    'title' => '插件操作日志',
                    'area'  => ['980px', '760px'],
                ],
                'class' => 'layui-btn layui-btn-xs layui-btn-default',
            ],
            [
                'title'   => '安装',
                'url'     => dp_url('install', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-blue',
                'ajax'    => 'post',
                'confirm' => '确定要安装该插件吗？',
                'when'    => [
                    [
                        'state' => 'hidden',
                        'field' => 'installed',
                        'op'    => '=',
                        'value' => true,
                    ],
                    [
                        'callback' => fn(array $data): array => $this->resolveInvalidActionState($data, '插件元数据无效，无法安装'),
                    ],
                ],
            ],
            [
                'title'   => '启用',
                'url'     => dp_url('enable', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-green',
                'ajax'    => 'post',
                'confirm' => '确定要启用该插件吗？',
                'when'    => [
                    [
                        'state' => 'hidden',
                        'rules' => [
                            ['field' => 'installed', 'op' => '=', 'value' => false],
                            ['field' => 'enabled', 'op' => '=', 'value' => true],
                        ],
                        'logic' => 'or',
                    ],
                    [
                        'callback' => fn(array $data): array => $this->resolveInvalidActionState($data, '插件当前状态异常，无法启用'),
                    ],
                ],
            ],
            [
                'title'   => '禁用',
                'url'     => dp_url('disable', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-bg-orange',
                'ajax'    => 'post',
                'confirm' => '确定要禁用该插件吗？',
                'when'    => [
                    [
                        'state' => 'hidden',
                        'field' => 'enabled',
                        'op'    => '=',
                        'value' => false,
                    ],
                ],
            ],
            [
                'title'   => '发布',
                'url'     => dp_url('publish', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-btn-warm',
                'ajax'    => 'post',
                'confirm' => '确定要重新发布该插件的静态资源吗？',
                'when'    => [
                    [
                        'state' => 'hidden',
                        'field' => 'installed',
                        'op'    => '=',
                        'value' => false,
                    ],
                    [
                        'callback' => fn(array $data): array => $this->resolveInvalidActionState($data, '插件元数据无效，无法发布静态资源'),
                    ],
                ],
            ],
            [
                'title'   => '卸载',
                'url'     => dp_url('uninstall', ['name' => '__name__']),
                'class'   => 'layui-btn layui-btn-xs layui-btn-danger',
                'ajax'    => 'post',
                'confirm' => '确定要卸载该插件吗？',
                'when'    => [
                    [
                        'state' => 'hidden',
                        'field' => 'installed',
                        'op'    => '=',
                        'value' => false,
                    ],
                    [
                        'state'  => 'disabled',
                        'field'  => 'enabled',
                        'op'     => '=',
                        'value'  => true,
                        'remark' => '请先禁用插件后再卸载',
                    ],
                ],
            ],
        ];
    }

    /**
     * 无效插件动作状态
     * @param array<string, mixed> $data
     * @param string $fallbackRemark
     * @return array<string, string>
     */
    private function resolveInvalidActionState(array $data, string $fallbackRemark): array
    {
        $invalid = (bool)($data['invalid'] ?? false);
        $status  = (string)($data['status'] ?? '');
        if (!$invalid && !in_array($status, ['invalid', 'broken'], true)) {
            return [];
        }

        $remark = trim((string)($data['error'] ?? ''));
        return [
            'state'  => 'disabled',
            'remark' => $remark !== '' ? $remark : $fallbackRemark,
        ];
    }

    /**
     * 插件打包动作状态
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function resolvePackageActionState(array $data): array
    {
        $path    = trim((string)($data['path'] ?? ''));
        $invalid = (bool)($data['invalid'] ?? false);
        $remark  = trim((string)($data['error'] ?? ''));

        if ($invalid) {
            return [
                'state'  => 'disabled',
                'remark' => $remark !== '' ? $remark : '插件元数据无效，无法打包',
            ];
        }

        if ($path === '' || !is_dir($path)) {
            return [
                'state'  => 'disabled',
                'remark' => '插件目录不存在，无法打包',
            ];
        }

        return [];
    }

    /**
     * 是否允许打包
     * @param PluginDescriptor $descriptor
     * @return bool
     */
    private function canPackage(PluginDescriptor $descriptor): bool
    {
        return $descriptor->isValid() && $descriptor->getPath() !== '' && is_dir($descriptor->getPath());
    }

    /**
     * 获取插件打包提示文案
     * @param PluginDescriptor $descriptor
     * @return string
     */
    private function resolvePackageRemarkByDescriptor(PluginDescriptor $descriptor): string
    {
        if ($descriptor->isValid() && $descriptor->getPath() !== '' && is_dir($descriptor->getPath())) {
            return '';
        }

        if (!$descriptor->isValid()) {
            return $descriptor->getError() !== '' ? $descriptor->getError() : '插件元数据无效，无法打包';
        }

        return '插件目录不存在，无法打包';
    }

    /**
     * 构建插件日志列表
     * @param string $pluginName
     * @return Paginator
     * @throws DbException
     */
    private function buildPluginLogRows(string $pluginName): Paginator
    {
        $query  = PluginLog::where('plugin_name', $pluginName);
        $status = $this->getTableSearchValue();

        if ($status === 'success') {
            $query->whereIn('status', ['success', 'succeed', 'ok']);
        } elseif ($status === 'failed') {
            $query->whereIn('status', ['failed', 'fail', 'error']);
        } elseif ($status === 'unknown') {
            $query->whereNotIn('status', ['success', 'succeed', 'ok', 'failed', 'fail', 'error']);
        }

        return $query
            ->order('id', 'desc')
            ->limit(50)
            ->paginate(dp_get_list_rows());
    }

    /**
     * 构建插件日志详情页数据
     * @param PluginLog $log
     * @return array<string, mixed>
     */
    private function buildPluginLogDetailViewData(PluginLog $log): array
    {
        $context       = (string)$log->getAttr('context_json');
        $prettyContext = $context;
        $decoded       = json_decode($context, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($pretty)) {
                $prettyContext = $pretty;
            }
        }

        $statusText = $this->normalizePluginLogStatus((string)$log->getAttr('status'));

        return [
            'pluginLogTitle' => '插件日志详情',
            'statusText'     => $statusText,
            'statusClass'    => strtolower($statusText) === '失败' ? 'danger' : 'success',
            'detailRows'     => [
                ['label' => '日志ID', 'value' => (string)$log->getAttr('id')],
                ['label' => '插件标识', 'value' => (string)$log->getAttr('plugin_name')],
                ['label' => '操作类型', 'value' => (string)$log->getAttr('operation')],
                ['label' => '执行状态', 'value' => $statusText],
                ['label' => '操作人ID', 'value' => (string)$log->getAttr('operator_id')],
                ['label' => '创建时间', 'value' => date('Y-m-d H:i:s', (int)$log->getAttr('create_time'))],
            ],
            'messageText'    => (string)$log->getAttr('message'),
            'contextText'    => $prettyContext !== '' ? $prettyContext : '{}',
        ];
    }

    /**
     * 规范化插件日志状态文案
     * @param string $status
     * @return string
     */
    private function normalizePluginLogStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'success', 'succeed', 'ok' => '成功',
            'failed', 'fail', 'error' => '失败',
            default => '未知',
        };
    }

    /**
     * 获取插件日志状态筛选项
     * @return array<string, string>
     */
    private function getPluginLogStatusOptions(): array
    {
        return [
            ''        => '全部状态',
            'success' => '成功',
            'failed'  => '失败',
            'unknown' => '未知',
        ];
    }

    /**
     * 读取表格搜索值
     * @return string
     */
    private function getTableSearchValue(): string
    {
        $searchParam = (string)config('table.search.param', '_s');
        $search      = $this->request->param($searchParam, []);
        if (!is_array($search)) {
            return '';
        }

        return trim((string)($search['log_status'] ?? ''));
    }
}
