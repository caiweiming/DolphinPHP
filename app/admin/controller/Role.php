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
use app\common\model\Role as RoleModel;
use app\common\model\Permission as PermissionModel;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * 角色管理控制器
 * @package app\admin\controller
 */
#[Permission('角色管理', icon: 'ti ti-user-check', sort: 50)]
class Role extends Auth
{
    /**
     * 角色模型
     * @var RoleModel
     */
    protected RoleModel $model;

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model = new RoleModel();
    }

    /**
     * 角色列表
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|Exception
     */
    public function index(): string|Json
    {
        // 配置表格
        $this->table
            ->columns([
                ['id', 'ID'],
                ['name', '角色名称'],
                ['code', '角色标识'],
                ['data_scope', '数据范围', RoleModel::getDataScopeList()],
                ['status', '状态', 'status'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'actions', [
                    'edit',
                    'permission' => [
                        'title' => '权限',
                        'url'   => url('permission', ['id' => '__id__']),
                        'class' => 'layui-btn layui-btn-xs layui-bg-blue',
                        'pop'   => true,
                        'auth'  => 'role.permission'
                    ],
                    'delete'
                ]]
            ])
            ->toolbar(['add', 'delete'])
            ->render();

        // 页面组装
        $this->page->row($this->table);

        return $this->fetch();
    }

    /**
     * 角色列表数据
     * @return Paginator
     * @throws DbException
     */
    protected function data(): Paginator
    {
        $keyword = $this->request->param('keyword', '');

        $where = [
            ['delete_time', '=', null]
        ];

        if ($keyword) {
            $where[] = ['name|code', 'like', '%' . $keyword . '%'];
        }

        return $this->model
            ->where($where)
            ->order('sort', 'asc')
            ->paginate(dp_get_list_rows());
    }

    /**
     * 新增角色
     * @return string|Json
     * @throws Exception|Throwable
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

            $this->success('新增成功');
        }

        // 构建表单
        $this->form
            ->items([
                [
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => '角色名称',
                    'tips'     => '请输入角色名称',
                    'required' => true
                ],
                [
                    'type'     => 'text',
                    'name'     => 'code',
                    'label'    => '角色标识',
                    'tips'     => '角色唯一标识,如: ADMIN, EDITOR',
                    'required' => true
                ],
                [
                    'type'     => 'select2',
                    'name'     => 'data_scope',
                    'label'    => '数据范围',
                    'tips'     => '选择该角色的数据权限范围',
                    'options'  => RoleModel::getDataScopeList(),
                    'value'    => RoleModel::DATA_SCOPE_SELF,
                    'required' => true
                ],
                [
                    'type'  => 'text',
                    'name'  => 'sort',
                    'label' => '排序',
                    'tips'  => '数字越小越靠前',
                    'value' => 0
                ],
                [
                    'type'  => 'switch',
                    'name'  => 'status',
                    'label' => '状态',
                    'tips'  => '是否启用',
                    'value' => 1
                ],
                [
                    'type'  => 'textarea',
                    'name'  => 'remark',
                    'label' => '备注',
                    'tips'  => '角色备注信息',
                    'rows'  => 3
                ]
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 编辑角色
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|Throwable
     */
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        // 获取角色信息
        $role = $this->model->find($id);
        if (!$role) {
            $this->error('角色不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post('', null, 'trim');

            // 自动验证
            $this->autoValidate();

            try {
                // 处理switch类型
                $data['status'] = $data['status'] ?? 0;
                // 保存数据
                $role->save($data);
            } catch (Exception) {
                $this->error('编辑失败');
            }

            $this->success('编辑成功', '', 'reload-table');
        }

        // 构建表单
        $this->form
            ->data($role->toArray())
            ->items([
                [
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => '角色名称',
                    'tips'     => '请输入角色名称',
                    'required' => true
                ],
                [
                    'type'     => 'text',
                    'name'     => 'code',
                    'label'    => '角色标识',
                    'tips'     => '角色唯一标识,如: ADMIN, EDITOR',
                    'required' => true
                ],
                [
                    'type'     => 'select2',
                    'name'     => 'data_scope',
                    'label'    => '数据范围',
                    'tips'     => '选择该角色的数据权限范围',
                    'options'  => RoleModel::getDataScopeList(),
                    'required' => true
                ],
                [
                    'type'  => 'text',
                    'name'  => 'sort',
                    'label' => '排序',
                    'tips'  => '数字越小越靠前',
                ],
                [
                    'type'  => 'switch',
                    'name'  => 'status',
                    'label' => '状态',
                    'tips'  => '是否启用',
                ],
                [
                    'type'  => 'textarea',
                    'name'  => 'remark',
                    'label' => '备注',
                    'tips'  => '角色备注信息',
                ]
            ]);

        // 页面组装
        $this->page->row($this->form);

        return $this->fetch();
    }

    /**
     * 删除角色
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delete(): Json
    {
        $id   = $this->request->param('id/d', 0);
        $role = $this->model->find($id);
        if (!$role) {
            $this->error('角色不存在');
        }

        // 删除前检查(模型中的 beforeDelete 会自动检查)
        if ($role->delete()) {
            $this->success('删除成功');
        } else {
            $this->error($role->getError());
        }
    }

    /**
     * 分配权限页面
     * @return string|Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|Throwable
     */
    public function permission(): string|Json
    {
        $id = $this->request->param('id/d', 0);

        if ($this->request->isPost()) {
            $permissionIds = $this->request->post('permission_ids/a', []);

            try {
                $role = $this->model->find($id);
                if (!$role) {
                    return json(['code' => 0, 'msg' => '角色不存在']);
                }

                // 分配权限
                if ($this->model->assignPermissions($id, $permissionIds)) {
                    return json(['code' => 1, 'msg' => '权限分配成功']);
                } else {
                    return json(['code' => 0, 'msg' => $this->model->getError()]);
                }
            } catch (Exception $e) {
                return json(['code' => 0, 'msg' => '权限分配失败: ' . $e->getMessage()]);
            }
        }

        // 获取角色信息
        $role = $this->model->find($id);
        if (!$role) {
            $this->error('角色不存在');
        }

        // 获取所有权限树
        $permissionModel = new PermissionModel();
        $permissionTree  = $permissionModel->getTree(0, null, false);

        // 检查是否有权限数据
        if (empty($permissionTree)) {
            // 没有权限数据，显示友好提示
            $this->assign('noPermissionData', true);
            $this->assign('roleName', htmlspecialchars($role['name']));
            $this->assign('roleId', $role['id']);
            return $this->fetch('permission');
        }

        // 获取角色已有权限ID
        $rolePermissionIds = $this->model->getPermissionIds($id, false);
        $roleName          = htmlspecialchars($role['name']);

        $this->assign('noPermissionData', false);
        $this->assign('count', count($rolePermissionIds));
        $this->assign('roleName', $roleName);
        $this->assign('roleId', $role['id']);
        $this->assign('permissionTreeJson', json_encode($permissionTree));
        $this->assign('rolePermissionIdsJson', json_encode($rolePermissionIds));

        return $this->fetch('permission');
    }

    /**
     * 获取角色权限API
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPermissions(): Json
    {
        $roleId      = $this->request->param('role_id/d', 0);
        $onlyEnabled = $this->request->param('only_enabled/d', 1);

        $permissions = $this->model->getPermissions($roleId, (bool)$onlyEnabled);

        return json(['code' => 1, 'data' => $permissions]);
    }
}
