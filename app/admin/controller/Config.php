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
use app\common\model\Config as ConfigModel;
use app\common\service\AppService;
use app\common\service\ConfigService;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 配置管理控制器
 */
#[Permission('配置管理', icon: 'ti ti-tool', sort: 20)]
class Config extends Auth
{
    /**
     * 配置模型
     * @var ConfigModel
     */
    protected ConfigModel $model;

    /**
     * 配置服务
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * 应用服务
     * @var AppService
     */
    protected AppService $appService;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['sort', 'status'];

    /**
     * 删除快照
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
        $this->model         = new ConfigModel();
        $this->configService = app(ConfigService::class);
        $this->appService    = app(AppService::class);
        $this->appService->syncInstalledApps();
    }

    /**
     * 配置定义列表
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        $requestedApp = trim((string)$this->request->param('app', ''));
        $currentApp   = $this->getCurrentAppContext();

        if (!$currentApp) {
            $this->buildEmptyState($requestedApp);
            $this->page->row($this->form);
            return $this->fetch();
        }

        $this->table
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '名称 / 键名 / 备注',
                    'op'          => 'like',
                    'fields'      => ['title', 'key', 'remark'],
                ],
                [
                    'name'        => 'group',
                    'type'        => 'select',
                    'placeholder' => '分组',
                    'options'     => $this->configService->getGroupOptions((string)$currentApp['name']),
                ],
                [
                    'name'        => 'type',
                    'type'        => 'select',
                    'placeholder' => '类型',
                    'options'     => $this->configService->getTypeOptions(),
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
                    'placeholder' => '内置项',
                    'options'     => [1 => '系统内置', 0 => '普通配置'],
                ],
            ])
            ->columns([
                ['id', 'ID', '', [], ['width' => 70]],
                ['group', '分组', '', [], ['minWidth' => 120]],
                ['title', '名称', '', [], ['minWidth' => 160]],
                ['key', '键名', '', [], ['minWidth' => 180]],
                ['type_text', '类型', '', [], ['width' => 120]],
                ['sort', '排序', 'text.edit', [], ['width' => 90]],
                ['status', '状态', 'switch', ['1' => '启用', '0' => '禁用'], ['width' => 90]],
                ['is_system', '内置', 'yes_no', [], ['width' => 90]],
                ['remark', '备注', '', [], ['minWidth' => 180]],
                ['update_time', '更新时间', '', [], ['width' => 170]],
                ['right_button', '操作', 'actions', [
                    [
                        'title' => '编辑',
                        'url'   => dp_url('edit', ['id' => '__id__']),
                        'pop'   => [
                            'title' => '编辑配置定义',
                            'area'  => ['860px', '760px'],
                        ],
                        'class' => 'layui-btn layui-btn-xs layui-btn-default',
                    ],
                    'delete',
                ], ['width' => 150]],
            ])
            ->toolbar([
                [
                    'title' => '新增配置',
                    'name'  => 'add',
                    'event' => 'add',
                    'icon'  => 'ti ti-plus',
                    'url'   => dp_url('create', ['app' => (string)$currentApp['name']]),
                    'pop'   => [
                        'title' => '新增配置定义',
                        'area'  => ['860px', '760px'],
                    ],
                ],
                'enable',
                'disable',
                'delete',
            ])
            ->render();

        $this->page->tabs($this->buildAppTabs($currentApp, $this->table), [
            'id'       => 'admin-config-app-tabs',
            'active'   => (string)$currentApp['name'],
            'remember' => true,
        ]);

        return $this->fetch();
    }

    /**
     * 列表数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        $currentApp = $this->getCurrentAppContext();
        if (!$currentApp) {
            return $this->model->where('id', 0)->paginate(dp_get_list_rows());
        }

        return $this->model
            ->where('app', (string)$currentApp['name'])
            ->where($this->getSearchWhere())
            ->order('group', 'asc')
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate(dp_get_list_rows())
            ->each(function (ConfigModel $item) {
                $type = (string)$item->getAttr('type');

                $item->setAttr('type_text', $this->configService->getTypeOptions()[$type] ?? $type);

                return $item;
            });
    }

    /**
     * 新增配置定义
     * @return string|Json
     * @throws Throwable
     */
    public function create(): string|Json
    {
        $currentApp = $this->getCurrentAppContext();
        if (!$currentApp) {
            $this->error('当前没有被授权进入 Config 的内置应用，请先在应用管理中设置');
        }

        if ($this->request->isPost()) {
            $data              = $this->request->post('', null, 'trim');
            $data['app']       = $data['app'] ?? (string)$currentApp['name'];
            $data['status']    = $data['status'] ?? 0;
            $data['is_system'] = $data['is_system'] ?? 0;

            $this->autoValidate('Config.create', $data);

            try {
                $payload = $this->configService->preparePersistData($data, null, dp_is_super_admin($this->getCurrentUserId()));
                $config  = $this->model->create($payload);
                $this->configService->clearCache();

                dp_log_user_action('新增动态配置', [
                    'config' => $this->buildLogData($config->toArray()),
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '新增动态配置', [
                    'submitted_data' => [
                        'app'   => $data['app'] ?? '',
                        'group' => $data['group'] ?? '',
                        'title' => $data['title'] ?? '',
                        'key'   => $data['key'] ?? '',
                        'type'  => $data['type'] ?? '',
                    ],
                ]);
                $this->error($e->getMessage());
            }

            $this->success('新增成功', '', 'reload-table');
        }

        $this->buildForm(null, $currentApp);
        $this->form->data([
            'app' => (string)$currentApp['name'],
        ]);
        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 编辑配置定义
     * @param int $id
     * @return string|Json
     * @throws Throwable
     */
    public function edit(int $id): string|Json
    {
        $config = $this->model->find($id);
        if (!$config) {
            $this->error('配置项不存在');
        }

        $currentApp = $this->getAppContextByName((string)$config->getAttr('app'));
        if (!$currentApp) {
            $this->error('当前应用未被授权在 Config 中管理动态配置');
        }

        if ($this->request->isPost()) {
            $data              = $this->request->post('', null, 'trim');
            $data['id']        = $id;
            $data['app']       = $data['app'] ?? (string)$config->getAttr('app');
            $data['status']    = $data['status'] ?? 0;
            $data['is_system'] = $data['is_system'] ?? 0;

            $this->autoValidate('Config.edit', $data);

            try {
                $before  = $this->buildLogData($config->toArray());
                $payload = $this->configService->preparePersistData(
                    $data,
                    $config,
                    dp_is_super_admin($this->getCurrentUserId())
                );

                $config->save($payload);
                $this->configService->clearCache();

                dp_log_user_action('编辑动态配置', [
                    'id'     => $id,
                    'before' => $before,
                    'after'  => $this->buildLogData($config->refresh()->toArray()),
                ]);
            } catch (Exception $e) {
                dp_log_exception($e, '编辑动态配置', [
                    'id'   => $id,
                    'app'  => $data['app'] ?? '',
                    'key'  => $config->getAttr('key'),
                    'type' => $config->getAttr('type'),
                ]);
                $this->error($e->getMessage());
            }

            $this->success('编辑成功', '', 'reload-table');
        }

        $this->buildForm($config, $currentApp);
        $this->form->data($this->configService->prepareEditorData($config));
        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 删除前校验
     * @param array $ids
     * @return bool
     */
    protected function beforeDelete(array $ids): bool
    {
        $ids     = array_values(array_unique(array_map('intval', $ids)));
        $records = $this->model->whereIn('id', $ids)->select();

        if ($records->isEmpty()) {
            $this->error('未找到可删除的配置项');
        }

        if ($records->count() !== count($ids)) {
            $this->error('存在不存在的配置项');
        }

        foreach ($records as $record) {
            if (!$this->appService->canUseDynamicConfig((string)$record->getAttr('app'))) {
                $this->error('当前应用未被授权在 Config 中管理动态配置');
            }

            if ((int)$record->getAttr('is_system') === 1) {
                $this->error('系统内置配置项不允许删除');
            }
        }

        $this->deleteSnapshots = $records->toArray();
        return true;
    }

    /**
     * 删除后日志
     * @param array $ids
     * @param mixed $count
     * @return void
     * @throws Throwable
     */
    protected function afterDelete(array $ids, mixed $count): void
    {
        if (false === $count) {
            dp_log_user_action('删除动态配置失败', [
                'target_ids' => $ids,
            ], 'error');
            return;
        }

        dp_log_user_action('删除动态配置', [
            'target_ids' => $ids,
            'configs'    => array_map(fn(array $item): array => $this->buildLogData($item), $this->deleteSnapshots),
        ]);
    }

    /**
     * 启用前校验
     * @param array $ids
     * @return bool
     */
    protected function beforeEnable(array $ids): bool
    {
        return $this->assertIdsCanManage($ids);
    }

    /**
     * 禁用前校验
     * @param array $ids
     * @return bool
     */
    protected function beforeDisable(array $ids): bool
    {
        return $this->assertIdsCanManage($ids);
    }

    /**
     * 启用后日志
     * @param array $ids
     * @param int|false $count
     * @return void
     * @throws Throwable
     */
    protected function afterEnable(array $ids, int|false $count): void
    {
        if ($count > 0) {
            dp_log_user_action('批量启用动态配置', [
                'target_ids' => $ids,
                'affected'   => $count,
            ]);
        }
    }

    /**
     * 禁用后日志
     * @param array $ids
     * @param int|false $count
     * @return void
     * @throws Throwable
     */
    protected function afterDisable(array $ids, int|false $count): void
    {
        if ($count > 0) {
            dp_log_user_action('批量禁用动态配置', [
                'target_ids' => $ids,
                'affected'   => $count,
            ]);
        }
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
        $record = $this->model->find($id);
        if (!$record) {
            $this->error('配置项不存在');
        }

        if (!$this->appService->canUseDynamicConfig((string)$record->getAttr('app'))) {
            $this->error('当前应用未被授权在 Config 中管理动态配置');
        }

        if ($field === 'sort' && !is_numeric($value)) {
            $this->error('排序必须为数字');
        }

        if ($field === 'status' && !in_array((string)$value, ['0', '1'], true)) {
            $this->error('状态值不正确');
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
     * @return void
     * @throws Throwable
     */
    protected function afterQuickEdit(int $id, string $field, mixed $value, mixed $oldValue, mixed $result): void
    {
        if (false === $result) {
            return;
        }

        dp_log_user_action('快速编辑动态配置', [
            'id'        => $id,
            'field'     => $field,
            'old_value' => $oldValue,
            'new_value' => $value,
        ]);
    }

    /**
     * 清理配置缓存
     * @return void
     */
    protected function clearCache(): void
    {
        $this->configService->clearCache();
    }

    /**
     * 构建表单
     * @param ConfigModel|null $config
     * @param array|null $app
     * @return void
     * @throws Exception
     */
    private function buildForm(?ConfigModel $config = null, ?array $app = null): void
    {
        $isEdit          = $config !== null;
        $isSuperAdmin    = dp_is_super_admin($this->getCurrentUserId());
        $typeDescription = '支持的类型仅限白名单内组件，System 页面会按该类型动态渲染。';
        $app             = $app ?? $this->getCurrentAppContext();
        $editorTypeMap   = $this->configService->getOptionEditorTypeMap();

        $baseItems     = [
            [
                'type'        => 'text',
                'name'        => 'group',
                'label'       => '配置分组',
                'required'    => true,
                'tips'        => '应用内分组，用于 System 页面在当前应用下分组展示，例如：站点设置、上传设置。',
                'placeholder' => '请输入配置分组',
            ],
            [
                'type'        => 'text',
                'name'        => 'title',
                'label'       => '配置名称',
                'required'    => true,
                'placeholder' => '请输入配置名称',
            ],
            [
                'type'        => 'text',
                'name'        => 'key',
                'label'       => '配置键名',
                'required'    => true,
                'tips'        => '建议使用点分命名，例如：site.name、upload.image_quality。',
                'placeholder' => '请输入唯一配置键',
            ],
            [
                'type'        => 'select',
                'name'        => 'type',
                'label'       => '配置类型',
                'required'    => true,
                'options'     => $this->configService->getTypeOptions(),
                'tips'        => $typeDescription,
                'placeholder' => '请选择配置类型',
            ],
            [
                'type'  => 'textarea',
                'name'  => 'remark',
                'label' => '备注',
                'tips'  => '用于说明配置项用途，会在 System 页面作为兜底提示展示。',
            ],
            [
                'type'  => 'number',
                'name'  => 'sort',
                'label' => '排序',
                'value' => 0,
                'tips'  => '数字越小越靠前。',
            ],
            [
                'type'  => 'switch',
                'name'  => 'status',
                'label' => '状态',
                'value' => 1,
                'tips'  => '关闭后不会在 System 页面展示。',
            ],
        ];
        $renderItems   = [
            [
                'type'        => 'text',
                'name'        => 'placeholder',
                'label'       => '占位提示',
                'tips'        => '适用于文本输入、选择类等支持 placeholder 的组件。',
                'placeholder' => '请输入 placeholder 文案',
            ],
            [
                'type'  => 'textarea',
                'name'  => 'tips',
                'label' => '字段提示',
                'tips'  => '用于 System 页面字段右侧提示，留空时会回退使用“备注”。',
            ],
            [
                'type'        => 'textarea',
                'name'        => 'option_lines',
                'label'       => '选项数据',
                'tips'        => '每行一项，格式为“键:值”；若不写冒号，则键和值相同。',
                'placeholder' => "例如：\nopen:开启\nclose:关闭",
                'when'        => $this->buildTypeWhen($editorTypeMap['option_lines']),
            ],
            [
                'type'  => 'switch',
                'name'  => 'multiple',
                'label' => '允许多选',
                'value' => 0,
                'tips'  => '仅适用于下拉菜单和增强下拉，用于控制是否支持多选。',
                'when'  => $this->buildTypeWhen($editorTypeMap['multiple']),
            ],
            [
                'type'        => 'select',
                'name'        => 'driver',
                'label'       => '上传驱动',
                'options'     => $this->configService->getUploadDriverOptions(),
                'value'       => (string)dp_setting('upload.default_driver', config('upload.default', 'local')),
                'placeholder' => '请选择上传驱动',
                'tips'        => '仅图片或文件上传类配置需要设置。未显式配置时，上传类表单项会优先回退到“默认上传驱动”。',
                'when'        => $this->buildTypeWhen($editorTypeMap['driver']),
            ],
            [
                'type'        => 'text',
                'name'        => 'dir',
                'label'       => '上传目录',
                'tips'        => '仅图片或文件上传类配置需要设置，填写相对目录，例如：banner 或 docs/report。',
                'placeholder' => '请输入上传目录',
                'when'        => $this->buildTypeWhen($editorTypeMap['dir']),
            ],
        ];
        $advancedItems = [
            [
                'type'  => 'textarea',
                'name'  => 'value',
                'label' => '当前值',
                'tips'  => '定义管理页中编辑的是原始存储值，System 页面会按类型进行友好编辑。',
            ],
            [
                'type'  => 'textarea',
                'name'  => 'default_value',
                'label' => '默认值',
                'tips'  => '当前值为空时会回退到默认值。数组值建议填写 JSON 或逗号分隔字符串。',
            ],
            [
                'type'  => 'textarea',
                'name'  => 'extra_options',
                'label' => '高级参数',
                'tips'  => '仅在当前结构化字段无法覆盖时再填写 JSON。',
            ],
            [
                'type'  => 'textarea',
                'name'  => 'rules',
                'label' => '校验规则',
                'tips'  => '支持 ThinkPHP 规则字符串，也支持 JSON 数组。多行文本会自动按 | 合并。',
            ],
        ];

        if ($isSuperAdmin) {
            $baseItems[] = [
                'type'  => 'switch',
                'name'  => 'is_system',
                'label' => '系统内置',
                'value' => 0,
                'tips'  => '系统内置配置项受删除保护，仅建议用于核心配置定义。',
            ];
        } elseif ($isEdit) {
            $baseItems[] = [
                'type'  => 'html',
                'name'  => '__system_notice__',
                'label' => '保护说明',
                'value' => '<div class="alert alert-warning mb-0">当前账号不是超级管理员，不能修改“系统内置”标记。</div>',
            ];
        }

        $this->form
            ->header(false)
            ->items([
                [
                    'type'  => 'hidden',
                    'name'  => 'app',
                    'value' => (string)($app['name'] ?? ConfigService::DEFAULT_APP),
                ],
                [
                    'type'  => 'html',
                    'name'  => '__app_display__',
                    'label' => '所属应用',
                    'value' => sprintf(
                        '<div class="form-control-plaintext">%s <span class="text-muted ms-2">(%s)</span></div>',
                        htmlspecialchars((string)($app['title'] ?? $app['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string)($app['name'] ?? ''), ENT_QUOTES, 'UTF-8')
                    ),
                ],
                [
                    'type'    => 'fieldset',
                    'name'    => '__section_base__',
                    'label'   => '配置定义',
                    'tips'    => '维护配置项的归属、命名、类型和基础状态。',
                    'options' => $baseItems,
                ],
                [
                    'type'    => 'fieldset',
                    'name'    => '__section_render__',
                    'label'   => '渲染设置',
                    'tips'    => '这些字段会按配置类型动态显隐，并最终统一写入 options。',
                    'options' => $renderItems,
                ],
                [
                    'type'    => 'fieldset',
                    'name'    => '__section_advanced__',
                    'label'   => '高级设置',
                    'tips'    => '用于维护原始值、默认值、校验规则以及少量特殊扩展参数。',
                    'options' => $advancedItems,
                ],
            ]);
    }

    /**
     * 构建类型联动规则
     * @param array $types
     * @return array
     */
    private function buildTypeWhen(array $types): array
    {
        return [
            'field' => 'type',
            'op'    => 'in',
            'value' => array_values($types),
            'then'  => ['show'],
            'else'  => ['hide'],
        ];
    }

    /**
     * 构建日志数据
     * @param array $record
     * @return array
     */
    private function buildLogData(array $record): array
    {
        return [
            'id'            => (int)($record['id'] ?? 0),
            'app'           => (string)($record['app'] ?? ConfigService::DEFAULT_APP),
            'group'         => (string)($record['group'] ?? ''),
            'title'         => (string)($record['title'] ?? ''),
            'key'           => (string)($record['key'] ?? ''),
            'type'          => (string)($record['type'] ?? ''),
            'status'        => (int)($record['status'] ?? 0),
            'is_system'     => (int)($record['is_system'] ?? 0),
            'default_value' => $this->configService->summarizeStoredValue(
                $record['default_value'] ?? '',
                (string)($record['type'] ?? ''),
                $record
            ),
            'current_value' => $this->configService->summarizeStoredValue(
                $record['value'] ?? '',
                (string)($record['type'] ?? ''),
                $record
            ),
        ];
    }

    /**
     * 构建应用切换 Tabs
     * @param array $currentApp
     * @param mixed $currentContent
     * @return array
     */
    private function buildAppTabs(array $currentApp, mixed $currentContent = ''): array
    {
        $tabs = [];

        foreach ($this->appService->getConfigApps(true) as $app) {
            $name = (string)($app['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $tabs[$name] = [
                'icon'  => $this->buildAppTabIcon($app),
                'title' => (string)($app['title'] ?? $name),
                'url'   => dp_url('index', ['app' => $name]),
            ];

            if ($name === (string)($currentApp['name'] ?? '')) {
                unset($tabs[$name]['url']);
                $tabs[$name]['content'] = $currentContent;
            }
        }

        return $tabs;
    }

    /**
     * 构建应用 Tab 图标
     * @param array $app
     * @return string
     */
    private function buildAppTabIcon(array $app): string
    {
        $icon = trim((string)($app['icon'] ?? ''));
        if ($icon === '') {
            return '';
        }

        return '<i class="dp-icon ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-1"></i>';
    }

    /**
     * 获取当前应用上下文
     * @return array|null
     */
    private function getCurrentAppContext(): ?array
    {
        return $this->getAppContextByName((string)$this->request->param('app', ''));
    }

    /**
     * 根据应用名解析上下文
     * @param string $appName
     * @return array|null
     */
    private function getAppContextByName(string $appName): ?array
    {
        return $this->appService->resolveConfigApp($appName);
    }

    /**
     * 构建空态
     * @param string $requestedApp
     * @return void
     * @throws Exception
     */
    private function buildEmptyState(string $requestedApp = ''): void
    {
        $message = $requestedApp !== ''
            ? sprintf(
                '应用“%s”未被授权进入 Config。只有已安装、系统内置且已开启“显示到 Config”的应用，才会出现在这里。',
                htmlspecialchars($requestedApp, ENT_QUOTES, 'UTF-8')
            )
            : '当前没有被授权进入 Config 的内置应用，请先前往“应用管理”开启“显示到 Config”开关。';

        $this->form
            ->footer(false)
            ->items([
                [
                    'type'  => 'html',
                    'name'  => '__empty_config_apps__',
                    'label' => '暂无可管理应用',
                    'value' => sprintf('<div class="alert alert-info mb-0">%s</div>', $message),
                ],
            ]);
    }

    /**
     * 校验指定配置是否允许在 Config 中管理
     * @param array $ids
     * @return bool
     */
    private function assertIdsCanManage(array $ids): bool
    {
        $ids     = array_values(array_unique(array_map('intval', $ids)));
        $records = $this->model->whereIn('id', $ids)->select();

        if ($records->isEmpty()) {
            $this->error('未找到可操作的配置项');
        }

        foreach ($records as $record) {
            if (!$this->appService->canUseDynamicConfig((string)$record->getAttr('app'))) {
                $this->error('当前应用未被授权在 Config 中管理动态配置');
            }
        }

        return true;
    }
}
