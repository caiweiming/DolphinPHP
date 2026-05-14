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

use app\common\model\Permission as PermissionModel;
use app\common\render\form\Field;
use app\common\service\AppService;
use app\common\service\PermissionSyncService;
use app\common\attribute\Permission as PermissionAttribute;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Log;
use think\response\Json;
use Throwable;

/**
 * 权限管理控制器
 * @package app\admin\controller
 */
#[PermissionAttribute('权限管理', icon: 'ti ti-lock', sort: 60)]
class Permission extends Auth
{
    /**
     * 可手工派生的标准按钮权限
     */
    private const STANDARD_BUTTONS = [
        'create'  => ['name' => '新增', 'sort' => 10],
        'edit'    => ['name' => '编辑', 'sort' => 20],
        'delete'  => ['name' => '删除', 'sort' => 30],
        'enable'  => ['name' => '启用', 'sort' => 40],
        'disable' => ['name' => '禁用', 'sort' => 50],
        'sort'    => ['name' => '排序', 'sort' => 60],
        'detail'  => ['name' => '详情', 'sort' => 70],
        'export'  => ['name' => '导出', 'sort' => 80],
        'import'  => ['name' => '导入', 'sort' => 90],
    ];

    /**
     * 权限模型
     * @var PermissionModel
     */
    protected PermissionModel $model;

