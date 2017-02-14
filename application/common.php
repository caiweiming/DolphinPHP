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

use think\Db;
// 应用公共文件

if (!function_exists('is_signin')) {
    /**
     * 判断是否登录
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function is_signin()
    {
        return model('user/user')->isLogin();
    }
}

if (!function_exists('get_file_path')) {
    /**
     * 获取附件路径
     * @param int $id 附件id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_file_path($id = 0)
    {
        $path = model('admin/attachment')->getFilePath($id);
        if (!$path) {
            return config('public_static_path').'admin/img/none.png';
        }
        return PUBLIC_PATH. $path;
    }
}

if (!function_exists('get_thumb')) {
    /**
     * 获取图片缩略图路径
     * @param int $id 附件id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_thumb($id = 0)
    {
        $path = model('admin/attachment')->getThumbPath($id);
        if (!$path) {
            return config('public_static_path').'admin/img/none.png';
        }
        return PUBLIC_PATH. $path;
    }
}

if (!function_exists('get_avatar')) {
    /**
     * 获取用户头像路径
     * @param int $id 附件id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_avatar($id = 0)
    {
        $path = model('admin/attachment')->getFilePath($id);
        if (!$path) {
            return config('public_static_path').'admin/img/avatar.jpg';
        }
        return PUBLIC_PATH. $path;
    }
}

if (!function_exists('get_file_name')) {
    /**
     * 根据附件id获取文件名
     * @param string $id 附件id
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_file_name($id = '')
    {
        $name = model('admin/attachment')->getFileName($id);
        if (!$name) {
            return '没有找到文件';
        }
        return $name;
    }
}

if (!function_exists('minify')) {
    /**
     * 合并输出js代码或css代码
     * @param string $type 类型：group-分组，file-单个文件，base-基础目录
     * @param string $files 文件名或分组名
     * @author 蔡伟明 <314013107@qq.com>
     */
    function minify($type = '', $files = '')
    {
        $files = !is_array($files) ? $files : implode(',', $files);
        $url   = PUBLIC_PATH. 'min/?';

        switch ($type) {
            case 'group':
                $url .= 'g=' . $files;
                break;
            case 'file':
                $url .= 'f=' . $files;
                break;
            case 'base':
                $url .= 'b=' . $files;
                break;
        }
        echo $url;
    }
}

if (!function_exists('ck_js')) {
    /**
     * 返回ckeditor编辑器上传文件时需要返回的js代码
     * @param string $callback 回调
     * @param string $file_path 文件路径
     * @param string $error_msg 错误信息
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function ck_js($callback = '', $file_path = '', $error_msg = '')
    {
        return "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($callback, '$file_path' , '$error_msg');</script>";
    }
}

if (!function_exists('parse_attr')) {
    /**
     * 解析配置
     * @param string $value 配置值
     * @return array|string
     */
    function parse_attr($value = '') {
        $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
        if (strpos($value, ':')) {
            $value  = array();
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k]   = $v;
            }
        } else {
            $value = $array;
        }
        return $value;
    }
}

