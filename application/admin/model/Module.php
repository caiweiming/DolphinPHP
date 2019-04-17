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

namespace app\admin\model;

use think\Model;
use think\facade\Env;

/**
 * 模块模型
 * @package app\admin\model
 */
class Module extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN_MODULE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取所有模块的名称和标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public static function getModule()
    {
        $modules = cache('modules');
        if (!$modules) {
            $modules = self::where('status', '>=', 0)->order('id')->column('name,title');
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('modules', $modules);
            }
        }
        return $modules;
    }

    /**
     * 获取所有模块信息
     * @param string $keyword 查找关键词
     * @param string $status 查找状态
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|bool
     */
    public function getAll($keyword = '', $status = '')
    {
        $result = cache('module_all');
        if (!$result) {
            $dirs = array_map('basename', glob(Env::get('app_path').'*', GLOB_ONLYDIR));
            if ($dirs === false || !file_exists(Env::get('app_path'))) {
                $this->error = '模块目录不可读或者不存在';
                return false;
            }

            // 不读取模块信息的目录
            $except_module = config('system.except_module');
            // 正常模块(包括已安装和未安装)
            $dirs = array_diff($dirs, $except_module);

            // 读取数据库模块表
            $modules = $this->order('sort asc,id desc')->column(true, 'name');

            // 读取未安装的模块
            foreach ($dirs as $module) {
                if (!isset($modules[$module])) {
                    // 获取模块信息
                    $info = self::getInfoFromFile($module);

                    $modules[$module]['name'] = $module;

                    // 模块模块信息缺失
                    if (empty($info)) {
                        $modules[$module]['status'] = '-2';
                        continue;
                    }

                    // 模块模块信息不完整
                    if (!$this->checkInfo($info)) {
                        $modules[$module]['status'] = '-3';
                        continue;
                    }

                    // 模块未安装
                    $modules[$module] = $info;
                    $modules[$module]['status'] = '-1'; // 模块未安装
                }
            }

            // 数量统计
            $total = [
                'all' => count($modules), // 所有模块数量
                '-2'  => 0,               // 已损坏数量
                '-1'  => 0,               // 未安装数量
                '0'   => 0,               // 已禁用数量
                '1'   => 0,               // 已启用数量
            ];

            // 过滤查询结果和统计数量
            foreach ($modules as $key => $value) {
                // 统计数量
                if (in_array($value['status'], ['-2', '-3'])) {
                    // 已损坏数量
                    $total['-2']++;
                } else {
                    $total[(string)$value['status']]++;
                }

                // 过滤查询
                if ($status != '') {
                    if ($status == '-2') {
                        // 过滤掉非已损坏的模块
                        if (!in_array($value['status'], ['-2', '-3'])) {
                            unset($modules[$key]);
                            continue;
                        }
                    } else if ($value['status'] != $status) {
                        unset($modules[$key]);
                        continue;
                    }
                }
                if ($keyword != '') {
                    if (stristr($value['name'], $keyword) === false && (!isset($value['title']) || stristr($value['title'], $keyword) === false) && (!isset($value['author']) || stristr($value['author'], $keyword) === false)) {
                        unset($modules[$key]);
                        continue;
                    }
                }
            }

            // 处理状态及模块按钮
            foreach ($modules as &$module) {
                // 系统核心模块
                if (isset($module['system_module']) && $module['system_module'] == '1') {
                    $module['actions'] = '<button class="btn btn-sm btn-noborder btn-danger" type="button" disabled>不可操作</button>';
                    $module['status_class'] = 'text-success';
                    $module['status_info'] = '<i class="fa fa-check"></i> 已启用';
                    $module['bg_color'] = 'success';
                    continue;
                }

                switch ($module['status']) {
                    case '-3': // 模块信息不完整
                        $module['title'] = '模块信息不完整';
                        $module['bg_color'] = 'danger';
                        $module['status_class'] = 'text-danger';
                        $module['status_info'] = '<i class="fa fa-times"></i> 已损坏';
                        $module['actions'] = '<button class="btn btn-sm btn-noborder btn-danger" type="button" disabled>不可操作</button>';
                        break;
                    case '-2': // 模块信息缺失
                        $module['title'] = '模块信息缺失';
                        $module['bg_color'] = 'danger';
                        $module['status_class'] = 'text-danger';
                        $module['status_info'] = '<i class="fa fa-times"></i> 已损坏';
                        $module['actions'] = '<button class="btn btn-sm btn-noborder btn-danger" type="button" disabled>不可操作</button>';
                        break;
                    case '-1': // 未安装
                        $module['bg_color'] = 'info';
                        $module['actions'] = '<a class="btn btn-sm btn-noborder btn-success" href="'.url('install', ['name' => $module['name']]).'">安装</a>';
                        $module['status_class'] = 'text-info';
                        $module['status_info'] = '<i class="fa fa-fw fa-th-large"></i> 未安装';
                        break;
                    case '0': // 禁用
                        $module['bg_color'] = 'warning';
                        $module['actions'] = '<a class="btn btn-sm btn-noborder btn-success ajax-get confirm" href="'.url('enable', ['ids' => $module['id']]).'">启用</a> ';
                        $module['actions'] .= '<a class="btn btn-sm btn-noborder btn-primary" href="'.url('export', ['name' => $module['name']]).'">导出</a> ';
                        $module['actions'] .= '<a class="btn btn-sm btn-noborder btn-danger" href="'.url('uninstall', ['name' => $module['name']]).'">卸载</a> ';
                        $module['status_class'] = 'text-warning';
                        $module['status_info'] = '<i class="fa fa-ban"></i> 已禁用';
                        break;
                    case '1': // 启用
                        $module['bg_color'] = 'success';
                        $module['actions'] = '<a class="btn btn-sm btn-noborder btn-info ajax-get confirm" href="'.url('update', ['name' => $module['name']]).'">更新</a> ';
                        $module['actions'] .= '<a class="btn btn-sm btn-noborder btn-warning ajax-get confirm" href="'.url('disable', ['ids' => $module['id']]).'">禁用</a> ';
                        $module['actions'] .= '<a class="btn btn-sm btn-noborder btn-primary" href="'.url('export', ['name' => $module['name']]).'">导出</a> ';
                        $module['actions'] .= '<a class="btn btn-sm btn-noborder btn-danger" href="'.url('uninstall', ['name' => $module['name']]).'">卸载</a> ';
                        $module['status_class'] = 'text-success';
                        $module['status_info'] = '<i class="fa fa-check"></i> 已启用';
                        break;
                    default: // 未知
                        $module['title'] = '未知';
                        break;
                }
            }

            $result = ['total' => $total, 'modules' => $modules];
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('module_all', $result);
            }
        }
        return $result;
    }

    /**
     * 从文件获取模块信息
     * @param string $name 模块名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getInfoFromFile($name = '')
    {
        $info = [];
        if ($name != '') {
            // 从配置文件获取
            if (is_file(Env::get('app_path'). $name . '/info.php')) {
                $info = include Env::get('app_path'). $name . '/info.php';
            }
        }
        return $info;
    }

    /**
     * 检查模块模块信息是否完整
     * @param string $info 模块模块信息
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    private function checkInfo($info = '')
    {
        $default_item = ['name','title','author','version'];
        foreach ($default_item as $item) {
            if (!isset($info[$item]) || $info[$item] == '') {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取模型配置信息
     * @param string $name 模型名
     * @param string $item 指定返回的模块配置项
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public static function getConfig($name = '', $item = '')
    {
        $name = $name == '' ? request()->module() : $name;

        $config = cache('module_config_'.$name);
        if (!$config) {
            $config = self::where('name', $name)->value('config');
            if (!$config) {
                return [];
            }

            $config = json_decode($config, true);
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('module_config_'.$name, $config);
            }
        }

        if (!empty($item)) {
            $items = explode(',', $item);
            if (count($items) == 1) {
                return isset($config[$item]) ? $config[$item] : '';
            }

            $result = [];
            foreach ($items as $item) {
                $result[$item] = isset($config[$item]) ? $config[$item] : '';
            }
            return $result;
        }
        return $config;
    }

    /**
     * 获取模型配置信息
     * @param string $name 插件名.配置名
     * @param string $value 配置值
     * @author caiweiming <314013107@qq.com>
     * @return bool
     */
    public static function setConfig($name = '', $value = '')
    {
        $item = '';
        if (strpos($name, '.')) {
            list($name, $item) = explode('.', $name);
        }

        // 获取缓存
        $config = cache('module_config_'.$name);

        if (!$config) {
            $config = self::where('name', $name)->value('config');
            if (!$config) {
                return false;
            }

            $config = json_decode($config, true);
        }

        if ($item === '') {
            // 批量更新
            if (!is_array($value) || empty($value)) {
                // 值的格式错误，必须为数组
                return false;
            }

            $config = array_merge($config, $value);
        } else {
            // 更新单个值
            $config[$item] = $value;
        }

        if (false === self::where('name', $name)->setField('config', json_encode($config))) {
            return false;
        }

        // 非开发模式，缓存数据
        if (config('develop_mode') == 0) {
            cache('module_config_'.$name, $config);
        }

        return true;
    }

    /**
     * 从文件获取模块菜单
     * @param string $name 模块名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getMenusFromFile($name = '')
    {
        $menus = [];
        if ($name != '' && is_file(Env::get('app_path'). $name . '/menus.php')) {
            // 从菜单文件获取
            $menus = include Env::get('app_path'). $name . '/menus.php';
        }
        return $menus;
    }
}