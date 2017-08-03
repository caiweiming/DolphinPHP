<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\controller\Common;
use app\common\builder\ZBuilder;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Module as ModuleModel;
use app\user\model\Role as RoleModel;
use think\Cache;
use think\Db;
use think\helper\Hash;

/**
 * 后台公共控制器
 * @package app\admin\controller
 */
class Admin extends Common
{
    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function _initialize()
    {
        // 判断是否登录，并定义用户ID常量
        defined('UID') or define('UID', $this->isLogin());

        // 设置当前角色菜单节点权限
        role_auth();

        // 检查权限
        if (!RoleModel::checkAuth()) $this->error('权限不足！');

        // 设置分页参数
        $this->setPageParam();

        // 后台公共模板
        $this->assign('_admin_base_layout', config('admin_base_layout'));
        // 当前配色方案
        $this->assign('system_color', config('system_color'));

        // 如果不是ajax请求，则读取菜单
        if (!$this->request->isAjax()) {
            // 读取顶部菜单
            $this->assign('_top_menus', MenuModel::getTopMenu(config('top_menu_max'), '_top_menus'));
            // 读取全部顶级菜单
            $this->assign('_top_menus_all', MenuModel::getTopMenu('', '_top_menus_all'));
            // 获取侧边栏菜单
            $this->assign('_sidebar_menus', MenuModel::getSidebarMenu());
            // 获取面包屑导航
            $this->assign('_location', MenuModel::getLocation('', true));
            // 构建侧栏
            $settings = [
                [
                    'title'   => '站点开关',
                    'tips'    => '站点关闭后将不能访问',
                    'checked' => Db::name('admin_config')->where('id', 1)->value('value'),
                    'table'   => 'admin_config',
                    'id'      => 1,
                    'field'   => 'value'
                ]
            ];
            ZBuilder::make('aside')
                ->addBlock('switch', '系统设置', $settings);
        }

        // 输出弹出层参数
        $this->assign('_pop', $this->request->param('_pop'));
    }

    /**
     * 设置分页参数
     * @author 蔡伟明 <314013107@qq.com>
     */
    final protected function setPageParam()
    {
        _system_check();
        $list_rows = input('?param.list_rows') ? input('param.list_rows') : config('list_rows');
        config('paginate.list_rows', $list_rows);
        config('paginate.query', input('get.'));
    }

    /**
     * 检查是否登录，没有登录则跳转到登录页面
     * @author 蔡伟明 <314013107@qq.com>
     * @return int
     */
    final protected function isLogin()
    {
        // 判断是否登录
        if ($uid = is_signin()) {
            // 已登录
            return $uid;
        } else {
            // 未登录
            $this->redirect('user/publics/signin');
        }
    }