if (!function_exists('parse_array')) {
    /**
     * 将一维数组解析成键值相同的数组
     * @param array $arr 一维数组
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    function parse_array($arr) {
        $result = [];
        foreach ($arr as $item) {
            $result[$item] = $item;
        }
        return $result;
    }
}

if (!function_exists('parse_config')) {
    /**
     * 解析配置，返回配置值
     * @param array $configs 配置
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    function parse_config($configs = []) {
        $type = [
            'hidden'      => 2,
            'date'        => 4,
            'ckeditor'    => 4,
            'daterange'   => 4,
            'datetime'    => 4,
            'editormd'    => 4,
            'file'        => 4,
            'colorpicker' => 4,
            'files'       => 4,
            'icon'        => 4,
            'image'       => 4,
            'images'      => 4,
            'jcrop'       => 4,
            'range'       => 4,
            'number'      => 4,
            'password'    => 4,
            'sort'        => 4,
            'static'      => 4,
            'summernote'  => 4,
            'switch'      => 4,
            'tags'        => 4,
            'text'        => 4,
            'array'       => 4,
            'textarea'    => 4,
            'time'        => 4,
            'ueditor'     => 4,
            'wangeditor'  => 4,
            'radio'       => 5,
            'bmap'        => 5,
            'masked'      => 5,
            'select'      => 5,
            'linkage'     => 5,
            'checkbox'    => 5,
            'linkages'    => 6
        ];
        $result = [];
        foreach ($configs as $item) {
            // 判断是否为分组
            if ($item[0] == 'group') {
                foreach ($item[1] as $option) {
                    foreach ($option as $group => $val) {
                        $result[$val[1]] = isset($val[$type[$val[0]]]) ? $val[$type[$val[0]]] : '';
                    }
                }
            } else {
                $result[$item[1]] = isset($item[$type[$item[0]]]) ? $item[$type[$item[0]]] : '';
            }
        }
        return $result;
    }
}

if (!function_exists('set_config_value')) {
    /**
     * 设置配置的值，并返回配置好的数组
     * @param array $configs 配置
     * @param array $values 配置值
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    function set_config_value($configs = [], $values = []) {
        $type = [
            'hidden'      => 2,
            'date'        => 4,
            'ckeditor'    => 4,
            'daterange'   => 4,
            'datetime'    => 4,
            'editormd'    => 4,
            'file'        => 4,
            'colorpicker' => 4,
            'files'       => 4,
            'icon'        => 4,
            'image'       => 4,
            'images'      => 4,
            'jcrop'       => 4,
            'range'       => 4,
            'number'      => 4,
            'password'    => 4,
            'sort'        => 4,
            'static'      => 4,
            'summernote'  => 4,
            'switch'      => 4,
            'tags'        => 4,
            'text'        => 4,
            'array'       => 4,
            'textarea'    => 4,
            'time'        => 4,
            'ueditor'     => 4,
            'wangeditor'  => 4,
            'radio'       => 5,
            'bmap'        => 5,
            'masked'      => 5,
            'select'      => 5,
            'linkage'     => 5,
            'checkbox'    => 5,
            'linkages'    => 6
        ];

        foreach ($configs as &$item) {
            // 判断是否为分组
            if ($item[0] == 'group') {
                foreach ($item[1] as &$option) {
                    foreach ($option as $group => &$val) {
                        $val[$type[$val[0]]] = isset($values[$val[1]]) ? $values[$val[1]] : '';
                    }
                }
            } else {
                $item[$type[$item[0]]] = isset($values[$item[1]]) ? $values[$item[1]] : '';
            }
        }
        return $configs;
    }
}

if (!function_exists('hook')) {
    /**
     * 监听钩子
     * @param string $name 钩子名称
     * @param array $params 参数
     * @author 蔡伟明 <314013107@qq.com>
     */
    function hook($name = '', $params = []) {
        \think\Hook::listen($name, $params);
    }
}

if (!function_exists('module_config')) {
    /**
     * 显示当前模块的参数配置页面，或获取参数值，或设置参数值
     * @param string $name
     * @param string $value
     * @author caiweiming <314013107@qq.com>
     * @return mixed
     */
    function module_config($name = '', $value = '')
    {
        if ($name === '') {
            // 显示模块配置页面
            return action('admin/admin/moduleConfig');
        } elseif ($value === '') {
            // 获取模块配置
            if (strpos($name, '.')) {
                list($name, $item) = explode('.', $name);
                return model('admin/module')->getConfig($name, $item);
            } else {
                return model('admin/module')->getConfig($name);
            }
        } else {
            // 设置值
            return model('admin/module')->setConfig($name, $value);
        }
    }
}

if (!function_exists('plugin_menage')) {
    /**
     * 显示插件的管理页面
     * @param string $name 插件名
     * @author caiweiming <314013107@qq.com>
     * @return mixed
     */
    function plugin_menage($name = '')
    {
        return action('admin/plugin/manage', ['name' => $name]);
    }
}

if (!function_exists('plugin_config')) {
    /**
     * 获取或设置某个插件配置参数
     * @param string $name 插件名.配置名
     * @param string $value 设置值
     * @author caiweiming <314013107@qq.com>
     * @return mixed
     */
    function plugin_config($name = '', $value = '')
    {
        if ($value === '') {
            // 获取插件配置
            if (strpos($name, '.')) {
                list($name, $item) = explode('.', $name);
                return model('admin/plugin')->getConfig($name, $item);
            } else {
                return model('admin/plugin')->getConfig($name);
            }
        } else {
            return model('admin/plugin')->setConfig($name, $value);
        }
    }
}

