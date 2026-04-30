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
use app\common\model\Department as DepartmentModel;
use app\common\model\User as UserModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;
use Throwable;
use Exception;

/**
 * 部门管理控制器
 * @package app\admin\controller
 */
#[Permission(name: '部门管理', icon: 'ti ti-building', sort: 40)]
class Department extends Auth
{
    /**
     * 部门模型
     * @var DepartmentModel
     */
    protected DepartmentModel $model;

    /**
     * 允许快速编辑的字段
     * @var array
     */
    protected array $quickEditFields = ['status'];

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model = new DepartmentModel();
    }

    /**
     * 部门列表(树形)
     * @return string|Json
     * @throws Exception
     */
    public function index(): string|Json
    {
        // 获取用户列表数据
        $list_user = UserModel::where('status', 1)->column('nickname', 'id');

        // 配置表格
        $this->table
            ->tree(true)
            ->checkbox('left')
            ->columns([
                ['id', 'ID', '', [], [
                    'maxWidth' => 60
                ]],
                ['name', '部门名称'],
                ['code', '部门编码'],
                ['leader_id', '负责人', $list_user],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'actions', [
                    'edit',
                    'delete',
                    [
                        'title' => '编辑',
                        'url'   => dp_url('edit', ['id' => '__id__']),
                        'pop'   => true,
                        'auth'  => 'system.permission',  // 权限标识
                    ],
                ]]
            ])
            ->toolbar(['add', 'expand', 'collapse', 'delete'])
            ->render();

        // 页面组装
        $this->page->row($this->table);

        return $this->fetch();
    }

    /**
     * 部门列表数据
     * @return array
     */
    protected function data(): array
    {
        // 获取树形数据
        return $this->model->getTree(0, false);
    }

    /**
     * 新增部门
     * @return string|Json
     * @throws Throwable
     */
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 自动验证
            $this->autoValidate();

            try {
                // 保存数据
                $this->model->create($data);
            } catch (Exception) {
                $this->error('新增失败');
            }

            $this->success('新增成功', '', 'reload-table');
        }

        // 获取部门树
        $departmentTree = $this->model->getTree(0, false);
        $departmentTree = dp_tree_to_options($departmentTree, '顶级部门');
        // 获取用户列表
        $userList = UserModel::where('status', 1)->column('nickname', 'id');

        // 构建表单
        $this->form
            ->items([
                ['text:*', 'name', '部门名称', '请输入部门名称'],
                ['text:*', 'code', '部门编码', '部门唯一编码,如: TECH_DEPT'],
                ['select2', 'parent_id', '上级部门', '选择上级部门,不选择则为顶级部门', '', $departmentTree],
                ['select2', 'leader_id', '部门负责人', '选择部门负责人', '', $userList],
                ['text', 'sort', '排序', '数字越小越靠前，同级部门按此字段排序', '0'],
                ['switch', 'status', '状态', '是否启用', '1'],
                ['textarea', 'remark', '备注', '部门备注信息'],
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 编辑部门
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取部门信息
        $department = $this->model->find($id);
        if (!$department) {
            $this->error('部门不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 检查是否将自己设为上级部门
            if (isset($data['parent_id']) && $data['parent_id'] == $id) {
                $this->error('不能将自己设为上级部门');
            }

            // 检查是否将子部门设为上级部门
            $childrenIds = $this->model->getChildrenIds($id);
            if (isset($data['parent_id']) && in_array($data['parent_id'], $childrenIds)) {
                $this->error('不能将子部门设为上级部门');
            }

            // 自动验证
            $this->autoValidate();

            try {
                // 处理switch类型
                $data['status'] = $data['status'] ?? 0;
                // 保存数据
                $department->save($data);
            } catch (Exception) {
                $this->error('编辑失败');
            }

            $this->success('编辑成功', '', 'reload-table');
        }

        // 获取部门树(用于选择上级部门,排除自己和子部门)
        $departmentTree = $this->model->getTree(0, false);
        $childrenIds    = $this->model->getChildrenIds($id, true);
        $departmentTree = dp_tree_to_options($departmentTree, '顶级部门', $childrenIds);

        // 获取用户列表
        $userList = UserModel::where('status', 1)->column('nickname', 'id');

        // 构建表单
        $this->form
            ->data($department->toArray())
            ->items([
                ['text:*', 'name', '部门名称', '请输入部门名称'],
                ['text:*', 'code', '部门编码', '部门唯一编码,如: TECH_DEPT'],
                ['select2', 'parent_id', '上级部门', '选择上级部门,不选择则为顶级部门', '', $departmentTree],
                ['select2', 'leader_id', '部门负责人', '选择部门负责人', '', $userList],
                ['text', 'sort', '排序', '数字越小越靠前，同级部门按此字段排序'],
                ['switch', 'status', '状态', '是否启用'],
                ['textarea', 'remark', '备注', '部门备注信息'],
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 删除部门前的钩子 - 扩展删除列表，包含所有子孙部门
     * @param array $ids 要删除的部门ID数组
     * @return bool 返回 false 阻止删除
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function beforeDelete(array &$ids): bool
    {
        $allIds = [];

        // 遍历每个要删除的部门
        foreach ($ids as $id) {
            // 获取该部门及其所有子孙部门的 ID
            $childrenIds = $this->model->getChildrenIds($id, true);

            // 检查这些部门下是否有用户
            $userCount = (new UserModel())
                ->whereIn('department_id', $childrenIds)
                ->where('delete_time', null)
                ->count();

            if ($userCount > 0) {
                $department     = $this->model->find($id);
                $departmentName = $department ? $department['name'] : "ID:$id";
                $this->error("部门「{$departmentName}」或其子部门下存在 $userCount 个用户，无法删除");
            }

            // 合并到总删除列表
            $allIds = array_merge($allIds, $childrenIds);
        }

        // 去重并更新 $ids
        $ids = array_values(array_unique($allIds));

        return true;
    }

    /**
     * 删除部门后的钩子 - 记录日志
     * @param array $ids 删除的部门ID数组
     * @param int|bool $count 实际删除的记录数，失败时为false
     * @return void
     * @throws Throwable
     */
    protected function afterDelete(array $ids, int|bool $count): void
    {
        if (false === $count) {
            // 操作失败，记录错误日志
            dp_log_user_action('删除部门失败', [
                'target_ids'      => $ids,
                'total_requested' => count($ids),
                'error'           => '数据库操作失败'
            ], 'error');
        } else {
            // 操作成功且有记录被删除
            dp_log_user_action('删除部门成功', [
                'target_ids'      => $ids,
                'total_requested' => count($ids)
            ], 'warning');
        }
    }

    /**
     * 快速编辑后的钩子 - 记录日志
     * @param int $id 部门ID
     * @param string $field 编辑的字段
     * @param mixed $value 新值
     * @param mixed $oldValue 旧值
     * @param int|bool $result 更新结果
     * @return void
     * @throws Throwable
     */
    protected function afterQuickEdit(int $id, string $field, mixed $value, mixed $oldValue, int|bool $result): void
    {
        if (false !== $result) {
            // 获取部门名称
            $department     = $this->model->find($id);
            $departmentName = $department ? $department['name'] : "ID:$id";

            // 记录操作日志
            dp_log_user_action('快速编辑部门', [
                'id'          => $id,
                'name'        => $departmentName,
                'field'       => $field,
                'value'       => $value,
                'oldValue'    => $oldValue,
                'description' => "修改部门「{$departmentName}」的{$field}为：$value"
            ]);
        }
    }

    /**
     * 获取部门树API
     * @return Json
     */
    public function getTree(): Json
    {
        $onlyEnabled = $this->request->param('only_enabled/d', 1);
        $tree        = $this->model->getTree(0, (bool)$onlyEnabled);

        return json(['code' => 1, 'data' => $tree]);
    }

    /**
     * 获取用户列表(用于 select2 ajax)
     * @return Json
     */
    public function getUserList(): Json
    {
        $keyword  = $this->request->param('keyword', '');
        $page     = $this->request->param('page/d', 1);
        $pageSize = 15;

        $where = [];
        if ($keyword) {
            $where[] = ['username|nickname', 'like', '%' . $keyword . '%'];
        }

        $list = (new UserModel())
            ->where($where)
            ->where('status', 1)
            ->where('delete_time', null)
            ->page($page, $pageSize)
            ->select();

        $total = (new UserModel())
            ->where($where)
            ->where('status', 1)
            ->where('delete_time', null)
            ->count();

        $data = [];
        foreach ($list as $user) {
            $data[] = [
                'id'   => $user->id,
                'text' => $user->nickname ?: $user->username
            ];
        }

        return json([
            'code'  => 1,
            'data'  => $data,
            'total' => $total
        ]);
    }
}