    /**
     * 应用服务
     * @var AppService
     */
    protected AppService $appService;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['status', 'sort'];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model      = new PermissionModel();
        $this->appService = app(AppService::class);
    }

    /**
     * 权限列表(树形)
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        // 配置表格
        $this->table
            ->tree(true)
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '权限名称 / 权限标识 / 路由',
                    'op'          => 'like',
                    'fields'      => ['name', 'code', 'route'],
                ],
                [
                    'name'        => 'type',
                    'type'        => 'select',
                    'placeholder' => '类型',
                    'options'     => PermissionModel::getTypeList(),
                ],
                [
                    'name'        => 'status',
                    'type'        => 'select',
                    'placeholder' => '状态',
                    'options'     => [1 => '启用', 0 => '禁用'],
                ],
            ])
            ->columns([
                ['id', 'ID', 80],
                ['name', '权限名称'],
                ['code', '权限标识'],
                ['type', '类型', 'status', [
                    PermissionModel::TYPE_MENU   => '菜单:green',
                    PermissionModel::TYPE_BUTTON => '按钮:blue',
                    PermissionModel::TYPE_API    => '接口:orange'
                ]],
                ['route', '路由/接口'],
                ['status', '状态', 'switch'],
                ['sort', '排序', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'actions', [
                    [
                        'title' => '子权限',
                        'url'   => dp_url('create', ['parent_id' => '__id__']),
                        'pop'   => true,
                        'auth'  => 'admin.permission.create'
                    ],
                    'edit',
                    'delete'
                ]]
            ])
            ->toolbar([
                'add',
                'expand',
                'collapse',
                [
                    'title' => '同步权限',
                    'event' => 'sync',
                    'url'   => dp_url('permission/sync'),
                    'icon'  => 'ti ti-key',
                    'pop'   => true,
                    'auth'  => 'admin.permission.sync'  // 明确指定权限标识
                ],
                'delete'
            ])
            ->render();

        // 页面组装
        $this->page->row($this->table);

        return $this->fetch();
    }

    /**
     * 权限列表数据
     * @return array
     */
    protected function data(): array
    {
        $tree    = $this->model->getTree(0, null, false);
        $search  = $this->getSearchData(['keyword', 'type', 'status']);
        $keyword = trim((string)($search['keyword'] ?? ''));
        $type    = trim((string)($search['type'] ?? ''));
        $status  = trim((string)($search['status'] ?? ''));

        if ($keyword === '' && $type === '' && $status === '') {
            return $tree;
        }

        if ($status !== '') {
            $tree = $this->filterPermissionTree($tree, static function (array $node) use ($status): bool {
                return (string)($node['status'] ?? '') === $status;
            });
        }

        if ($type !== '') {
            $tree = $this->filterPermissionTree($tree, static function (array $node) use ($type): bool {
                return (string)($node['type'] ?? '') === $type;
            });
        }

        if ($keyword === '') {
            return $tree;
        }

        return $this->filterPermissionTree($tree, function (array $node) use ($keyword): bool {
            return $this->permissionNodeMatchesKeyword($node, $keyword);
        });
    }

    /**
     * 通用权限树过滤器
     * @param array $tree
     * @param callable $matcher
     * @return array
     */
    private function filterPermissionTree(array $tree, callable $matcher): array
    {
        $filtered = [];

        foreach ($tree as $node) {
            if (!is_array($node)) {
                continue;
            }

            $children = [];
            if (!empty($node['children']) && is_array($node['children'])) {
                $children = $this->filterPermissionTree($node['children'], $matcher);
            }

            if ($matcher($node) || $children !== []) {
                $node['children'] = $children;
                $filtered[]       = $node;
            }
        }

        return $filtered;
    }

    /**
     * 判断权限节点是否命中关键字
     * @param array $node
     * @param string $keyword
     * @return bool
     */
    private function permissionNodeMatchesKeyword(array $node, string $keyword): bool
    {
        $haystacks = [
            (string)($node['name'] ?? ''),
            (string)($node['code'] ?? ''),
            (string)($node['route'] ?? ''),
        ];

        foreach ($haystacks as $haystack) {
            if ($haystack !== '' && mb_stripos($haystack, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 新增权限
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data                  = $this->request->post('', null, 'trim');
            $standardButtonActions = $this->extractStandardButtonActions($data);

            // 移除时间字段，让模型自动处理
            unset($data['create_time'], $data['update_time'], $data['delete_time'], $data['standard_buttons']);

            // 处理switch类型
            $data['status'] = $data['status'] ?? 0;

            // 自动验证
            $this->autoValidate();

            try {
                Db::transaction(function () use ($data, $standardButtonActions): void {
                    $permission = $this->model->create($data);
                    $this->syncSelectedStandardButtons($permission, $standardButtonActions);
                });
                $this->clearCache();
            } catch (Throwable $e) {
                Log::error('新增权限失败: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $this->error('新增失败');
            }

            $this->success('新增成功', '', 'reload-table');
        }

        // 获取权限树(用于选择上级权限)
        $permissionTree = $this->model->getTree(0, null, false);
        $parentId       = $this->request->param('parent_id/d', 0);
        if ($parentId > 0 && !$this->model->find($parentId)) {
            $parentId = 0;
        }

        // 构建表单
        $this->form
            ->items($this->buildPermissionFormItems(
                permissionTree: $permissionTree,
                parentId: $parentId
            ));

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 编辑权限
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取权限信息
        $permission = $this->model->find($id);
        if (!$permission) {
            $this->error('权限不存在');
        }

        if ($this->request->isPost()) {
            $data                  = $this->request->post('', null, 'trim');
            $standardButtonActions = $this->extractStandardButtonActions($data);
            $originalCode          = (string)$permission->getAttr('code');

            // 移除时间字段，让模型自动处理
            unset($data['create_time'], $data['update_time'], $data['delete_time'], $data['standard_buttons']);

            // 检查是否将自己设为上级权限
            if (isset($data['parent_id']) && $data['parent_id'] == $id) {
                $this->error('不能将自己设为上级权限');
            }

            // 检查是否将子权限设为上级权限
            $childrenIds = $this->model->getChildrenIds($id);
            if (isset($data['parent_id']) && in_array($data['parent_id'], $childrenIds)) {
                $this->error('不能将子权限设为上级权限');
            }

            // 自动验证
            $this->autoValidate();

            try {
                // 处理switch类型
                $data['status'] = $data['status'] ?? 0;
                Db::transaction(function () use ($permission, $data, $originalCode, $standardButtonActions): void {
                    $permission->save($data);
                    $this->syncSelectedStandardButtons($permission, $standardButtonActions);
                    $this->syncAppRegistryIconFromPermission($permission, $data, $originalCode);
                });
                $this->clearCache($id);
            } catch (Throwable $e) {
                Log::error('编辑权限失败: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'id' => $id]);
                $this->error('编辑失败');
            }

            $this->success('编辑成功', '', 'reload-table');
        }

        // 获取权限树(用于选择上级权限,排除自己和子权限)
        $permissionTree = $this->model->getTree(0, null, false);
        $childrenIds    = $this->model->getChildrenIds($id, true);

        // 构建表单
        $this->form
            ->data(array_merge($permission->toArray(), [
                'standard_buttons' => $this->getExistingStandardButtonActions($permission),
            ]))
            ->items($this->buildPermissionFormItems(
                permissionTree: $permissionTree,
                parentId: (int)$permission->getAttr('parent_id'),
                excludeIds: $childrenIds,
                selectedStandardButtons: $this->getExistingStandardButtonActions($permission)
            ));

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 删除权限
     * @return Json
     */
    public function delete(): Json
    {
        $ids         = $this->resolveDeleteIds();
        $permissions = $this->loadDeletePermissions($ids);

        try {
            $deletedCount = Db::transaction(function () use ($permissions): int {
                $count = 0;
                foreach ($permissions as $permission) {
                    if ($permission->delete() === false) {
                        throw new Exception('删除失败');
                    }

                    $count++;
                }

                return $count;
            });

            $this->clearCache();
        } catch (Throwable $e) {
            Log::error('删除权限失败: ' . $e->getMessage(), ['ids' => $ids, 'trace' => $e->getTraceAsString()]);
            $this->error('删除失败');
        }

        $message = count($permissions) > 1
            ? sprintf('成功删除 %d 个权限', $deletedCount)
            : '删除成功';

        $this->success($message, '', 'reload-table');
    }

    /**
     * 解析删除请求中的权限ID
     * @return array<int>
     */
    private function resolveDeleteIds(): array
    {
        $ids = $this->parseDeleteIdsParam($this->request->param('ids', []));

        if ($ids === []) {
            $id = $this->request->param('id/d', 0);
            if ($id > 0) {
                $ids = [$id];
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            $this->error('参数错误');
        }

        return $ids;
    }

    /**
     * 解析批量删除参数
     * @param mixed $idsParam
     * @return array
     */
    private function parseDeleteIdsParam(mixed $idsParam): array
    {
        if (is_array($idsParam)) {
            return $idsParam;
        }

        if (!is_string($idsParam) || trim($idsParam) === '') {
            return [];
        }

        $raw = trim($idsParam);
        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (str_contains($raw, ',')) {
            return array_map('trim', explode(',', $raw));
        }

        return [$raw];
    }

    /**
     * 加载并校验待删除的权限集合
     * @param array<int> $ids
     * @return array<int, PermissionModel>
     */
    private function loadDeletePermissions(array $ids): array
    {
        $records = $this->model
            ->whereIn('id', $ids)
            ->select()
            ->all();

        $permissionMap = [];
        foreach ($records as $permission) {
            $permissionMap[(int)$permission->id] = $permission;
        }

        $permissions = [];
        foreach ($ids as $id) {
            if (isset($permissionMap[$id])) {
                $permissions[] = $permissionMap[$id];
            }
        }

        $foundIds = array_map(static fn(PermissionModel $permission): int => (int)$permission->id, $permissions);
        $missing  = array_values(array_diff($ids, $foundIds));

        if ($missing !== []) {
            $this->error(count($missing) === 1 ? '权限不存在' : '存在不存在的权限');
        }

        $this->validatePermissionsCanDelete($permissions);

        return $permissions;
    }

    /**
     * 校验权限集合是否允许删除
     * @param array<int, PermissionModel> $permissions
     * @return void
     */
    private function validatePermissionsCanDelete(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $permissionId   = (int)$permission->id;
            $permissionName = (string)$permission->getAttr('name');

            if ($this->model->hasChildren($permissionId)) {
                $message = count($permissions) > 1
                    ? sprintf('权限「%s」下存在子权限,无法批量删除', $permissionName)
                    : '该权限下存在子权限,无法删除';
                $this->error($message);
            }

            if ($this->model->hasRoles($permissionId)) {
                $message = count($permissions) > 1
                    ? sprintf('权限「%s」已分配给角色,无法批量删除', $permissionName)
                    : '该权限已分配给角色,无法删除';
                $this->error($message);
            }
        }
    }

    /**
     * 获取权限树API
     * @return Json
     */
    public function getTree(): Json
    {
        $onlyEnabled = $this->request->param('only_enabled/d', 1);
        $type        = $this->request->param('type', '');

        // 获取权限树
        $tree = $this->model->getTree(0, $type ?: null, (bool)$onlyEnabled);

        return json(['code' => 1, 'data' => $tree]);
    }

    /**
     * 同步权限
     * @return string|void
     * @throws Throwable
     */
    #[PermissionAttribute('同步权限', icon: 'ti ti-key')]
    public function sync()
    {
        // 超级管理员权限检查
        if (!dp_is_super_admin($this->getCurrentUserId())) {
            $this->error('此功能仅限超级管理员使用');
        }

        if ($this->request->isPost()) {
            // 获取参数
            $app         = $this->request->post('app', 'admin');
            $incremental = $this->request->post('incremental', 0);

            // 获取可用应用列表
            $availableApps = array_keys($this->getAvailableApps());

            // 参数验证
            if (!in_array($app, $availableApps)) {
                $this->error('无效的应用名称');
            }

            try {
                // 调用同步服务
                $syncService = app(PermissionSyncService::class);
                $result      = $syncService->sync($app, [
                    'incremental' => (bool)$incremental,
                    'dryRun'      => false
                ]);
            } catch (Exception $e) {
                // 记录错误日志
                dp_log_user_action('同步权限失败', [
                    'app'   => $app,
                    'error' => $e->getMessage()
                ], 'error');

                $this->error('同步失败');
            }

            // 记录操作日志
            dp_log_user_action('同步权限', [
                'app'         => $app,
                'incremental' => $incremental,
                'added'       => $result['added'],
                'skipped'     => $result['skipped'],
                'failed'      => $result['failed'],
                'details'     => $result['details'],
            ]);

            $this->clearCache();

            $this->modalSuccess('同步完成', sprintf(
                '新增 %d 个权限，跳过 %d 个权限，失败 %d 个权限<br>如需完全重建权限，请先手动清空权限表。',
                $result['added'],
                $result['skipped'],
                $result['failed']
            ));
        } else {
            // 构建表单
            $this->form
                ->confirm(text: '确定要同步权限吗？请谨慎操作！')
                ->items([
                    [
                        'type'     => 'select2',
                        'name'     => 'app',
                        'label'    => '选择应用',
                        'tips'     => '选择要同步权限的应用，选择"全部应用"将扫描所有应用',
                        'options'  => $this->getAvailableApps(),
                        'value'    => 'admin',
                        'required' => true
                    ],
                    [
                        'type'  => 'switch',
                        'name'  => 'incremental',
                        'label' => '增量更新',
                        'value' => 1,
                        'tips'  => '开启：智能跳过已存在的权限，只添加新权限（推荐）<br>关闭：尝试添加所有权限，但已存在的会因唯一约束失败（不推荐，除非你清空了权限表）'
                    ]
                ])
                ->alert([
                    '此操作将扫描控制器并自动生成权限数据，请谨慎操作。',
                    '建议先选择单个应用进行测试，确认无误后再同步全部应用。'
                ], type: 'info:icon');

            // 页面组装
            $this->page->row($this->form);

            return $this->fetch();
        }
    }

    /**
     * 获取可用的应用列表
     * @return array
     */
    private function getAvailableApps(): array
    {
        $apps    = ['all' => '全部应用'];
        $appPath = app()->getBasePath();

        // 扫描 app 目录
        $dirs = scandir($appPath);
        foreach ($dirs as $dir) {
            // 跳过特殊目录
            if ($dir === '.' || $dir === '..' || $dir === 'common') {
                continue;
            }

            $fullPath = $appPath . $dir;

            // 检查是否是目录
            if (!is_dir($fullPath)) {
                continue;
            }

            // 检查是否存在 app.php 配置文件
            $configFile = $fullPath . '/app.php';
            if (!file_exists($configFile)) {
                continue;
            }

            // 读取应用配置
            try {
                $config = include $configFile;
                if (is_array($config) && isset($config['name'])) {
                    $apps[$dir] = $config['name'] . " ($dir)";
                } else {
                    // 如果配置文件不包含 name，使用目录名
                    $apps[$dir] = ucfirst($dir) . " ($dir)";
                }
            } catch (Exception $e) {
                // 配置文件加载失败，跳过
                continue;
            }
        }

        return $apps;
    }

    /**
     * 构建权限表单项
     * @param array $permissionTree
     * @param int $parentId
     * @param array $excludeIds
     * @param array $selectedStandardButtons
     * @return array
     */
    private function buildPermissionFormItems(
        array $permissionTree,
        int   $parentId = 0,
        array $excludeIds = [],
        array $selectedStandardButtons = []
    ): array
    {
        return [
            ['text:*', 'name', '权限名称', '请输入权限名称'],
            ['text:*', 'code', '权限标识', '权限唯一标识,如: admin.user.create'],
            [
                'type'     => 'radio',
                'inline'   => true,
                'name'     => 'type',
                'label'    => '权限类型',
                'tips'     => '选择权限类型',
                'options'  => [
                    PermissionModel::TYPE_MENU   => '菜单',
                    PermissionModel::TYPE_BUTTON => '按钮',
                    PermissionModel::TYPE_API    => '接口',
                ],
                'value'    => PermissionModel::TYPE_MENU,
                'required' => true
            ],
            [
                'type'    => 'select2',
                'name'    => 'parent_id',
                'label'   => '上级权限',
                'tips'    => '选择上级权限,不选择则为顶级权限',
                'options' => $this->buildPermissionOptions($permissionTree, $excludeIds),
                'value'   => $parentId,
            ],
            ['text', 'route', '路由/接口', '菜单路由或API接口路径,如: admin/user/index 或 /api/v1/user'],
            Field::checkbox('standard_buttons', '标准按钮权限', '仅对菜单权限生效；系统只会补建所勾选的标准按钮权限，后续权限同步不会再自动补齐继承动作。')
                ->options($this->getStandardButtonOptions())
                ->inline()
                ->value($selectedStandardButtons)
                ->whenIn('type', [PermissionModel::TYPE_MENU], ['show'], ['hide', 'clear']),
            ['icon', 'icon', '图标', '菜单图标class,如: ti ti-user'],
            ['text', 'sort', '排序', '数字越小越靠前', 0],
            ['switch', 'status', '状态', '是否启用', 1],
            ['textarea', 'remark', '备注', '权限备注信息']
        ];
    }

    /**
     * 获取标准按钮权限选项
     * @return array
     */
    private function getStandardButtonOptions(): array
    {
        $options = [];
        foreach (self::STANDARD_BUTTONS as $action => $definition) {
            $options[$action] = $definition['name'];
        }

        return $options;
    }

    /**
     * 构建权限选项(用于 select2)
     * @param array $tree 权限树
     * @param array $excludeIds 排除的ID列表
     * @param int $level 层级
     * @return array
     */
    private function buildPermissionOptions(array $tree, array $excludeIds = [], int $level = 0): array
    {
        $options = [0 => '顶级权限'];

        foreach ($tree as $item) {
            // 排除指定的权限
            if (in_array($item['id'], $excludeIds)) {
                continue;
            }

            $prefix    = str_repeat('　', $level);
            $typeLabel = '';
            switch ($item['type']) {
                case PermissionModel::TYPE_MENU:
                    $typeLabel = '[菜单]';
                    break;
                case PermissionModel::TYPE_BUTTON:
                    $typeLabel = '[按钮]';
                    break;
                case PermissionModel::TYPE_API:
                    $typeLabel = '[接口]';
                    break;
            }

            $options[$item['id']] = $prefix . ($level > 0 ? '├─ ' : '') . $typeLabel . ' ' . $item['name'];

            if (!empty($item['children'])) {
                $children = $this->buildPermissionOptions($item['children'], $excludeIds, $level + 1);
                // 移除第一个元素(顶级权限)
                unset($children[0]);
                $options = $options + $children;
            }
        }

        return $options;
    }

    /**
     * 提取标准按钮权限勾选值
     * @param array $data
     * @return array
     */
    private function extractStandardButtonActions(array $data): array
    {
        if (($data['type'] ?? PermissionModel::TYPE_MENU) !== PermissionModel::TYPE_MENU) {
            return [];
        }

        $selected = $data['standard_buttons'] ?? [];
        if (!is_array($selected)) {
            $selected = $selected === '' ? [] : [$selected];
        }

        $actions = [];
        foreach ($selected as $action) {
            $action = strtolower(trim((string)$action));
            if ($action === '' || !isset(self::STANDARD_BUTTONS[$action])) {
                continue;
            }

            $actions[$action] = $action;
        }

        return array_values($actions);
    }

    /**
     * 获取菜单权限下已存在的标准按钮动作
     * @param PermissionModel $permission
     * @return array
     */
    private function getExistingStandardButtonActions(PermissionModel $permission): array
    {
        if ((string)$permission->getAttr('type') !== PermissionModel::TYPE_MENU) {
            return [];
        }

        $menuCode = trim((string)$permission->getAttr('code'));
        if ($menuCode === '') {
            return [];
        }

        $codes   = $this->model
            ->where('parent_id', (int)$permission->getAttr('id'))
            ->where('type', PermissionModel::TYPE_BUTTON)
            ->column('code');
        $actions = [];

        foreach ($codes as $code) {
            $code = trim((string)$code);
            if (!str_starts_with($code, $menuCode . '.')) {
                continue;
            }

            $action = substr($code, strlen($menuCode) + 1);
            if (isset(self::STANDARD_BUTTONS[$action])) {
                $actions[$action] = $action;
            }
        }

        return array_values($actions);
    }

    /**
     * 同步勾选的标准按钮权限
     * @param PermissionModel $permission
     * @param array $actions
     * @return void
     */
    private function syncSelectedStandardButtons(PermissionModel $permission, array $actions): void
    {
        $payloads = $this->buildStandardButtonPermissionPayloads($permission, $actions);
        if ($payloads === []) {
            return;
        }

        $existingCodes = $this->model
            ->where('parent_id', (int)$permission->getAttr('id'))
            ->where('type', PermissionModel::TYPE_BUTTON)
            ->whereIn('code', array_column($payloads, 'code'))
            ->column('code');
        $existingCodes = array_map('strval', $existingCodes);

        foreach ($payloads as $payload) {
            if (in_array($payload['code'], $existingCodes, true)) {
                continue;
            }

            $this->model->create($payload);
        }
    }

    /**
     * 构建标准按钮权限数据
     * @param PermissionModel $permission
     * @param array $actions
     * @return array
     */
    private function buildStandardButtonPermissionPayloads(PermissionModel $permission, array $actions): array
    {
        if ((string)$permission->getAttr('type') !== PermissionModel::TYPE_MENU) {
            return [];
        }

        $menuCode = trim((string)$permission->getAttr('code'));
        if ($menuCode === '') {
            return [];
        }

        $payloads = [];
        foreach ($actions as $action) {
            if (!isset(self::STANDARD_BUTTONS[$action])) {
                continue;
            }

            $definition = self::STANDARD_BUTTONS[$action];
            $payloads[] = [
                'parent_id' => (int)$permission->getAttr('id'),
                'name'      => $definition['name'],
                'code'      => $menuCode . '.' . $action,
                'type'      => PermissionModel::TYPE_BUTTON,
                'route'     => $this->deriveStandardButtonRoute((string)$permission->getAttr('route'), $action),
                'icon'      => '',
                'sort'      => $definition['sort'],
                'status'    => (int)$permission->getAttr('status'),
                'remark'    => '由菜单权限派生生成',
            ];
        }

        return $payloads;
    }

    /**
     * 由菜单路由推导标准按钮路由
     * @param string $menuRoute
     * @param string $action
     * @return string
     */
    private function deriveStandardButtonRoute(string $menuRoute, string $action): string
    {
        $route = trim(explode('?', $menuRoute, 2)[0], '/');
        if ($route === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $route), static fn($segment) => $segment !== ''));
        if (count($segments) < 2) {
            return '';
        }

        if (count($segments) >= 3) {
            $segments[count($segments) - 1] = $action;
        } else {
            $segments[] = $action;
        }

        return implode('/', $segments);
    }

    /**
     * 清理权限相关缓存
     * @param int $id
     * @return void
     */
    protected function clearCache(int $id = 0): void
    {
        try {
            $this->getPermissionService()->clearAllCache();
        } catch (Throwable $e) {
            Log::error('清理权限缓存失败: ' . $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * 当编辑的是应用顶级菜单时，回写应用注册表图标
     * @param PermissionModel $permission
     * @param array $data
     * @param string $originalCode
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function syncAppRegistryIconFromPermission(PermissionModel $permission, array $data, string $originalCode = ''): void
    {
        if ((int)$permission->getAttr('parent_id') !== 0) {
            return;
        }

        if ((string)$permission->getAttr('type') !== PermissionModel::TYPE_MENU) {
            return;
        }

        if (!array_key_exists('icon', $data)) {
            return;
        }

        $app = $this->appService->resolveAppByMenuCode($originalCode !== '' ? $originalCode : (string)$permission->getAttr('code'));
        if (!$app) {
            return;
        }

        $record = app(\app\common\model\App::class)->where('name', (string)$app['name'])->find();
        if (!$record) {
            return;
        }

        $record->save([
            'icon' => trim((string)($data['icon'] ?? '')),
        ]);

        $this->appService->clearCache((string)$app['name']);
    }
}