if (!function_exists('get_plugin_class')) {
    /**
     * 获取插件类名
     * @param  string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_plugin_class($name)
    {
        return "plugins\\{$name}\\{$name}";
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0, $adv = false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}

if (!function_exists('format_bytes')) {
    /**
     * 格式化字节大小
     * @param  number $size      字节数
     * @param  string $delimiter 数字和单位分隔符
     * @return string            格式化后的带单位的大小
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    function format_bytes($size, $delimiter = '') {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('format_time')) {
    /**
     * 时间戳格式化
     * @param null $time
     * @param string $format
     * @return false|string
     */
    function format_time($time = null, $format='Y-m-d H:i') {
        $time = $time === null ? time() : intval($time);
        return date($format, $time);
    }
}

if (!function_exists('format_moment')) {
    /**
     * 使用momentjs的时间格式来格式化时间戳
     * @param null $time 时间戳
     * @param string $format momentjs的时间格式
     * @author 蔡伟明 <314013107@qq.com>
     * @return false|string
     */
    function format_moment($time = null, $format='YYYY-MM-DD HH:mm') {
        $format_map = [
            // 年、月、日
            'YYYY' => 'Y',
            'YY'   => 'y',
//            'Y'    => '',
            'Q'    => 'I',
            'MMMM' => 'F',
            'MMM'  => 'M',
            'MM'   => 'm',
            'M'    => 'n',
            'DDDD' => '',
            'DDD'  => '',
            'DD'   => 'd',
            'D'    => 'j',
            'Do'   => 'jS',
            'X'    => 'U',
            'x'    => 'u',

            // 星期
//            'gggg' => '',
//            'gg' => '',
//            'ww' => '',
//            'w' => '',
            'e'    => 'w',
            'dddd' => 'l',
            'ddd'  => 'D',
            'GGGG' => 'o',
//            'GG' => '',
            'WW' => 'W',
            'W'  => 'W',
            'E'  => 'N',

            // 时、分、秒
            'HH'  => 'H',
            'H'   => 'G',
            'hh'  => 'h',
            'h'   => 'g',
            'A'   => 'A',
            'a'   => 'a',
            'mm'  => 'i',
            'm'   => 'i',
            'ss'  => 's',
            's'   => 's',
//            'SSS' => '[B]',
//            'SS'  => '[B]',
//            'S'   => '[B]',
            'ZZ'  => 'O',
            'Z'   => 'P',
        ];

        // 提取格式
        preg_match_all('/([a-zA-Z]+)/', $format, $matches);
        $replace = [];
        foreach ($matches[1] as $match) {
            $replace[] = isset($format_map[$match]) ? $format_map[$match] : '';
        }

        // 替换成date函数支持的格式
        $format = str_replace($matches[1], $replace, $format);
        $time = $time === null ? time() : intval($time);
        return date($format, $time);
    }
}

if (!function_exists('format_linkage')) {
    /**
     * 格式化联动数据
     * @param array $data 数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function format_linkage($data = [])
    {
        $list = [];
        foreach ($data as $key => $value) {
            $list[] = [
                'key'   => $key,
                'value' => $value
            ];
        }
        return $list;
    }
}

if (!function_exists('get_auth_node')) {
    /**
     * 获取用户授权节点
     * @param int $uid 用户id
     * @param string $group 权限分组，可以以点分开模型名称和分组名称，如user.group
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function get_auth_node($uid = 0, $group = '')
    {
        return model('admin/access')->getAuthNode($uid, $group);
    }
}

if (!function_exists('check_auth_node')) {
    /**
     * 检查用户的某个节点是否授权
     * @param int $uid 用户id
     * @param string $group $group 权限分组，可以以点分开模型名称和分组名称，如user.group
     * @param int $node 需要检查的节点id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function check_auth_node($uid = 0, $group = '', $node = 0)
    {
        return model('admin/access')->checkAuthNode($uid, $group, $node);
    }
}

if (!function_exists('get_level_data')) {
    /**
     * 获取联动数据
     * @param string $table 表名
     * @param  integer $pid 父级ID
     * @param  string $pid_field 父级ID的字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return false|PDOStatement|string|\think\Collection
     */
    function get_level_data($table = '', $pid = 0, $pid_field = 'pid')
    {
        if ($table == '') {
            return '';
        }

        $data_list = Db::name($table)->where($pid_field, $pid)->select();

        if ($data_list) {
            return $data_list;
        } else {
            return '';
        }
    }
}

