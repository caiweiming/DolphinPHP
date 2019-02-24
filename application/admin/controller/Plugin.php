<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\admin\model\Plugin as PluginModel;
use app\admin\model\HookPlugin as HookPluginModel;
use think\facade\Cache;
use util\Sql;
use think\Db;
use think\facade\Hook;

/**
 * 插件管理控制器
 * @package app\admin\controller
 */
class Plugin extends Admin
{
    /**
     * 首页
     * @param string $group 分组
     * @param string $type 显示类型
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function index($group = 'local', $type = '')
    {
        // 配置分组信息
        $list_group = ['local' => '本地插件'];
        $tab_list   = [];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['url']   = url('index', ['group' => $key]);
        }

        // 监听tab钩子
        Hook::listen('plugin_index_tab_list', $tab_list);

        switch ($group) {
            case 'local':
                // 查询条件
                $keyword = $this->request->get('keyword', '');

                if (input('?param.status') && input('param.status') != '_all') {
                    $status = input('param.status');
                } else {
                    $status  = '';
                }

                $PluginModel = new PluginModel;
                $result = $PluginModel->getAll($keyword, $status);

                if ($result['plugins'] === false) {
                    $this->error($PluginModel->getError());
                }

                $type_show = Cache::get('plugin_type_show');
                $type_show = $type != '' ? $type : ($type_show == false ? 'block' : $type_show);
                Cache::set('plugin_type_show', $type_show);
                $type = $type_show == 'block' ? 'list' : 'block';

                $this->assign('page_title', '插件管理');
                $this->assign('plugins', $result['plugins']);
                $this->assign('total', $result['total']);
                $this->assign('tab_nav', ['tab_list' => $tab_list, 'curr_tab' => $group]);
                $this->assign('type', $type);
                return $this->fetch();
                break;
            case 'online':
                break;
        }
    }

    /**
     * 安装插件
     * @param string $name 插件标识
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function install($name = '')
    {
        // 设置最大执行时间和内存大小
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $plug_name = trim($name);
        if ($plug_name == '') $this->error('插件不存在！');

        $plugin_class = get_plugin_class($plug_name);

        if (!class_exists($plugin_class)) {
            $this->error('插件不存在！');
        }

        // 实例化插件
        $plugin = new $plugin_class;
        // 插件预安装
        if(!$plugin->install()) {
            $this->error('插件预安装失败!原因：'. $plugin->getError());
        }

        // 添加钩子
        if (isset($plugin->hooks) && !empty($plugin->hooks)) {
            if (!HookPluginModel::addHooks($plugin->hooks, $name)) {
                $this->error('安装插件钩子时出现错误，请重新安装');
            }
            cache('hook_plugins', null);
        }

        // 执行安装插件sql文件
        $sql_file = realpath(config('plugin_path').$name.'/install.sql');
        if (file_exists($sql_file)) {
            if (isset($plugin->database_prefix) && $plugin->database_prefix != '') {
                $sql_statement = Sql::getSqlFromFile($sql_file, false, [$plugin->database_prefix => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file);
            }

            if (!empty($sql_statement)) {
                foreach ($sql_statement as $value) {
                    Db::execute($value);
                }
            }
        }

        // 插件配置信息
        $plugin_info = $plugin->info;

        // 验证插件信息
        $result = $this->validate($plugin_info, 'Plugin');
        // 验证失败 输出错误信息
        if(true !== $result) $this->error($result);

        // 并入插件配置值
        $plugin_info['config'] = $plugin->getConfigValue();

        // 将插件信息写入数据库
        if (PluginModel::create($plugin_info)) {
            cache('plugin_all', null);
            $this->success('插件安装成功');
        } else {
            $this->error('插件安装失败');
        }
    }

    /**
     * 卸载插件
     * @param string $name 插件标识
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function uninstall($name = '')
    {
        $plug_name = trim($name);
        if ($plug_name == '') $this->error('插件不存在！');

        $class = get_plugin_class($plug_name);
        if (!class_exists($class)) {
            $this->error('插件不存在！');
        }

        // 实例化插件
        $plugin = new $class;
        // 插件预卸
        if(!$plugin->uninstall()) {
            $this->error('插件预卸载失败!原因：'. $plugin->getError());
        }

        // 卸载插件自带钩子
        if (isset($plugin->hooks) && !empty($plugin->hooks)) {
            if (false === HookPluginModel::deleteHooks($plug_name)) {
                $this->error('卸载插件钩子时出现错误，请重新卸载');
            }
            cache('hook_plugins', null);
        }

        // 执行卸载插件sql文件
        $sql_file = realpath(config('plugin_path').$plug_name.'/uninstall.sql');
        if (file_exists($sql_file)) {
            if (isset($plugin->database_prefix) && $plugin->database_prefix != '') {
                $sql_statement = Sql::getSqlFromFile($sql_file, true, [$plugin->database_prefix => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file, true);
            }

            if (!empty($sql_statement)) {
                Db::execute($sql_statement);
            }
        }

        // 删除插件信息
        if (PluginModel::where('name', $plug_name)->delete()) {
            cache('plugin_all', null);
            $this->success('插件卸载成功');
        } else {
            $this->error('插件卸载失败');
        }
    }

    /**
     * 插件管理
     * @param string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function manage($name = '')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 加载自定义后台页面
        if (plugin_action_exists($name, 'Admin', 'index')) {
            return plugin_action($name, 'Admin', 'index');
        }

        // 加载系统的后台页面
        $class = get_plugin_class($name);
        if (!class_exists($class)) {
            $this->error($name.'插件不存在！');
        }

        // 实例化插件
        $plugin = new $class;

        // 获取后台字段信息，并分析
        if (isset($plugin->admin)) {
            $admin = $this->parseAdmin($plugin->admin);
        } else {
            $admin = $this->parseAdmin();
        }

        if (!plugin_model_exists($name)) {
            $this->error('插件: '.$name.' 缺少模型文件！');
        }

        // 获取插件模型实例
        $PluginModel = get_plugin_model($name);
        $order       = $this->getOrder();
        $map         = $this->getMap();
        $data_list   = $PluginModel->where($map)->order($order)->paginate();
        $page        = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        $builder = ZBuilder::make('table')
            ->setPageTitle($admin['title']) // 设置页面标题
            ->setPluginName($name)
            ->setTableName($admin['table_name'])
            ->setSearch($admin['search_field'], $admin['search_title']) // 设置搜索框
            ->addOrder($admin['order'])
            ->addTopButton('back', [
                'title' => '返回插件列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            ->addTopButtons($admin['top_buttons']) // 批量添加顶部按钮
            ->addRightButtons($admin['right_buttons']); // 批量添加右侧按钮

            // 自定义顶部按钮
            if (!empty($admin['custom_top_buttons'])) {
                foreach ($admin['custom_top_buttons'] as $custom) {
                    $builder->addTopButton('custom', $custom);
                }
            }
            // 自定义右侧按钮
            if (!empty($admin['custom_right_buttons'])) {
                foreach ($admin['custom_right_buttons'] as $custom) {
                    $builder->addRightButton('custom', $custom);
                }
            }

            // 表头筛选
            if (is_array($admin['filter'])) {
                foreach ($admin['filter'] as $column => $params) {
                    $options = isset($params[0]) ? $params[0] : [];
                    $default = isset($params[1]) ? $params[1] : [];
                    $type    = isset($params[2]) ? $params[2] : 'checkbox';
                    $builder->addFilter($column, $options, $default, $type);
                }
            } else {
                $builder->addFilter($admin['filter']);
            }

        return $builder
            ->addColumns($admin['columns']) // 批量添加数据列
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染模板
    }

    /**
     * 插件新增方法
     * @param string $plugin_name 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add($plugin_name = '')
    {
        // 如果存在自定义的新增方法，则优先执行
        if (plugin_action_exists($plugin_name, 'Admin', 'add')) {
            $params = $this->request->param();
            return plugin_action($plugin_name, 'Admin', 'add', $params);
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 执行插件的验证器（如果存在的话）
            if (plugin_validate_exists($plugin_name)) {
                $plugin_validate = get_plugin_validate($plugin_name);
                if (!$plugin_validate->check($data)) {
                    // 验证失败 输出错误信息
                    $this->error($plugin_validate->getError());
                }
            }

            // 实例化模型并添加数据
            $PluginModel = get_plugin_model($plugin_name);
            if ($PluginModel->data($data)->save()) {
                $this->success('新增成功', cookie('__forward__'));
            } else {
                $this->error('新增失败');
            }
        }

        // 获取插件模型
        $class = get_plugin_class($plugin_name);
        if (!class_exists($class)) {
            $this->error('插件不存在！');
        }

        // 实例化插件
        $plugin = new $class;
        if (!isset($plugin->fields)) {
            $this->error('插件新增、编辑字段不存在！');
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItems($plugin->fields)
            ->fetch();
    }

    /**
     * 编辑插件方法
     * @param string $id 数据id
     * @param string $plugin_name 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = '', $plugin_name = '')
    {
        // 如果存在自定义的编辑方法，则优先执行
        if (plugin_action_exists($plugin_name, 'Admin', 'edit')) {
            $params = $this->request->param();
            return plugin_action($plugin_name, 'Admin', 'edit', $params);
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 执行插件的验证器（如果存在的话）
            if (plugin_validate_exists($plugin_name)) {
                $plugin_validate = get_plugin_validate($plugin_name);
                if (!$plugin_validate->check($data)) {
                    // 验证失败 输出错误信息
                    $this->error($plugin_validate->getError());
                }
            }

            // 实例化模型并添加数据
            $PluginModel = get_plugin_model($plugin_name);
            if (false !== $PluginModel->isUpdate(true)->save($data)) {
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取插件类名
        $class = get_plugin_class($plugin_name);
        if (!class_exists($class)) {
            $this->error('插件不存在！');
        }

        // 实例化插件
        $plugin = new $class;
        if (!isset($plugin->fields)) {
            $this->error('插件新增、编辑字段不存在！');
        }

        // 获取数据
        $PluginModel = get_plugin_model($plugin_name);
        $info = $PluginModel->find($id);
        if (!$info) {
            $this->error('找不到数据！');
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addHidden('id')
            ->addFormItems($plugin->fields)
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 插件参数设置
     * @param string $name 插件名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function config($name = '')
    {
        // 更新配置
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data = json_encode($data);

            if (false !== PluginModel::where('name', $name)->update(['config' => $data])) {
                $this->success('更新成功', 'index');
            } else {
                $this->error('更新失败');
            }
        }

        $plugin_class = get_plugin_class($name);
        // 实例化插件
        $plugin  = new $plugin_class;
        $trigger = isset($plugin->trigger) ? $plugin->trigger : [];

        // 插件配置值
        $info      = PluginModel::where('name', $name)->field('id,name,config')->find();
        $db_config = json_decode($info['config'], true);

        // 插件配置项
        $config    = include config('plugin_path'). $name. '/config.php';

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('插件设置')
            ->addFormItems($config)
            ->setFormData($db_config)
            ->setTrigger($trigger)
            ->fetch();
    }

    /**
     * 设置状态
     * @param string $type 状态类型:enable/disable
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $_t  = input('param._t', '');
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        empty($ids) && $this->error('缺少主键');

        $status = $type == 'enable' ? 1 : 0;

        if ($_t != '') {
            parent::setStatus($type, $record);
        } else {
            $plugins = PluginModel::where('id', 'in', $ids)->value('name');
            if ($plugins) {
                HookPluginModel::$type($plugins);
            }

            if (false !== PluginModel::where('id', 'in', $ids)->setField('status', $status)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }

    /**
     * 禁用插件/禁用插件数据
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        $this->setStatus('disable');
    }

    /**
     * 启用插件/启用插件数据
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        $this->setStatus('enable');
    }

    /**
     * 删除插件数据
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($record = [])
    {
        $this->setStatus('delete');
    }

    /**
     * 执行插件内部方法
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function execute()
    {
        $plugin     = input('param._plugin');
        $controller = input('param._controller');
        $action     = input('param._action');
        $params     = $this->request->except(['_plugin', '_controller', '_action'], 'param');

        if (empty($plugin) || empty($controller) || empty($action)) {
            $this->error('没有指定插件名称、控制器名称或操作名称');
        }

        if (!plugin_action_exists($plugin, $controller, $action)) {
            $this->error("找不到方法：{$plugin}/{$controller}/{$action}");
        }
        return plugin_action($plugin, $controller, $action, $params);
    }

    /**
     * 分析后台字段信息
     * @param array $data 字段信息
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    private function parseAdmin($data = [])
    {
        $admin = [
            'title'         => '数据列表',
            'search_title'  => '',
            'search_field'  => [],
            'order'         => '',
            'filter'        => '',
            'table_name'    => '',
            'columns'       => [],
            'right_buttons' => [],
            'top_buttons'   => [],
            'customs'       => [],
        ];

        if (empty($data)) {
            return $admin;
        }

        // 处理工具栏按钮链接
        if (isset($data['top_buttons']) && !empty($data['top_buttons'])) {
            $this->parseButton('top_buttons', $data);
        }

        // 处理右侧按钮链接
        if (isset($data['right_buttons']) && !empty($data['right_buttons'])) {
            $this->parseButton('right_buttons', $data);
        }

        return array_merge($admin, $data);
    }

    /**
     * 解析按钮链接
     * @param string $button 按钮名称
     * @param array $data 字段信息
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function parseButton($button, &$data)
    {
        foreach ($data[$button] as $key => &$value) {
            // 处理自定义按钮
            if ($key === 'customs') {
                if (!empty($value)) {
                    foreach ($value as &$custom) {
                        if (isset($custom['href']['url']) && $custom['href']['url'] != '') {
                            $params            = isset($custom['href']['params']) ? $custom['href']['params'] : [];
                            $custom['href']    = plugin_url($custom['href']['url'], $params);
                            $data['custom_'.$button][] = $custom;
                        }
                    }
                }
                unset($data[$button][$key]);
            }
            if (!is_numeric($key) && isset($value['href']['url']) && $value['href']['url'] != '') {
                $value['href'] = plugin_url($value['href']['url']);
            }
        }
    }
}