    /**
     * 禁用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable', $record);
    }

    /**
     * 启用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable', $record);
    }

    /**
     * 启用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete', $record);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $field = input('post.name', '');
        $value = input('post.value', '');
        $table = input('post.table', '');
        $type  = input('post.type', '');
        $id    = input('post.pk', '');
        $validate = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');

        if ($table == '') $this->error('缺少表名');
        if ($field == '') $this->error('缺少字段名');
        if ($id == '') $this->error('缺少主键值');

        // 验证是否操作管理员
        if ($table == 'admin_user' || $table == 'admin_role') {
            if ($id == 1) {
                $this->error('禁止操作超级管理员');
            }
        }

        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate.'.'.$field);
                if (true !== $result) $this->error($result);
            }
        }

        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
            // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
            // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }

        // 主键名
        $pk     = Db::name($table)->getPk();
        $result = Db::name($table)->where($pk, $id)->setField($field, $value);

        cache('hook_plugins', null);
        cache('system_config', null);
        cache('access_menus', null);
        if (false !== $result) {
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 自动创建添加页面
     * @author caiweiming <314013107@qq.com>
     */
    public function add()
    {
        // 获取表单项
        $cache_name = $this->request->module().'/'.parse_name($this->request->controller()).'/add';
        $cache_name = strtolower($cache_name);
        $form       = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动新增数据不存在，请重新打开此页面');
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $_pop = $this->request->get('_pop');

            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if(true !== $result) $this->error($result);
            }

            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                $now_time = $this->request->time();
                foreach ($form['auto_time'] as $item) {
                    if (strpos($item, '|')) {
                        list($item, $format) = explode('|', $item);
                        $data[$item] = date($format, $now_time);
                    } else {
                        $data[$item] = $form['format'] != '' ? date($form['format'], $now_time) : $now_time;
                    }
                }
            }

            // 插入数据
            if (Db::name($form['table'])->insert($data)) {
                if ($_pop == 1) {
                    $this->success('新增成功', null, '_parent_reload');
                } else {
                    $this->success('新增成功', $form['go_back']);
                }
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems($form['items'])
            ->fetch();
    }

    /**
     * 自动创建编辑页面
     * @param string $id 主键值
     * @author caiweiming <314013107@qq.com>
     */
    public function edit($id = '')
    {
        if ($id === '') $this->error('参数错误');

        // 获取表单项
        $cache_name = $this->request->module().'/'.parse_name($this->request->controller()).'/edit';
        $cache_name = strtolower($cache_name);
        $form       = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动编辑数据不存在，请重新打开此页面');
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $_pop = $this->request->get('_pop');

            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if(true !== $result) $this->error($result);
            }

            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                $now_time = $this->request->time();
                foreach ($form['auto_time'] as $item) {
                    if (strpos($item, '|')) {
                        list($item, $format) = explode('|', $item);
                        $data[$item] = date($format, $now_time);
                    } else {
                        $data[$item] = $form['format'] != '' ? date($form['format'], $now_time) : $now_time;
                    }
                }
            }

            // 更新数据
            if (false !== Db::name($form['table'])->update($data)) {
                if ($_pop == 1) {
                    $this->success('编辑成功', null, '_parent_reload');
                } else {
                    $this->success('编辑成功', $form['go_back']);
                }
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = Db::name($form['table'])->find($id);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItems($form['items'])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 设置状态
     * 禁用、启用、删除都是调用这个内部方法
     * @param string $type 操作类型：enable,disable,delete
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $table = input('param.table');
        $field = input('param.field', 'status');

        if (empty($ids)) $this->error('缺少主键');
        if (empty($table)) $this->error('缺少表名');

        // 验证是否操作管理员
        if ($table == 'admin_user' || $table == 'admin_role' || $table == 'admin_module') {
            if (is_array($ids) && in_array('1', $ids)) {
                // 去掉值为1的数据，比如超级管理员，系统核心模块
                $this->error('禁止操作');
            } else if($ids === '1') {
                $this->error('禁止操作');
            }
        }

        $pk = Db::name($table)->getPk(); // 主键名称
        $map[$pk] = ['in', $ids];

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = Db::name($table)->where($map)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = Db::name($table)->where($map)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = Db::name($table)->where($map)->delete();
                break;
            default:
                $this->error('非法操作');
                break;
        }

        if (false !== $result) {
            Cache::clear();
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 模块设置
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function moduleConfig()
    {
        // 当前模块名
        $module = $this->request->module();

        // 保存
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data = json_encode($data);

            if (false !== ModuleModel::where('name', $module)->update(['config' => $data])) {
                cache('module_config_'.$module, null);
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }

        // 模块配置信息
        $module_info = ModuleModel::getInfoFromFile($module);
        $config      = $module_info['config'];
        $trigger     = isset($module_info['trigger']) ? $module_info['trigger'] : [];

        // 数据库内的模块信息
        $db_config = ModuleModel::where('name', $module)->value('config');
        $db_config = json_decode($db_config, true);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('模块设置')
            ->addFormItems($config)
            ->setFormdata($db_config) // 设置表格数据
            ->setTrigger($trigger) // 设置触发
            ->fetch();
    }
}