if (!function_exists('get_level_pid')) {
    /**
     * 获取联动等级和父级id
     * @param string $table 表名
     * @param int $id 主键值
     * @param string $id_field 主键名
     * @param string $pid_field pid字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function get_level_pid($table = '', $id = 1, $id_field = 'id', $pid_field = 'pid')
    {
        return Db::name($table)->where($id_field, $id)->value($pid_field);
    }
}

if (!function_exists('get_level_key_data')) {
    /**
     * 反向获取联动数据
     * @param string $table 表名
     * @param string $id 主键值
     * @param string $id_field 主键名
     * @param string $name_field name字段名
     * @param string $pid_field pid字段名
     * @param int $level 级别
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    function get_level_key_data($table = '', $id = '', $id_field = 'id', $name_field = 'name', $pid_field = 'pid', $level = 1)
    {
        $result = [];
        $level_pid = get_level_pid($table, $id, $id_field, $pid_field);
        $level_key[$level] = $level_pid;
        $level_data[$level] = get_level_data($table, $level_pid, $pid_field);

        if ($level_pid != 0) {
            $data = get_level_key_data($table, $level_pid, $id_field, $name_field, $pid_field, $level + 1);
            $level_key = $level_key + $data['key'];
            $level_data = $level_data + $data['data'];
        }
        $result['key'] = $level_key;
        $result['data'] = $level_data;

        return $result;
    }
}

if (!function_exists('plugin_action_exists')) {
    /**
     * 检查插件控制器是否存在某操作
     * @param string $name 插件名
     * @param string $controller 控制器
     * @param string $action 动作
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    function plugin_action_exists($name = '', $controller = '', $action = '')
    {
        if (strpos($name, '/')) {
            list($name, $controller, $action) = explode('/', $name);
        }
        return method_exists("plugins\\{$name}\\controller\\{$controller}", $action);
    }
}

if (!function_exists('plugin_model_exists')) {
    /**
     * 检查插件模型是否存在
     * @param string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    function plugin_model_exists($name = '')
    {
        return class_exists("plugins\\{$name}\\model\\{$name}");
    }
}

if (!function_exists('plugin_validate_exists')) {
    /**
     * 检查插件验证器是否存在
     * @param string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    function plugin_validate_exists($name = '')
    {
        return class_exists("plugins\\{$name}\\validate\\{$name}");
    }
}

if (!function_exists('get_plugin_model')) {
    /**
     * 获取插件模型实例
     * @param  string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function get_plugin_model($name)
    {
        $class = "plugins\\{$name}\\model\\{$name}";
        return new $class;
    }
}

if (!function_exists('plugin_action')) {
    /**
     * 执行插件动作
     * 也可以用这种方式调用：plugin_action('插件名/控制器/动作', [参数1,参数2...])
     * @param string $name 插件名
     * @param string $controller 控制器
     * @param string $action 动作
     * @param mixed $params 参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function plugin_action($name = '', $controller = '', $action = '', $params = [])
    {
        if (strpos($name, '/')) {
            $params = is_array($controller) ? $controller : (array)$controller;
            list($name, $controller, $action) = explode('/', $name);
        }
        if (!is_array($params)) {
            $params = (array)$params;
        }
        $class = "plugins\\{$name}\\controller\\{$controller}";
        $obj = new $class;
        return call_user_func_array([$obj, $action], $params);
    }
}

if (!function_exists('get_plugin_validate')) {
    /**
     * 获取插件验证类实例
     * @param string $name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    function get_plugin_validate($name = '')
    {
        $class = "plugins\\{$name}\\validate\\{$name}";
        return new $class;
    }
}

if (!function_exists('plugin_url')) {
    /**
     * 生成插件操作链接
     * @param string $url 链接：插件名称/控制器/操作
     * @param array $param 参数
     * @param string $module 模块名，admin需要登录验证，index不需要登录验证
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function plugin_url($url = '', $param = [], $module = 'admin')
    {
        $params = [];
        $url = explode('/', $url);
        if (isset($url[0])) {
            $params['_plugin'] = $url[0];
        }
        if (isset($url[1])) {
            $params['_controller'] = $url[1];
        }
        if (isset($url[2])) {
            $params['_action'] = $url[2];
        }

        // 合并参数
        $params = array_merge($params, $param);

        // 返回url地址
        return url($module .'/plugin/execute', $params);
    }
}

if (!function_exists('public_url')) {
    /**
     * 生成插件操作链接(不需要登陆验证)
     * @param string $url 链接：插件名称/控制器/操作
     * @param array $param 参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function public_url($url = '', $param = [])
    {
        // 返回url地址
        return plugin_url($url, $param, 'index');
    }
}

if (!function_exists('clear_js')) {
    /**
     * 过滤js内容
     * @param string $str 要过滤的字符串
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed|string
     */
    function clear_js($str = '')
    {
        $search ="/<script[^>]*?>.*?<\/script>/si";
        $str = preg_replace($search, '', $str);
        return $str;
    }
}

