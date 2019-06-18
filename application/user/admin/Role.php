<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\user\model\Role as RoleModel;
use app\admin\model\Menu as MenuModel;
use util\Tree;
use think\Db;

/**
 * 角色控制器
 * @package app\admin\controller
 */
class Role extends Admin
{
    /**
     * 角色列表页
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取查询条件
        $map = $this->getMap();
        // 非超级管理员检查可管理角色
        if (session('user_auth.role') != 1) {
            $role_list = RoleModel::getChildsId(session('user_auth.role'));
            $map[] = ['id', 'in', $role_list];
        }
        // 数据列表
        $data_list = RoleModel::where($map)->order('pid,id')->paginate();
        // 角色列表
        $list_role = RoleModel::column('id,name');
        $list_role[0] = '顶级角色';

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('角色管理') // 页面标题
            ->setTableName('admin_role') // 设置表名
            ->setSearch(['name' => '角色名称', 'id' => 'ID']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '角色名称'],
                ['pid', '上级角色', $list_role],
                ['description', '描述'],
                ['default_module', '默认模块', 'callback', function($value, $list_module){
                    if ($value == '') {
                        return '未设置';
                    } else {
                        return isset($list_module[$value]) ? $list_module[$value] : '模块不存在';
                    }
                }, MenuModel::where('pid', 0)->column('id,title')],
                ['create_time', '创建时间', 'datetime'],
                ['access', '是否可登录后台', 'switch'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons('edit,delete') // 批量添加右侧按钮
            ->replaceRightButton(['id' => 1], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (!isset($data['menu_auth'])) {
                $data['menu_auth'] = [];
            } else {
                $data['menu_auth'] = explode(',', $data['menu_auth']);
            }
            // 验证
            $result = $this->validate($data, 'Role');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            // 非超级管理员检查可添加角色
            if (session('user_auth.role') != 1) {
                $role_list = RoleModel::getChildsId(session('user_auth.role'));
                if ($data['pid'] != session('user_auth.role') && !in_array($data['pid'], $role_list)) {
                    $this->error('所属角色设置错误，没有权限添加该角色');
                }
            }

            // 非超级管理员检查可添加的节点权限
            if (session('user_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('user_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $menu_auth = array_intersect($menu_auth, $data['menu_auth']);
                $data['menu_auth'] = $menu_auth;
            }

            // 添加数据
            if ($role = RoleModel::create($data)) {
                // 记录行为
                action_log('role_add', 'admin_role', $role['id'], UID, $data['name']);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 菜单列表
        $menus = cache('access_menus');
        if (!$menus) {
            $modules = Db::name('admin_module')->where('status', 1)->column('name,title');
            $map     = [];
            // 非超级管理员角色，只能分配当前角色所拥有的权限
            if (session('user_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('user_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $map[]     = ['id', 'in', $menu_auth];
            }

            // 当前用户能分配的所有菜单
            $menus = MenuModel::where('module', 'in', array_keys($modules))
                ->where($map)
                ->order('module,sort,id')
                ->column('id,pid,sort,url_value,title,icon,module');

            // 按模块分组菜单
            $moduleMenus = [];
            foreach ($menus as $key => $menu) {
                if (!isset($moduleMenus[$menu['module']])) {
                    $moduleMenus[$menu['module']] = [
                        'title' => isset($modules[$menu['module']]) ? $modules[$menu['module']] : '未知',
                        'menus' => [$menu]
                    ];
                } else {
                    $moduleMenus[$menu['module']]['menus'][] = $menu;
                }
            }

            // 层级化每个模块的菜单
            foreach ($moduleMenus as $key => $module) {
                $menu = Tree::toLayer($module['menus']);
                $moduleMenus[$key]['menus'] = $this->buildJsTree($menu);
            }
            $menus = $moduleMenus;

            // 非开发模式，缓存菜单
            if (config('develop_mode') == 0) {
                cache('access_menus', $menus);
            }
        }

        if (session('user_auth.role') != 1) {
            $role_list = RoleModel::getTree(null, false, session('user_auth.role'));
        } else {
            $role_list = RoleModel::getTree();
        }

        $this->assign('page_title', '新增');
        $this->assign('role_list', $role_list);
        $this->assign('module_list', MenuModel::where('pid', 0)->column('id,title'));
        $this->assign('menus', $menus);
        $this->assign('curr_tab', current(array_keys($menus)));
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 角色id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        if ($id == 1) $this->error('超级管理员不可修改');

        // 非超级管理员检查可编辑角色
        if (session('user_auth.role') != 1) {
            $role_list = RoleModel::getChildsId(session('user_auth.role'));
            if (!in_array($id, $role_list)) {
                $this->error('权限不足，当前没有编辑该角色的权限！');
            }
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!isset($data['menu_auth'])) {
                $data['menu_auth'] = [];
            } else {
                $data['menu_auth'] = explode(',', $data['menu_auth']);
            }
            // 验证
            $result = $this->validate($data, 'Role');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            // 非超级管理员检查可添加角色
            if (session('user_auth.role') != 1) {
                $role_list = RoleModel::getChildsId(session('user_auth.role'));
                if ($data['pid'] != session('user_auth.role') && !in_array($data['pid'], $role_list)) {
                    $this->error('所属角色设置错误，没有权限添加该角色');
                }
            }

            // 检查所属角色不能是自己当前角色及其子角色
            $role_list = RoleModel::getChildsId($data['id']);
            if ($data['id'] == $data['pid'] || in_array($data['pid'], $role_list)) {
                $this->error('所属角色设置错误，禁止设置为当前角色及其子角色。');
            }

            // 非超级管理员检查可添加的节点权限
            if (session('user_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('user_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $menu_auth = array_intersect($menu_auth, $data['menu_auth']);
                $data['menu_auth'] = $menu_auth;
            }

            if (RoleModel::update($data)) {
                // 更新成功，循环处理子角色权限
                RoleModel::resetAuth($id, $data['menu_auth']);
                role_auth();
                // 记录行为
                action_log('role_edit', 'admin_role', $id, UID, $data['name']);
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = RoleModel::get($id);

        if (session('user_auth.role') != 1) {
            $role_list = RoleModel::getTree($id, false, session('user_auth.role'));
        } else {
            $role_list = RoleModel::getTree($id, '顶级角色');
        }

        $modules = Db::name('admin_module')->where('status', 1)->column('name,title');
        $map     = [];
        // 非超级管理员角色，只能分配当前角色所拥有的权限
        if (session('user_auth.role') != 1) {
            $menu_auth = RoleModel::where('id', session('user_auth.role'))->value('menu_auth');
            $menu_auth = json_decode($menu_auth, true);
            $map[]     = ['id', 'in', $menu_auth];
        }

        // 当前用户能分配的所有菜单
        $menus = MenuModel::where('module', 'in', array_keys($modules))
            ->where($map)
            ->order('module,sort,id')
            ->column('id,pid,sort,url_value,title,icon,module');

        // 按模块分组菜单
        $moduleMenus = [];
        foreach ($menus as $key => $menu) {
            if (!isset($moduleMenus[$menu['module']])) {
                $moduleMenus[$menu['module']] = [
                    'title' => isset($modules[$menu['module']]) ? $modules[$menu['module']] : '未知',
                    'menus' => [$menu]
                ];
            } else {
                $moduleMenus[$menu['module']]['menus'][] = $menu;
            }
        }

        // 层级化每个模块的菜单
        foreach ($moduleMenus as $key => $module) {
            $menu = Tree::toLayer($module['menus']);
            $moduleMenus[$key]['menus'] = $this->buildJsTree($menu, $info);
        }

        $this->assign('page_title', '编辑');
        $this->assign('role_list', $role_list);
        $this->assign('module_list', MenuModel::where('pid', 0)->column('id,title'));
        $this->assign('menus', $moduleMenus);
        $this->assign('curr_tab', current(array_keys($moduleMenus)));
        $this->assign('info', $info);
        return $this->fetch('edit');
    }

    /**
     * 构建jstree代码
     * @param array $menus 菜单节点
     * @param array $user 用户信息
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    private function buildJsTree($menus = [], $user = [])
    {
        $result = '';
        if (!empty($menus)) {
            $option = [
                'opened'   => true,
                'selected' => false,
                'icon'     => '',
            ];
            foreach ($menus as $menu) {
                $option['icon'] = $menu['icon'];
                if (isset($user['menu_auth'])) {
                    $option['selected'] = in_array($menu['id'], $user['menu_auth']) ? true : false;
                }
                if (isset($menu['child'])) {
                    $result .= '<li id="'.$menu['id'].'" data-jstree=\''.json_encode($option).'\'>'.$menu['title'].($menu['url_value'] == '' ? '' : ' ('.$menu['url_value'].')').$this->buildJsTree($menu['child'], $user).'</li>';
                } else {
                    $result .= '<li id="'.$menu['id'].'" data-jstree=\''.json_encode($option).'\'>'.$menu['title'].($menu['url_value'] == '' ? '' : ' ('.$menu['url_value'].')').'</li>';
                }
            }
        }

        return '<ul>'.$result.'</ul>';
    }

    /**
     * 删除角色
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete');
    }

    /**
     * 启用角色
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用角色
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置角色状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        // 当前角色所能操作的子角色
        $role_list = RoleModel::getChildsId(session('user_auth.role'));
        if (session('user_auth.role') != 1 && !$role_list) {
            $this->error('权限不足，没有可操作的角色');
        }

        foreach ($ids as $id) {
            if ($id == 1) {
                // 跳过默认角色
                continue;
            }

            // 非超级管理员检查可管理角色
            if (session('user_auth.role') != 1) {
                if (!in_array($id, $role_list)) {
                    $this->error('权限不足，禁止操作角色ID：'.$id);
                }
            }

            switch ($type) {
                case 'enable':
                    if (false === RoleModel::where('id', $id)->setField('status', 1)) {
                        $this->error('启用失败，角色ID：'.$id);
                    }
                    break;
                case 'disable':
                    if (false === RoleModel::where('id', $id)->setField('status', 0)) {
                        $this->error('禁用失败，角色ID：'.$id);
                    }
                    break;
                case 'delete':
                    $all_id = array_merge([$id], RoleModel::getChildsId($id));

                    if (false === RoleModel::where('id', 'in', $all_id)->delete()) {
                        $this->error('删除失败，角色ID：'.$id);
                    }
                    break;
                default:
                    $this->error('非法操作');
            }

            action_log('role_'.$type, 'admin_role', $id, UID);
        }

        $this->success('操作成功');
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');

        // 非超级管理员检查可操作的角色
        if (session('user_auth.role') != 1) {
            $role_list = RoleModel::getChildsId(session('user_auth.role'));
            if (!in_array($id, $role_list)) {
                $this->error('权限不足，没有可操作的角色');
            }
        }

        $config  = RoleModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['role_edit', 'admin_role', $id, UID, $details]);
    }
}