if (!function_exists('get_nickname')) {
    /**
     * 根据用户ID获取用户昵称
     * @param  integer $uid 用户ID
     * @return string  用户昵称
     */
    function get_nickname($uid = 0)
    {
        static $list;
        // 获取当前登录用户名
        if (!($uid && is_numeric($uid))) {
            return session('user_auth.username');
        }

        // 获取缓存数据
        if (empty($list)) {
            $list = cache('sys_user_nickname_list');
        }

        // 查找用户信息
        $key = "u{$uid}";
        if (isset($list[$key])) {
            // 已缓存，直接使用
            $name = $list[$key];
        } else {
            // 调用接口获取用户信息
            $info = model('user/user')->field('nickname')->find($uid);
            if ($info !== false && $info['nickname']) {
                $nickname = $info['nickname'];
                $name = $list[$key] = $nickname;
                /* 缓存用户 */
                $count = count($list);
                $max   = config('user_max_cache');
                while ($count-- > $max) {
                    array_shift($list);
                }
                cache('sys_user_nickname_list', $list);
            } else {
                $name = '';
            }
        }
        return $name;
    }
}

if (!function_exists('action_log')) {
    /**
     * 记录行为日志，并执行该行为的规则
     * @param null $action 行为标识
     * @param null $model 触发行为的模型名
     * @param string $record_id 触发行为的记录id
     * @param null $user_id 执行行为的用户id
     * @param string $details 详情
     * @author huajie <banhuajie@163.com>
     * @alter 蔡伟明 <314013107@qq.com>
     * @return bool|string
     */
    function action_log($action = null, $model = null, $record_id = '', $user_id = null, $details = '')
    {
        // 参数检查
        if(empty($action) || empty($model)){
            return '参数不能为空';
        }
        if(empty($user_id)){
            $user_id = is_signin();
        }
        if (strpos($action, '.')) {
            list($module, $action) = explode('.', $action);
        } else {
            $module = request()->module();
        }

        // 查询行为,判断是否执行
        $action_info = model('admin/action')->where('module', $module)->getByName($action);
        if($action_info['status'] != 1){
            return '该行为被禁用或删除';
        }

        // 插入行为日志
        $data = [
            'action_id'   => $action_info['id'],
            'user_id'     => $user_id,
            'action_ip'   => get_client_ip(1),
            'model'       => $model,
            'record_id'   => $record_id,
            'create_time' => request()->time()
        ];

        // 解析日志规则,生成日志备注
        if(!empty($action_info['log'])){
            if(preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)){
                $log = [
                    'user'    => $user_id,
                    'record'  => $record_id,
                    'model'   => $model,
                    'time'    => request()->time(),
                    'data'    => ['user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => request()->time()],
                    'details' => $details
                ];

                $replace = [];
                foreach ($match[1] as $value){
                    $param = explode('|', $value);
                    if(isset($param[1])){
                        $replace[] = call_user_func($param[1], $log[$param[0]]);
                    }else{
                        $replace[] = $log[$param[0]];
                    }
                }

                $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
            }else{
                $data['remark'] = $action_info['log'];
            }
        }else{
            // 未定义日志规则，记录操作url
            $data['remark'] = '操作url：'.$_SERVER['REQUEST_URI'];
        }

        // 保存日志
        model('admin/log')->insert($data);

        if(!empty($action_info['rule'])){
            // 解析行为
            $rules = parse_action($action, $user_id);
            // 执行行为
            $res = execute_action($rules, $action_info['id'], $user_id);
            if (!$res) {
                return '执行行为失败';
            }
        }

        return true;
    }
}

if (!function_exists('parse_action')) {
    /**
     * 解析行为规则
     * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
     * 规则字段解释：table->要操作的数据表，不需要加表前缀；
     *            field->要操作的字段；
     *            condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
     *            rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
     *            cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
     *            max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
     * 单个行为后可加 ； 连接其他规则
     * @param string $action 行为id或者name
     * @param int $self 替换规则里的变量为执行用户的id
     * @author huajie <banhuajie@163.com>
     * @alter 蔡伟明 <314013107@qq.com>
     * @return boolean|array: false解析出错 ， 成功返回规则数组
     */
    function parse_action($action = null, $self){
        if(empty($action)){
            return false;
        }

        // 参数支持id或者name
        if(is_numeric($action)){
            $map = ['id' => $action];
        }else{
            $map = ['name' => $action];
        }

        // 查询行为信息
        $info = model('admin/action')->where($map)->find();
        if(!$info || $info['status'] != 1){
            return false;
        }

        // 解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
        $rule   = $info['rule'];
        $rule   = str_replace('{$self}', $self, $rule);
        $rules  = explode(';', $rule);
        $return = [];
        foreach ($rules as $key => &$rule){
            $rule = explode('|', $rule);
            foreach ($rule as $k => $fields){
                $field = empty($fields) ? array() : explode(':', $fields);
                if(!empty($field)){
                    $return[$key][$field[0]] = $field[1];
                }
            }
            // cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
            if (!isset($return[$key]['cycle']) || !isset($return[$key]['max'])) {
                unset($return[$key]['cycle'],$return[$key]['max']);
            }
        }

        return $return;
    }
}

if (!function_exists('execute_action')) {
    /**
     * 执行行为
     * @param array|bool $rules 解析后的规则数组
     * @param int $action_id 行为id
     * @param array $user_id 执行的用户id
     * @author huajie <banhuajie@163.com>
     * @alter 蔡伟明 <314013107@qq.com>
     * @return boolean false 失败 ， true 成功
     */
    function execute_action($rules = false, $action_id = null, $user_id = null){
        if(!$rules || empty($action_id) || empty($user_id)){
            return false;
        }

        $return = true;
        foreach ($rules as $rule){
            // 检查执行周期
            $map = ['action_id' => $action_id, 'user_id' => $user_id];
            $map['create_time'] = ['gt', request()->time() - intval($rule['cycle']) * 3600];
            $exec_count = model('admin/log')->where($map)->count();
            if($exec_count > $rule['max']){
                continue;
            }

            // 执行数据库操作
            $field = $rule['field'];
            $res   = Db::name($rule['table'])->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

            if(!$res){
                $return = false;
            }
        }
        return $return;
    }
}

if (!function_exists('get_location')) {
    /**
     * 获取当前位置
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    function get_location()
    {
        $location = model('admin/menu')->getLocation();
        return $location;
    }
}

if (!function_exists('packet_exists')) {
    /**
     * 查询数据包是否存在，即是否已经安装
     * @param string $name 数据包名
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function packet_exists($name = '')
    {
        if (Db::name('admin_packet')->where('name', $name)->find()) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('load_assets')) {
    /**
     * 加载静态资源
     * @param string $assets 资源名称
     * @param string $type 资源类型
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    function load_assets($assets = '', $type = 'css')
    {
        $assets_list = config('assets.'. $assets);

        $result = '';
        foreach ($assets_list as $item) {
            if ($type == 'css') {
                $result .= '<link rel="stylesheet" href="'.$item.'">';
            } else {
                $result .= '<script src="'.$item.'"></script>';
            }
        }
        return $result;
    }
}
