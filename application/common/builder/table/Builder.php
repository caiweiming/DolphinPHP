<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\common\builder\table;

use app\admin\model\Menu;
use app\common\builder\ZBuilder;
use app\user\model\Role;
use think\facade\Cache;
use think\facade\Env;

/**
 * 表格构建器
 * @package app\common\builder\table
 * @author 蔡伟明 <314013107@qq.com>
 */
class Builder extends ZBuilder
{
    /**
     * @var string 当前模型名称
     */
    private $_module = '';

    /**
     * @var string 当前控制器名称
     */
    private $_controller = '';

    /**
     * @var string 当前操作名称
     */
    private $_action = '';

    /**
     * @var string 数据表名
     */
    private $_table_name = '';

    /**
     * @var string 插件名称
     */
    private $_plugin_name = '';

    /**
     * @var string 模板路径
     */
    private $_template = '';

    /**
     * @var array 要替换的右侧按钮内容
     */
    private $_replace_right_buttons = [];

    /**
     * @var bool 有分页数据
     */
    private $_has_pages = true;

    /**
     * @var array 存储字段筛选选项
     */
    private $_filter_options = [];

    /***
     * @var array 存储字段筛选列表
     */
    private $_filter_list = [];

    /**
     * @var array 存储字段筛选类型
     */
    private $_filter_type = [];

    /**
     * @var array 列名
     */
    private $_field_name = [];

    /**
     * @var array 存储搜索框数据
     */
    private $_search = [];

    /**
     * @var array 顶部下拉菜单默认选项集合
     */
    private $_select_list_default = [];

    /**
     * @var array 行class
     */
    private $_tr_class = [];

    /**
     * @var int 前缀模式:0-不含表前缀，1-含表前缀，2-使用模型
     */
    private $_prefix = 1;

    /**
     * @var mixed 表格原始数据
     */
    private $data;

    /**
     * @var array 使用原始数据的字段
     */
    protected $rawField = [];

    /**
     * @var array 模板变量
     */
    private $_vars = [
        'page_title'         => '',       // 页面标题
        'page_tips'          => '',       // 页面提示
        'tips_type'          => '',       // 提示类型
        'tab_nav'            => [],       // 页面Tab导航
        'hide_checkbox'      => false,    // 是否隐藏第一列多选
        'extra_html'         => '',       // 额外HTML代码
        'extra_js'           => '',       // 额外JS代码
        'extra_css'          => '',       // 额外CSS代码
        'order_columns'      => [],       // 需要排序的列表头
        'filter_columns'     => [],       // 需要筛选功能的列表头
        'filter_map'         => [],       // 字段筛选的排序条件
        '_field_display'     => [],       // 字段筛选的默认选项
        '_filter_content'    => [],       // 字段筛选的默认选中值
        '_filter'            => [],       // 字段筛选的默认字段名
        'top_buttons'        => [],       // 顶部栏按钮
        'right_buttons'      => [],       // 表格右侧按钮
        'search'             => [],       // 搜索参数
        'search_button'      => false,    // 搜索按钮
        'columns'            => [],       // 表格列集合
        'pages'              => '',       // 分页数据
        'row_list'           => [],       // 表格数据列表
        '_page_info'         => '',       // 分页信息
        'primary_key'        => 'id',     // 表格主键名称
        '_table'             => '',       // 表名
        'js_list'            => [],       // js文件名
        'css_list'           => [],       // css文件名
        'validate'           => '',       // 快速编辑的验证器名
        '_js_files'          => [],       // js文件
        '_css_files'         => [],       // css文件
        '_select_list'       => [],       // 顶部下拉菜单列表
        '_filter_time'       => [],       // 时间段筛选
        'empty_tips'         => '暂无数据', // 没有数据时的提示信息
        '_search_area'       => [],       // 搜索区域
        '_search_area_url'   => '',       // 搜索区域url
        '_search_area_op'    => '',       // 搜索区域匹配方式
        'builder_height'     => 'fixed',  // 表格高度
        'fixed_right_column' => 0,        // 固定右边列数量
        'fixed_left_column'  => 0,        // 固定左边列数量
        'column_width'       => [],       // 列宽度
        'column_hide'        => [],       // 隐藏列
    ];

    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function initialize()
    {
        $this->_module     = $this->request->module();
        $this->_controller = parse_name($this->request->controller());
        $this->_action     = $this->request->action();
        $this->_table_name = strtolower($this->_module.'_'.$this->_controller);
        $this->_template   = Env::get('app_path'). 'common/builder/table/layout.html';

        // 默认加载快速编辑所需js和css
        $this->_vars['_js_files'][]  = 'editable_js';
        $this->_vars['_css_files'][] = 'editable_css';
    }

    /**
     * 模板变量赋值
     * @param mixed $name 要显示的模板变量
     * @param string $value 变量的值
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->_vars = array_merge($this->_vars, $name);
        } else {
            $this->_vars[$name] = $value;
        }
        return $this;
    }

    /**
     * 设置页面标题
     * @param string $page_title 页面标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPageTitle($page_title = '')
    {
        if ($page_title != '') {
            $this->_vars['page_title'] = $page_title;
        }
        return $this;
    }

    /**
     * 隐藏第一列多选框
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function hideCheckbox($status = true)
    {
        $this->_vars['hide_checkbox'] = $status;
        return $this;
    }

    /**
     * 设置页面提示
     * @param string $tips 提示信息
     * @param string $type 提示类型：success/info/warning/danger，默认info
     * @param string $pos 提示位置：top,button
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPageTips($tips = '', $type = 'info', $pos = 'top')
    {
        if ($tips != '') {
            $this->_vars['page_tips_'.$pos] = $tips;
            $this->_vars['tips_type'] = $type != '' ? trim($type) : 'info';
        }
        return $this;
    }

    /**
     * 添加顶部下拉框
     * @param string $name 表单名，即name值
     * @param string $title 第一个下来菜单项标题，不写则不显示
     * @param array $options 表单项内容，传递数组形式，如：array([2015] => '2015年', [2016] => '2016年')
     * @param string $default 默认选项，初始化时，默认选中的菜单项
     * @param string $ignore 生成url时，需要忽略的参数，用于有父子关系的下拉菜单，比如省份和地区，省份URL不应该带有地区参数的，
     *                       所以可以在定义省份下拉菜单时，传入地区的下拉列表名，
     *                       如需忽略多个参数，用逗号隔开
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTopSelect($name = '', $title = '', $options = [], $default = '', $ignore = '')
    {
        if ($name != '') {
            $this->_vars['_select_list'][$name] = [
                'name'    => $name,
                'title'   => $title,
                'options' => $options,
                'ignore'  => $ignore,
                'current' => '',
            ];
            if ($default != '') {
                $this->_select_list_default[$name] = $default;
            }
            $this->_vars['_js_files'][]  = 'select2_js';
            $this->_vars['_css_files'][] = 'select2_css';
            $this->_vars['_js_init'][]   = 'select2';
        }
        return $this;
    }

    /**
     * 添加表头排序
     * @param array|string $column 表头排序字段，多个以逗号隔开
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addOrder($column = [])
    {
        if (!empty($column)) {
            $column = is_array($column) ? $column : explode(',', $column);
            $this->_vars['order_columns'] = array_merge($this->_vars['order_columns'], $column);
        }
        return $this;
    }

    /**
     * 添加表头筛选
     * @param array|string $columns 表头筛选字段，多个以逗号隔开
     * @param array $options 选项，供有些字段值需要另外显示的，比如字段值是数字，但显示的时候是其他文字。
     * @param array $default 默认选项，['字段名' => '字段值,字段值...']
     * @param string $type 筛选类型，默认为CheckBox，也可以是radio
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addFilter($columns = [], $options = [], $default = [], $type = 'checkbox')
    {
        if (!empty($columns)) {
            $columns = is_array($columns) ? $columns : explode(',', $columns);
            $this->_vars['filter_columns'] = array_merge($this->_vars['filter_columns'], $columns);
            // 存储对应的字段选项
            if (!empty($options) && is_array($options)) {
                foreach ($columns as $key => $column) {
                    if (is_numeric($key)) {
                        if (strpos($column, '.')) {
                            $column = explode('.', $column)[1];
                        }
                        cache('filter_options_'.$column, $options);
                        $this->_filter_options[$column] = 'filter_options_'.$column;
                    } else {
                        cache('filter_options_'.$key, $options);
                        $this->_filter_options[$key] = 'filter_options_'.$key;
                    }
                }
            }
            // 处理默认选项和值
            if (!empty($default) && is_array($default)) {
                foreach ($default as $display => $content) {
                    if (strpos($display, '|')) {
                        list($display, $filter) = explode('|', $display);
                    } else {
                        $filter = $display;
                    }
                    if (strpos($display, '.')) {
                        $display = explode('.', $display)[1];
                    }
                    $this->_vars['_field_display'][]  = $display;
                    $this->_vars['_filter'][]         = $filter;
                    $this->_vars['_filter_content'][] = is_array($content) ? implode(',', $content) : $content;
                }
            }
            // 处理筛选类型
            foreach ($columns as $column) {
                $this->_filter_type[$column] = $type;
            }
        }
        return $this;
    }

    /**
     * 添加表头筛选列表
     * @param string $field 表头筛选字段
     * @param array $list 需要显示的列表
     * @param string $default 默认值，一维数组或逗号隔开的字符串
     * @param string $type 筛选类型，默认为CheckBox，也可以是radio
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addFilterList($field = '', $list = [], $default = '', $type = 'checkbox')
    {
        if ($field != '' && !empty($list)) {
            $this->_vars['filter_columns'][] = $field;
            $this->_filter_type[$field] = $type;
            $this->_filter_list[$field] = md5('_filter_list_'.$this->_module.'_'.$this->_controller.'_'.$this->_action.'_'.session('user_auth.uid').'_'.$field);
            Cache::set($this->_filter_list[$field], $list);

            // 处理默认选项和值
            if ($default != '') {
                $this->_vars['_field_display'][]  = $field;
                $this->_vars['_filter'][]         = $field;
                $this->_vars['_filter_content'][] = is_array($default) ? implode(',', $default) : $default;
            }
        }
        return $this;
    }

    /**
     * 添加表头筛选条件
     * @param string $fields 字段名，多个可以用逗号隔开
     * @param array $map 查询条件
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function addFilterMap($fields = '', $map = [])
    {
        if ($fields != '') {
            if (is_array($fields)) {
                $this->_vars['filter_map'] = array_merge($this->_vars['filter_map'], $fields);
            } else {
                $map = $this->buildFilterMap($map);
                if (strpos($fields, ',')) {
                    $fields = explode(',', $fields);
                    foreach ($fields as $field) {
                        if (isset($this->_vars['filter_map'][$field])) {
                            $this->_vars['filter_map'][$field] = array_merge($this->_vars['filter_map'][$field], $map);
                        } else {
                            $this->_vars['filter_map'][$field] = $map;
                        }
                    }
                } else {
                    if (isset($this->_vars['filter_map'][$fields])) {
                        $this->_vars['filter_map'][$fields] = array_merge($this->_vars['filter_map'][$fields], $map);
                    } else {
                        $this->_vars['filter_map'][$fields] = $map;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 组合筛选条件
     * @param string $map 筛选条件
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    private function buildFilterMap($map = '')
    {
        if (is_array($map)) return $map;

        $_map = [];
        $_filter = $this->request->param('_filter');
        $_filter = explode('|', $_filter);
        $_pos    = array_search($map, $_filter);
        if ($_pos !== false) {
            $_filter_content = $this->request->param('_filter_content');
            $_filter_content = explode('|', $_filter_content);

            if (strpos($map, '.')) {
                $_field = explode('.', $map)[1];
            } else {
                $_field = $map;
            }

            $_map[] = isset($_filter_content[$_pos]) ? [$_field, 'in', $_filter_content[$_pos]] : [$_field, 'eq', ''];
        }

        return $_map;
    }

    /**
     * 时间段过滤
     * @param string $field 字段名
     * @param string|array $date 默认的开始日期和结束日期
     * @param string|array $tips 开始日期和结束日期的提示
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTimeFilter($field = '', $date = '', $tips = '')
    {
        if ($field != '') {
            $date_start = '';
            $date_end   = '';
            $tips_start = '开始日期';
            $tips_end   = '结束日期';

            if (!empty($date)) {
                if (!is_array($date)) {
                    if (strpos($date, ',')) {
                        list($date_start, $date_end) = explode(',', $date);
                    } else {
                        $date_start = $date_end = $date;
                    }
                } else {
                    list($date_start, $date_end) = $date;
                }
            }

            if (!empty($tips)) {
                if (!is_array($tips)) {
                    if (strpos($tips, ',')) {
                        list($tips_start, $tips_end) = explode(',', $tips);
                    } else {
                        $tips_start = $tips_end = $tips;
                    }
                } else {
                    list($tips_start, $tips_end) = $tips;
                }
            }

            $this->_vars['_js_files'][]  = 'datepicker_js';
            $this->_vars['_css_files'][] = 'datepicker_css';
            $this->_vars['_js_init'][]   = 'datepicker';
            $this->_vars['_filter_time'] = [
                'field'      => $field,
                'tips_start' => $tips_start,
                'tips_end'   => $tips_end,
                'date_start' => $date_start,
                'date_end'   => $date_end,
            ];
        }
        return $this;
    }

    /**
     * 添加快捷编辑的验证器
     * @param string $validate 验证器名
     * @param string $fields 要验证的字段，多个用逗号隔开，并且在验证器中要定义该字段名对应的场景
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addValidate($validate = '', $fields = '')
    {
        if ($validate != '') {
            $this->_vars['validate']        = $validate;
            $this->_vars['validate_fields'] = $fields;
        }
        return $this;
    }

    /**
     * 替换右侧按钮
     * @param array $map 条件，格式为：['字段名' => '字段值', '字段名' => '字段值'....]
     * @param string $content 要替换的内容
     * @param null $target 要替换的目标按钮
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function replaceRightButton($map = [], $content = '', $target = null)
    {
        if (!empty($map)) {
            $maps   = [];
            $target = is_string($target) ? explode(',', $target) : $target;
            if (is_callable($map)) {
                $maps[] = [$map, $content, $target];
            } else {
                foreach ($map as $key => $value) {
                    if (is_array($value)) {
                        $op = strtolower($value[0]);
                        switch ($op) {
                            case '=':  $op = 'eq';  break;
                            case '<>': $op = 'neq'; break;
                            case '>':  $op = 'gt';  break;
                            case '<':  $op = 'lt';  break;
                            case '>=': $op = 'egt'; break;
                            case '<=': $op = 'elt'; break;
                            case 'in':
                            case 'not in':
                            case 'between':
                            case 'not between':
                                $value[1] = is_array($value[1]) ? $value[1] : explode(',', $value[1]); break;
                        }
                        $maps[] = [$key, $op, $value[1]];
                    } else {
                        $maps[] = [$key, 'eq', $value];
                    }
                }
            }

            $this->_replace_right_buttons[] = [
                'maps'    => $maps,
                'content' => $content,
                'target'  => $target
            ];
        }
        return $this;
    }

    /**
     * 自动创建新增页面
     * @param array $items 表单项
     * @param string $table 表名
     * @param string $validate 验证器名
     * @param string $auto_time 自动添加时间，默认有两个create_time和update_time
     * @param string $format 时间格式
     * @param bool $pop 弹窗显示
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function autoAdd($items = [], $table = '', $validate = '', $auto_time = '', $format = '', $pop = false)
    {
        if (!empty($items)) {
            // 默认属性
            $btn_attribute = [
                'title' => '新增',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-primary'.($pop === true ? ' pop' : ''),
                'href'  => url(
                    $this->_module.'/'.$this->_controller.'/add'
                ).($pop === true ? '?_pop=1' : ''),
            ];

            // 判断当前用户是否有权限，没有权限则不生成按钮
            if (session('user_auth.role') != 1 && substr($btn_attribute['href'], 0, 4) != 'http' && $btn_attribute['href'] != 'javascript:history.back(-1);') {
                if ($this->checkButtonAuth($btn_attribute) === false) {
                    return $this;
                }
            }

            // 缓存名称
            $cache_name = strtolower($this->_module.'/'.$this->_controller.'/add');

            // 自动插入时间
            if ($auto_time != '') {
                $auto_time = $auto_time === true ? ['create_time', 'update_time'] : explode(',', $auto_time);
            }

            // 表单缓存数据
            $form = [
                'items'     => $items,
                'table'     => $table == '' ? strtolower($this->_module . '_' . $this->_controller) : $table,
                'validate'  => $validate === true ? ucfirst($this->_controller) : $validate,
                'auto_time' => $auto_time,
                'format'    => $format,
                'go_back'   => $this->request->server('REQUEST_URI')
            ];

            // 开发模式
            if (config('develop_mode')) {
                Cache::set($cache_name, $form);
            }

            if (!Cache::get($cache_name)) {
                Cache::set($cache_name, $form);
            }

            // 添加到按钮组
            $this->_vars['top_buttons'][] = $btn_attribute;
        }
        return $this;
    }

    /**
     * 获取默认url
     * @param string $type 按钮类型：add/enable/disable/delete
     * @param array $params 参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    private function getDefaultUrl($type = '', $params = [])
    {
        $url = $this->_module.'/'.$this->_controller.'/'.$type;
        $MenuModel = new Menu();
        $menu  = $MenuModel->where('url_value', $url)->find();
        if ($menu['params'] != '') {
            $url_params = explode('&', trim($menu['params'], '&'));
            if (!empty($url_params)) {
                foreach ($url_params as $item) {
                    list($key, $value) = explode('=', $item);
                    $params[$key] = $value;
                }
            }
        }

        if (!empty($params) && config('url_common_param')) {
            $params = array_filter($params, function($v){return $v !== '';});
        }

        return $menu['url_type'] == 'module_home' ? home_url($url, $params) : url($url, $params);
    }

    /**
     * 添加一个顶部按钮
     * @param string $type 按钮类型：add/enable/disable/back/delete/custom
     * @param array $attribute 按钮属性
     * @param bool $pop 是否使用弹出框形式
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTopButton($type = '', $attribute = [], $pop = false)
    {
        if ($type == '') {
            return $this;
        }

        // 表单名，用于替换
        if (isset($attribute['table'])) {
            if (isset($attribute['prefix'])) {
                $table_token = $this->createTableToken($attribute['table'], $attribute['prefix']);
            } else {
                $table_token = $this->createTableToken($attribute['table'], $this->_prefix);
            }
        } else {
            $table_token = '__table__';
        }

        // 自定义字段
        $field = isset($attribute['field']) ? $attribute['field'] : '';

        // 这个专门为插件准备的属性，是插件名称
        $plugin_name = isset($attribute['plugin_name']) ? $attribute['plugin_name'] : $this->_plugin_name;

        switch ($type) {
            // 新增按钮
            case 'add':
                // 默认属性
                $btn_attribute = [
                    'title' => '新增',
                    'icon'  => 'fa fa-plus-circle',
                    'class' => 'btn btn-primary',
                    'href'  => $this->getDefaultUrl($type, ['plugin_name' => $plugin_name])
                ];
                break;

            // 启用按钮
            case 'enable':
                // 默认属性
                $btn_attribute = [
                    'title'       => '启用',
                    'icon'        => 'fa fa-check-circle-o',
                    'class'       => 'btn btn-success ajax-post confirm',
                    'target-form' => 'ids',
                    'href'        => $this->getDefaultUrl($type, ['_t' => $table_token, 'field' => $field])
                ];
                break;

            // 禁用按钮
            case 'disable':
                // 默认属性
                $btn_attribute = [
                    'title'       => '禁用',
                    'icon'        => 'fa fa-ban',
                    'class'       => 'btn btn-warning ajax-post confirm',
                    'target-form' => 'ids',
                    'href'        => $this->getDefaultUrl($type, ['_t' => $table_token, 'field' => $field])
                ];
                break;

            // 返回按钮
            case 'back':
                // 默认属性
                $btn_attribute = [
                    'title' => '返回',
                    'icon'  => 'fa fa-reply',
                    'class' => 'btn btn-info',
                    'href'  => 'javascript:history.back(-1);'
                ];
                break;

            // 删除按钮(不可恢复)
            case 'delete':
                // 默认属性
                $btn_attribute = [
                    'title'       => '删除',
                    'icon'        => 'fa fa-times-circle-o',
                    'class'       => 'btn btn-danger ajax-post confirm',
                    'target-form' => 'ids',
                    'href'        => $this->getDefaultUrl($type, ['_t' => $table_token])
                ];
                break;

            // 自定义按钮
            default:
                // 默认属性
                $btn_attribute = [
                    'title'       => '定义按钮',
                    'class'       => 'btn btn-default',
                    'target-form' => 'ids',
                    'href'        => 'javascript:void(0);'
                ];
                break;
        }

        // 合并自定义属性
        if ($attribute && is_array($attribute)) {
            $btn_attribute = array_merge($btn_attribute, $attribute);
        }

        // 判断当前用户是否有权限，没有权限则不生成按钮
        if (session('user_auth.role') != 1 && substr($btn_attribute['href'], 0, 4) != 'http' && $btn_attribute['href'] != 'javascript:history.back(-1);') {
            if ($this->checkButtonAuth($btn_attribute) === false) {
                return $this;
            }
        }

        // 是否为弹出框方式
        if ($pop !== false) {
            $btn_attribute['class'] .= ' pop';
            $btn_attribute['href']  .= (strpos($btn_attribute['href'], '?') ? '&' : '?').'_pop=1';
            if (is_array($pop) && !empty($pop)) {
                $btn_attribute['data-layer'] = json_encode($pop);
            }
        }

        $this->_vars['top_buttons'][] = $btn_attribute;
        return $this;
    }

    /**
     * 检查是否有按钮权限
     * @param array $btn_attribute 按钮属性
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    private function checkButtonAuth($btn_attribute = [])
    {
        if (preg_match('/\/(index.php|'.ADMIN_FILE.')\/(.*)/', $btn_attribute['href'], $match)) {
            $url_value = explode('/', $match[2]);
            if (strpos($url_value[2], '.')) {
                $url_value[2] = substr($url_value[2], 0, strpos($url_value[2], '.'));
            }
            $url_value = $url_value[0].'/'.$url_value[1].'/'.$url_value[2];
            $url_value = strtolower($url_value);
            return Role::checkAuth($url_value, true);
        }
        return true;
    }

    /**
     * 一次性添加多个顶部按钮
     * @param array|string $buttons 按钮类型
     * 例如：
     * $builder->addTopButtons('add');
     * $builder->addTopButtons('add,delete');
     * $builder->addTopButtons(['add', 'delete']);
     * $builder->addTopButtons(['add' => ['table' => '__USER__'], 'delete']);
     *
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTopButtons($buttons = [])
    {
        if (!empty($buttons)) {
            $buttons = is_array($buttons) ? $buttons : explode(',', $buttons);
            foreach ($buttons as $key => $value) {
                if (is_numeric($key)) {
                    $this->addTopButton($value);
                } else {
                    $this->addTopButton($key, $value);
                }
            }
        }
        return $this;
    }

    /**
     * 自动创建编辑页面
     * @param array $items 表单项
     * @param string $table 表名
     * @param string $validate 验证器名
     * @param string $auto_time 自动添加时间，默认有两个create_time和update_time
     * @param string $format 时间格式
     * @param bool $pop 弹窗显示
     * @param array $extra 额外参数，设置按钮样式
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function autoEdit($items = [], $table = '', $validate = '', $auto_time = '', $format = '', $pop = false, $extra = [])
    {
        if (!empty($items)) {
            // 按钮样式
            $btn_style = array_merge(config('zbuilder.right_button'), $extra);

            // 默认属性
            $btn_attribute = [
                'title' => '编辑',
                'icon'  => 'fa fa-pencil',
                'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'].($pop === true ? ' pop' : ''),
                'href'  => url(
                    $this->_module.'/'.$this->_controller.'/edit',
                    ['id' => '__id__']
                ),
                'target' => '_self',
                '_style' => $btn_style
            ];

            // 是否弹窗显示
            if ($pop === true) {
                $btn_attribute['href'] .= (strpos($btn_attribute['href'], '?') ? '&' : '?').'_pop=1';
            }

            // 判断当前用户是否有权限，没有权限则不生成按钮
            if (session('user_auth.role') != 1 && substr($btn_attribute['href'], 0, 4) != 'http') {
                if ($this->checkButtonAuth($btn_attribute) === false) {
                    return $this;
                }
            }

            // 缓存名称
            $cache_name = strtolower($this->_module.'/'.$this->_controller.'/edit');

            // 自动插入时间
            if ($auto_time != '') {
                $auto_time = $auto_time === true ? ['update_time'] : explode(',', $auto_time);
            }

            // 表单缓存数据
            $form = [
                'items'     => $items,
                'table'     => $table == '' ? strtolower($this->_module . '_' . $this->_controller) : $table,
                'validate'  => $validate === true ? ucfirst($this->_controller) : $validate,
                'auto_time' => $auto_time,
                'format'    => $format,
                'go_back'   => $this->request->server('REQUEST_URI')
            ];

            // 开发模式
            if (config('develop_mode')) {
                Cache::set($cache_name, $form);
            }

            if (!Cache::get($cache_name)) {
                Cache::set($cache_name, $form);
            }

            // 添加到按钮组
            $this->_vars['right_buttons'][] = $btn_attribute;
        }
        return $this;
    }

    /**
     * 创建表名Token
     * @param string $table 表名
     * @param int $prefix 前缀类型：0使用Db类(不添加表前缀)，1使用Db类(添加表前缀)，2使用模型
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|string
     */
    private function createTableToken($table = '', $prefix = 1)
    {
        $data = [
            'table'      => $table, // 表名或模型名
            'prefix'     => $prefix,
            'module'     => $this->_module,
            'controller' => $this->_controller,
            'action'     => $this->_action,
        ];

        $table_token = substr(sha1($this->_module.'-'.$this->_controller.'-'.$this->_action.'-'.$table), 0, 8);
        session($table_token, $data);
        return $table_token;
    }

    /**
     * 添加一个右侧按钮
     * @param string $type 按钮类型：edit/enable/disable/delete/custom
     * @param array $attribute 按钮属性
     * @param bool $pop 是否使用弹出框形式
     * @param array $extra 扩展参数，设置按钮样式
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addRightButton($type = '', $attribute = [], $pop = false, $extra = [])
    {
        if ($type == '') {
            return $this;
        }

        // 表单名，用于替换
        if (isset($attribute['table'])) {
            if (isset($attribute['prefix'])) {
                $table_token = $this->createTableToken($attribute['table'], $attribute['prefix']);
            } else {
                $table_token = $this->createTableToken($attribute['table'], $this->_prefix);
            }
        } else {
            $table_token = '__table__';
        }

        // 这个专门为插件准备的属性，是插件名称
        $plugin_name = isset($attribute['plugin_name']) ? $attribute['plugin_name'] : $this->_plugin_name;
        // 自定义字段名
        $field = isset($attribute['field']) ? $attribute['field'] : '';

        // 按钮样式
        $btn_style = array_merge(config('zbuilder.right_button'), $extra);

        switch ($type) {
            // 编辑按钮
            case 'edit':
                // 默认属性
                $btn_attribute = [
                    'title' => '编辑',
                    'icon'  => 'fa fa-pencil',
                    'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'],
                    'href'  => $this->getDefaultUrl($type, ['id' => '__id__', 'plugin_name' => $plugin_name]),
                    'target' => '_self'
                ];
                break;

            // 启用按钮
            case 'enable':
                // 默认属性
                $btn_attribute = [
                    'title' => '启用',
                    'icon'  => 'fa fa-check',
                    'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'].' ajax-get confirm',
                    'href'  => $this->getDefaultUrl($type, ['ids' => '__id__', '_t' => $table_token, 'field' => $field])
                ];
                break;

            // 禁用按钮
            case 'disable':
                // 默认属性
                $btn_attribute = [
                    'title' => '禁用',
                    'icon'  => 'fa fa-ban',
                    'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'].' ajax-get confirm',
                    'href'  => $this->getDefaultUrl($type, ['ids' => '__id__', '_t' => $table_token, 'field' => $field])
                ];
                break;

            // 删除按钮(不可恢复)
            case 'delete':
                // 默认属性
                $btn_attribute = [
                    'title' => '删除',
                    'icon'  => 'fa fa-times',
                    'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'].' ajax-get confirm',
                    'href'  => $this->getDefaultUrl($type, ['ids' => '__id__', '_t' => $table_token])
                ];
                break;

            // 自定义按钮
            default:
                // 默认属性
                $btn_attribute = [
                    'title' => '自定义按钮',
                    'icon'  => 'fa fa-smile-o',
                    'class' => 'btn btn-'.$btn_style['size'].' btn-'.$btn_style['style'],
                    'href'  => 'javascript:void(0);'
                ];
                break;
        }

        // 合并自定义属性
        if ($attribute && is_array($attribute)) {
            $btn_attribute = array_merge($btn_attribute, $attribute);
        }

        // 判断当前用户是否有权限，没有权限则不生成按钮
        if (session('user_auth.role') != 1 && substr($btn_attribute['href'], 0, 4) != 'http') {
            if ($this->checkButtonAuth($btn_attribute) === false) {
                return $this;
            }
        }

        // 是否为弹出框方式
        if ($pop !== false) {
            $btn_attribute['class'] .= ' pop';
            $btn_attribute['href']  .= (strpos($btn_attribute['href'], '?') ? '&' : '?').'_pop=1';
            if (is_array($pop) && !empty($pop)) {
                $btn_attribute['data-layer'] = json_encode($pop);
            }
        }

        // 添加按钮样式
        $btn_attribute['_style'] = $btn_style;

        // 添加按钮标签
        $btn_attribute['_tag'] = $type;

        $this->_vars['right_buttons'][] = $btn_attribute;
        return $this;
    }

    /**
     * 一次性添加多个右侧按钮
     * @param array|string $buttons 按钮类型
     * 例如：
     * $builder->addRightButtons('edit');
     * $builder->addRightButtons('edit,delete');
     * $builder->addRightButtons(['edit', 'delete']);
     * $builder->addRightButtons(['edit' => ['table' => 'admin_user'], 'delete']);
     *
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addRightButtons($buttons = [])
    {
        if (!empty($buttons)) {
            $buttons = is_array($buttons) ? $buttons : explode(',', $buttons);
            foreach ($buttons as $key => $value) {
                if (is_numeric($key)) {
                    $this->addRightButton($value);
                } else {
                    $this->addRightButton($key, $value);
                }
            }
        }
        return $this;
    }

    /**
     * 设置表格高度
     * @param string $height 高度：fixed/auto/具体数值
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     * @since 1.3.0
     */
    public function setHeight($height = 'fixed')
    {
        $this->_vars['builder_height'] = $height;
        return $this;
    }

    /**
     * 固定左侧列数
     * @param int $num 数量
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function fixedRight($num = 0)
    {
        $this->_vars['fixed_right_column'] = $num;
        return $this;
    }

    /**
     * 固定右侧列数
     * @param int $num 数量
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function fixedLeft($num = 0)
    {
        $this->_vars['fixed_left_column'] = $num;
        return $this;
    }

    /**
     * 设置搜索参数
     * @param array $fields 参与搜索的字段
     * @param string $placeholder 提示符
     * @param string $url 提交地址
     * @param null $search_button 提交按钮
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setSearch($fields = [], $placeholder = '', $url = '', $search_button = null)
    {
        if (!empty($fields)) {
            $this->_search = [
                'fields'      => is_string($fields) ? explode(',', $fields) : $fields,
                'placeholder' => $placeholder,
                'url'         => $url,
            ];

            $this->_vars['search_button'] = $search_button !== null ? $search_button : config('zbuilder.search_button');
        }
        return $this;
    }

    /**
     * 设置搜索区域
     * @param array $items
     * @param string $url
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setSearchArea($items = [], $url = '')
    {
        if (!empty($items)) {
            $_op = [];
            $_defaults = [];
            $_s  = $this->request->param('_s', '');
            if ($_s != '') {
                $_s = explode('|', $_s);
                foreach ($_s as $v) {
                    list($field, $value) = explode('=', $v);
                    $_defaults[$field] = $value;
                }
            }

            foreach ($items as &$item) {
                $layout = 3;

                if (strpos($item[0], ':')) {
                    list($item[0], $layout) = explode(':', $item[0]);
                }

                $type    = $item[0];
                $name    = $item[1];
                $label   = $item[2];
                $op      = isset($item[3]) ? $item[3] : 'eq';
                $item[4] = isset($_defaults[$name]) ? $_defaults[$name] : (isset($item[4]) ? $item[4] : ''); // 默认值
                $item[5] = isset($item[5]) ? $item[5] : [];

                switch ($op) {
                    case '=':  $op = 'eq';  break;
                    case '<>': $op = 'neq'; break;
                    case '>':  $op = 'gt';  break;
                    case '<':  $op = 'lt';  break;
                    case '>=': $op = 'egt'; break;
                    case '<=': $op = 'elt'; break;
                    default:
                        $op = $op == '' ? 'eq' : $op;
                }

                switch ($type) {
                    case 'text':
                        break;
                    case 'select':
                        $this->_vars['_js_files'][]  = 'select2_js';
                        $this->_vars['_css_files'][] = 'select2_css';
                        $this->_vars['_js_init'][]   = 'select2';
                        break;
                    case 'daterange':
                        $this->_vars['_js_files'][]  = 'moment_js';
                        $this->_vars['_js_files'][]  = 'daterangepicker_js';
                        $this->_vars['_css_files'][] = 'daterangepicker_css';
                        $this->_vars['_js_init'][]   = 'daterangepicker';
                        $op = $op == 'eq' ? 'between time' : $op . ' time';

                        $params = [];
                        if (!empty($item[5])) {
                            foreach ($item[5] as $key => $param) {
                                $params[] = 'data-'.strtolower($key).'="'.$param.'"';
                            }
                        }
                        $item[5] = implode(' ', $params);
                        break;
                    default:

                }

                $_op[] = $name.'='.strtolower($op);
                $this->_vars['_search_area_layout'][$name] = $layout;
            }

            $this->_vars['_search_area_op']  = implode('|', $_op);
            $this->_vars['_search_area']     = $items;
            $this->_vars['_search_area_url'] = $url == '' ? $this->request->baseUrl(true) : $url;
        }
        return $this;
    }

    /**
     * 引入模块js文件
     * @param string $files_name js文件名，多个文件用逗号隔开
     * @param string $module 指定模块
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function js($files_name = '', $module = '')
    {
        if ($files_name != '') {
            $this->loadFile('js', $files_name, $module);
        }
        return $this;
    }

    /**
     * 引入模块css文件
     * @param string $files_name css文件名，多个文件用逗号隔开
     * @param string $module 指定模块
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function css($files_name = '', $module = '')
    {
        if ($files_name != '') {
            $this->loadFile('css', $files_name, $module);
        }
        return $this;
    }

    /**
     * 引入css或js文件
     * @param string $type 类型：css/js
     * @param string $files_name 文件名，多个用逗号隔开
     * @param string $module 指定模块
     * @author caiweiming <314013107@qq.com>
     */
    private function loadFile($type = '', $files_name = '', $module = '')
    {
        if ($files_name != '') {
            $module = $module == '' ? $this->_module : $module;
            if (!is_array($files_name)) {
                $files_name = explode(',', $files_name);
            }
            foreach ($files_name as $item) {
                if (strpos($item, '/')) {
                    $this->_vars[$type.'_list'][] = PUBLIC_PATH. 'static/'. $item.'.'.$type;
                } else {
                    $this->_vars[$type.'_list'][] = PUBLIC_PATH. 'static/'. $module .'/'.$type.'/'.$item.'.'.$type;
                }
            }
        }
    }

    /**
     * 设置数据库表名
     * @param string $table 数据库表名，不含前缀，如果为true则使用模型方式
     * @param int $prefix 前缀类型：0使用Db类(不添加表前缀)，1使用Db类(添加表前缀)，2使用模型
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTableName($table = '', $prefix = 1)
    {
        if ($table === true) {
            $this->_prefix     = 2;
            $this->_table_name = strtolower($this->_module.'/'.$this->_controller);
        } else {
            $this->_prefix = $prefix === true ? 2 : $prefix;

            if ($this->_prefix == 2) {
                $this->_table_name = strpos($table, '/') ? $table : strtolower($this->_module.'/'.$table);
            } else {
                $this->_table_name = $table;
            }
        }
        return $this;
    }

    /**
     * 设置插件名称（此方法只供制作插件时用）
     * @param string $plugin_name 插件名
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPluginName($plugin_name = '')
    {
        if ($plugin_name != '') {
            $this->_plugin_name = $plugin_name;
        }
        return $this;
    }

    /**
     * 添加一列
     * @param string $name 字段名称
     * @param string $title 列标题
     * @param string $type 单元格类型
     * @param string $default 默认值
     * @param string $param 额外参数
     * @param string $class css类名
     * @param string $extra 扩展参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addColumn($name = '', $title = '', $type = '', $default = '', $param = '', $class = '', $extra = '')
    {
        $field = $name;
        $table = '';

        // 判断是否有字段别名
        if (strpos($name, '|')) {
            list($name, $field) = explode('|', $name);
            // 判断是否有表名
            if (strpos($field, '.')) {
                list($table, $field) = explode('.', $field);
            }
        }

        $column = [
            'name'    => $name,
            'title'   => $title,
            'type'    => $type,
            'default' => $default,
            'param'   => $param,
            'class'   => $class,
            'extra'   => $extra,
            'field'   => $field,
            'table'   => $table,
        ];

        $args   = array_slice(func_get_args(), 7);
        $column = array_merge($column, $args);

        $this->_vars['columns'][] = $column;
        $this->_field_name[$name] = $title;
        return $this;
    }

    /**
     * 一次性添加多列
     * @param array $columns 数据列
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function addColumns($columns = [])
    {
        if (!empty($columns)) {
            foreach ($columns as $column) {
                call_user_func_array([$this, 'addColumn'], $column);
            }
        }
        return $this;
    }

    /**
     * 设置列宽
     * @param string $column 列名，即字段名
     * @param int $width 宽度，默认为100
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setColumnWidth($column = '', $width = 100)
    {
        if ($column != '') {
            if (is_array($column)) {
                foreach ($column as $field => $width) {
                    $this->_vars['column_width'][$field] = $width;
                }
            } else {
                if (strpos($column, ',')) {
                    $columns = explode(',', $column);
                    foreach ($columns as $column) {
                        $this->_vars['column_width'][$column] = $width;
                    }
                } else {
                    $this->_vars['column_width'][$column] = $width;
                }
            }
        }
        return $this;
    }

    /**
     * 隐藏列
     * @param string $column 列名，即字段名
     * @param string $screen 屏幕，xs/sm/md/lg
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function hideColumn($column = '', $screen = '')
    {
        if ($column != '') {
            if (is_array($column)) {
                foreach ($column as $field => $screen) {
                    $screens = is_array($screen) ? $screen : explode(',', $screen);
                    foreach ($screens as $key => $value) {
                        $screens[$key] = 'hidden-'.$value;
                    }
                    $screens = implode(' ', $screens);

                    $this->_vars['column_hide'][$field] = $screens;
                }
            } else {
                $screens = is_array($screen) ? $screen : explode(',', $screen);
                foreach ($screens as &$screen) {
                    $screen = 'hidden-'.$screen;
                }
                $screens = implode(' ', $screens);

                if (strpos($column, ',')) {
                    $columns = explode(',', $column);
                    foreach ($columns as $column) {
                        $this->_vars['column_hide'][$column] = isset($this->_vars['column_hide'][$column]) ?
                            $this->_vars['column_hide'][$column]. ' ' . $screen :
                            $screens;
                    }
                } else {
                    $this->_vars['column_hide'][$column] = isset($this->_vars['column_hide'][$column]) ?
                        $this->_vars['column_hide'][$column]. ' ' . $screen :
                        $screens;
                }
            }
        }
        return $this;
    }

    /**
     * 设置表格数据列表
     * @param array|object $row_list 表格数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setRowList($row_list = null)
    {
        if ($row_list !== null) {
            // 原始表格数据
            $this->data = $row_list;
            // 转为数组后的表格数据
            $this->_vars['row_list'] = $this->toArray($row_list);
            if ($row_list instanceof \think\paginator) {
                $this->_vars['_page_info'] = $row_list;
                // 设置分页
                $this->setPages($row_list->render());
            }
        }
        if (empty($this->_vars['row_list'])) {
            $params = $this->request->param();
            if (isset($params['page'])) {
                unset($params['page']);
                $url = url($this->_module.'/'.$this->_controller.'/'.$this->_action).'?'.http_build_query($params);
                $this->redirect($url);
            }
        }
        return $this;
    }

    /**
     * 将表格数据转换为纯数组
     * @param array|object $row_list 数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    private function toArray($row_list)
    {
        if ($row_list instanceof \think\paginator) {
            return $row_list->toArray()['data'];
        } elseif ($row_list instanceof \think\model\Collection) {
            return $row_list->toArray();
        } else {
            return $row_list;
        }
    }

    /**
     * 获取原始数据
     * @param string $index 索引
     * @param string $field 字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    private function getData($index = '', $field = '')
    {
        if (is_object($this->data) && is_object(current($this->data->getIterator()))) {
            try {
                $result = $this->data[$index]->getData($field);
            } catch (\Exception $e) {
                $result = isset($this->data[$index][$field]) ? $this->data[$index][$field] : '';
            }
            return $result;
        } else {
            return isset($this->data[$index][$field]) ? $this->data[$index][$field] : '';
        }
    }

    /**
     * 设置需要使用原始数据的字段
     * @param string|array $field 字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function raw($field = '')
    {
        if (is_array($field)) {
            $this->rawField = array_merge($this->rawField, $field);
        } else {
            $this->rawField = array_merge($this->rawField, explode(',', $field));
        }
        return $this;
    }

    /**
     * 设置表格主键
     * @param string $key 主键名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPrimaryKey($key = '')
    {
        if ($key != '') {
            $this->_vars['primary_key'] = $key;
        }
        return $this;
    }

    /**
     * 设置Tab按钮列表
     * @param array $tab_list Tab列表  ['title' => '标题', 'href' => 'http://www.dolphinphp.com']
     * @param string $curr_tab 当前tab
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTabNav($tab_list = [], $curr_tab = '')
    {
        if (!empty($tab_list)) {
            $this->_vars['tab_nav'] = [
                'tab_list' => $tab_list,
                'curr_tab' => $curr_tab,
            ];
        }
        return $this;
    }

    /**
     * 设置分页
     * @param string $pages 分页数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPages($pages = '')
    {
        if ($pages != '') {
            $this->_vars['pages'] = $pages;
        }
        return $this;
    }

    /**
     * 设置为无分页
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function noPages()
    {
        $this->_has_pages = false;
        return $this;
    }

    /**
     * 设置额外代码
     * @param string $extra_html 额外代码
     * @param string $tag 标记
     * @author 蔡伟明 <314013107@qq.com>
     * @alter 小乌 <82950492@qq.com>
     * @return $this
     */
    public function setExtraHtml($extra_html = '', $tag = '')
    {
        if ($extra_html != '') {
            $tag != '' && $tag = '_'.$tag;
            $this->_vars['extra_html'.$tag] = $extra_html;
        }
        return $this;
    }

    /**
     * 通过文件设置额外代码
     * @param string $template 模板文件名
     * @param string $tag 标记
     * @param array $vars 模板输出变量
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setExtraHtmlFile($template = '', $tag = '', $vars = [])
    {
        $template = $template == '' ? $this->_action : $template;
        $file = Env::get('app_path'). $this->_module.'/view/admin/'.$this->_controller.'/'.$template.'.html';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = $this->view->display($content, $vars);
        } else {
            $content = '模板文件不存在：'.$file;
        }

        $tag != '' && $tag = '_'.$tag;
        $this->_vars['extra_html'.$tag] = $content;

        return $this;
    }

    /**
     * 设置额外JS代码
     * @param string $extra_js 额外JS代码
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setExtraJs($extra_js = '')
    {
        if ($extra_js != '') {
            $this->_vars['extra_js'] = $extra_js;
        }
        return $this;
    }

    /**
     * 设置额外CSS代码
     * @param string $extra_css 额外CSS代码
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setExtraCss($extra_css = '')
    {
        if ($extra_css != '') {
            $this->_vars['extra_css'] = $extra_css;
        }
        return $this;
    }

    /**
     * 设置页面模版
     * @param string $template 模版
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTemplate($template = '')
    {
        if ($template != '') {
            $this->_template = $template;
        }
        return $this;
    }

    /**
     * 列class
     * @param string $class class名
     * @param mixed $field 字段名
     * @param null $op 表达式
     * @param null $condition 查询条件
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTrClass($class = '', $field, $op = null, $condition = null)
    {
        if ($class != '') {
            if (is_callable($field)) {
                $args = array_slice(func_get_args(), 2);
                $this->_tr_class[$class][] = [$field, $args];
            } elseif (!is_null($op)) {
                $op = strtolower($op);
                if (is_null($condition)) {
                    $this->_tr_class[$class][] = [$field, 'eq', $op];
                } else {
                    switch ($op) {
                        case '=':  $op = 'eq';  break;
                        case '<>': $op = 'neq'; break;
                        case '>':  $op = 'gt';  break;
                        case '<':  $op = 'lt';  break;
                        case '>=': $op = 'egt'; break;
                        case '<=': $op = 'elt'; break;
                        case 'in':
                        case 'not in':
                        case 'between':
                        case 'not between':
                            $condition = is_array($condition) ? $condition : explode(',', $condition); break;
                    }

                    $this->_tr_class[$class][] = [$field, $op, $condition];
                }
            }
        }

        return $this;
    }

    /**
     * 编译HTML属性
     * @param array $attr 要编译的数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|string
     */
    private function compileHtmlAttr($attr = []) {
        $result = [];
        if ($attr) {
            foreach ($attr as $key => &$value) {
                if ($key == 'title') {
                    $value = trim(htmlspecialchars(strip_tags(trim($value))));
                } else {
                    $value = htmlspecialchars($value);
                }
                array_push($result, "$key=\"$value\"");
            }
        }
        return implode(' ', $result);
    }

    /**
     * 编译表格数据row_list的值
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function compileRows()
    {
        foreach ($this->_vars['row_list'] as $key => &$row) {
            // 处理行class
            if ($this->_tr_class) {
                $_tr_class = $this->parseTrClass($row);

                if (!empty($_tr_class)) {
                    $row['_tr_class'] = implode(' ', $_tr_class);
                }
            }

            // 编译右侧按钮
            if ($this->_vars['right_buttons']) {
                // 默认给列添加个空的右侧按钮
                if (!isset($row['right_button'])) {
                    $row['right_button'] = '';
                }

                foreach ($this->_vars['right_buttons'] as $index => $button) {
                    // 处理按钮替换
                    if (!empty($this->_replace_right_buttons)) {
                        foreach ($this->_replace_right_buttons as $replace_right_button) {
                            // 是否能匹配到条件
                            $_button_match = true;
                            foreach ($replace_right_button['maps'] as $condition) {
                                if (is_string($condition[0])) {
                                    if (!isset($row[$condition[0]])) {
                                        $_button_match = false; continue;
                                    }
                                    $_button_match = $this->parseCondition($row, $condition) ? $_button_match : false;
                                } elseif (is_callable($condition[0])) {
                                    $_button_match = call_user_func($condition[0], $row) ? $_button_match : false;
                                }
                            }

                            // 替换按钮内容支持数据变量
                            if ($replace_right_button['content'] != '') {
                                if (preg_match_all('/__(.*?)__/', $replace_right_button['content'], $matches)) {
                                    $replace_to = [];
                                    $pattern    = [];
                                    foreach ($matches[1] as $match) {
                                        $pattern[]    = '/__'. $match .'__/i';
                                        $replace_to[] = $row[$match];
                                    }
                                    $replace_right_button['content'] = preg_replace($pattern, $replace_to, $replace_right_button['content']);
                                }
                            }

                            if ($_button_match) {
                                if ($replace_right_button['target'] === null) {
                                    $row['right_button'] = $replace_right_button['content'];
                                    break(2);
                                } else {
                                    if (in_array($button['_tag'], $replace_right_button['target'])) {
                                        $row['right_button'] .= $replace_right_button['content'];
                                        continue(2);
                                    }
                                }
                            }
                        }
                    }

                    // 处理主键变量值
                    $button['href'] = preg_replace(
                        '/__id__/i',
                        $row[$this->_vars['primary_key']],
                        $button['href']
                    );

                    // 处理表名变量值
                    if (strpos($button['href'], '__table__') !== false) {
                        $button['href'] = preg_replace(
                            '/__table__/i',
                            $this->createTableToken($this->_table_name, $this->_prefix),
                            $button['href']
                        );
                    }

                    // 替换其他字段值
                    if (preg_match_all('/__(.*?)__/', $button['href'], $matches)) {
                        // 要替换的字段名
                        $replace_to = [];
                        $pattern    = [];
                        foreach ($matches[1] as $match) {
                            $replace = in_array($match, $this->rawField) ? $this->getData($key, $match) : (isset($row[$match]) ? $row[$match] : '');
                            if (isset($row[$match])) {
                                $pattern[]    = '/__'. $match .'__/i';
                                $replace_to[] = $replace;
                            }
                        }
                        $button['href'] = preg_replace(
                            $pattern,
                            $replace_to,
                            $button['href']
                        );
                    }

                    $button_style = $button['_style'];
                    unset($button['_style']);
                    // 编译按钮属性
                    $button['attribute'] = $this->compileHtmlAttr($button);
                    if ($button_style['title']) {
                        $row['right_button'] .= '<a '.$button['attribute'].'">';
                        if ($button_style['icon']) {
                            $row['right_button'] .= '<i class="'.$button['icon'].'"></i> ';
                        }
                        $row['right_button'] .= $button['title'].'</a>';
                    } else {
                        $row['right_button'] .= '<a '.$button['attribute'].' data-toggle="tooltip"><i class="'.$button['icon'].'"></i></a>';
                    }
                }
                $row['right_button'] = '<div class="btn-group">'. $row['right_button'] .'</div>';
            }

            // 编译单元格数据类型
            if ($this->_vars['columns']) {
                // 另外拷贝一份主键值，以免将主键设置为快速编辑的时候解析出错
                $row['_primary_key_value'] = isset($row[$this->_vars['primary_key']]) ? $row[$this->_vars['primary_key']] : '';

                foreach ($this->_vars['columns'] as $column) {
                    $_name       = $column['field'];
                    $_table_name = $column['table'];

                    // 如果需要显示编号
                    if ($column['name'] == '__INDEX__') {
                        $row[$column['name']] = $key + 1;
                    }

                    if (in_array($column['name'], $this->rawField)) {
                        $row[$column['name']] = $this->getData($key, $column['name']);
                    }

                    // 备份原数据
                    if (isset($row[$column['name']])) {
                        $row['__'.$column['name'].'__'] = $row[$column['name']];
                    }

                    switch ($column['type']) {
                        case 'link': // 链接
                            if ($column['default'] != '') {
                                // 要替换的字段名
                                $replace_to = [];
                                $pattern    = [];
                                $url        = $column['default'];
                                $target     = $column['param'] == '' ? '_self' : $column['param'];
                                if (preg_match_all('/__(.*?)__/', $column['default'], $matches)) {
                                    foreach ($matches[1] as $match) {
                                        $pattern[]    = '/__'. $match .'__/i';
                                        $replace_to[] = $row[$match];
                                    }
                                    $url = preg_replace($pattern, $replace_to, $url);
                                }

                                $url = $column['class'] == 'pop' ? $url.(strpos($url, '?') ? '&' : '?').'_pop=1' : $url;

                                if ($column['extra'] != '') {
                                    $title = $column['extra'] === true ? $column['title'] : $column['extra'];
                                } else {
                                    $title = $row[$column['name']];
                                }

                                $row[$column['name'].'__'.$column['type']] = '<a href="'. $url .'"
                                    title="'. $title .'"
                                    class="'. $column['class'] .'"
                                    target="'.$target.'">'.$row[$column['name']].'</a>';
                            }
                            break;
                        case 'switch': // 开关
                            switch ($row[$column['name']]) {
                                case '0': // 关闭
                                    $row[$column['name'].'__'.$column['type']] = '<label class="css-input switch switch-sm switch-primary" title="开启/关闭"><input type="checkbox" data-table="'.$this->createTableToken($this->_table_name, $this->_prefix).'" data-id="'.$row['_primary_key_value'].'" data-field="'.$column['name'].'"><span></span></label>';
                                    break;
                                case '1': // 开启
                                    $row[$column['name'].'__'.$column['type']] = '<label class="css-input switch switch-sm switch-primary" title="开启/关闭"><input type="checkbox" data-table="'.$this->createTableToken($this->_table_name, $this->_prefix).'" data-id="'.$row['_primary_key_value'].'" data-field="'.$column['name'].'" checked=""><span></span></label>';
                                    break;
                            }
                            break;
                        case 'status': // 状态
                            $status = $row[$column['name']];
                            $list_status = !empty($column['param']) ? $column['param'] : ['禁用:warning', '启用:success'];

                            if (isset($list_status[$status])) {
                                switch ($status) {
                                    case '0': $class = 'warning';break;
                                    case '1': $class = 'success';break;
                                    case '2': $class = 'primary';break;
                                    case '3': $class = 'info';break;
                                    default: $class  = 'default';
                                }
                                if (strpos($list_status[$status], ':')) {
                                    list($label, $class) = explode(':', $list_status[$status]);
                                } else {
                                    $label = $list_status[$status];
                                }
                                $row[$column['name'].'__'.$column['type']] = '<span class="label label-'.$class.'">'.$label.'</span>';
                            }
                            break;
                        case 'yesno': // 是/否
                            switch ($row[$column['name']]) {
                                case '0': // 否
                                    $row[$column['name'].'__'.$column['type']] = '<i class="fa fa-ban text-danger"></i>';
                                    break;
                                case '1': // 是
                                    $row[$column['name'].'__'.$column['type']] = '<i class="fa fa-check text-success"></i>';
                                    break;
                            }
                            break;
                        case 'text.edit': // 可编辑的单行文本
                            $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                data-type="text" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'textarea.edit': // 可编辑的多行文本
                            $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                class="textarea-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                data-type="textarea" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'password': // 密码框
                            $column['param'] = $column['param'] != '' ? $column['param'] : $column['name'];
                            $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                data-type="password" 
                                data-value="" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">******</a>';
                            break;
                        case 'email': // 邮箱地址
                        case 'url': // 链接地址
                        case 'tel': // 电话
                        case 'number': // 数字
                        case 'range': // 范围
                            $column['param'] = $column['param'] != '' ? $column['param'] : $column['name'];
                            $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                data-type="'.$column['type'].'" 
                                data-value="'.$row[$column['name']].'" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'icon': // 图标
                            if ($row[$column['name']] === '') {
                                $row[$column['name'].'__'.$column['type']] = '<i class="'.$column['default'].'"></i>';
                            } else {
                                $row[$column['name'].'__'.$column['type']] = '<i class="'.$row[$column['name']].'"></i>';
                            }
                            break;
                        case 'byte': // 字节
                            if ($row[$column['name']] === '') {
                                $row[$column['name'].'__'.$column['type']] = $column['default'];
                            } else {
                                $row[$column['name'].'__'.$column['type']] = format_bytes($row[$column['name']], $column['param']);
                            }
                            break;
                        case 'date': // 日期
                        case 'datetime': // 日期时间
                        case 'time': // 时间
                            // 默认格式
                            $format = 'Y-m-d H:i';
                            switch ($column['type']) {
                                case 'date': $format = 'Y-m-d';break;
                                case 'datetime': $format = 'Y-m-d H:i';break;
                                case 'time': $format = 'H:i';break;
                            }
                            // 格式
                            $format = $column['param'] == '' ? $format : $column['param'];
                            if ($row[$column['name']] == '') {
                                $row[$column['name'].'__'.$column['type']] = $column['default'];
                            } else {
                                $row[$column['name'].'__'.$column['type']] = format_time($row[$column['name']], $format);
                            }
                            break;
                        case 'date.edit': // 可编辑日期时间，默认发送的是格式化好的
                        case 'datetime.edit': // 可编辑日期时间，默认发送的是格式化好的
                        case 'time.edit': // 可编辑时间，默认发送的是格式化好的
                            // 默认格式
                            $format = 'YYYY-MM-DD HH:mm';
                            switch ($column['type']) {
                                case 'date.edit': $format = 'YYYY-MM-DD';break;
                                case 'datetime.edit': $format = 'YYYY-MM-DD HH:mm';break;
                                case 'time.edit': $format = 'HH:mm';break;
                            }
                            // 格式
                            $format = $column['param'] == '' ? $format : $column['param'];
                            // 时间戳
                            $timestamp = $row[$column['name']];
                            $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                class="combodate-edit" 
                                data-format="'.$format.'" 
                                data-name="'.$_name.'" 
                                data-template="'.$format.'" 
                                data-callback="" 
                                data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                data-type="combodate" 
                                data-pk="'.$row['_primary_key_value'].'">';
                            if ($row[$column['name']] == '') {
                                $row[$column['name'].'__'.$column['type']] .= $column['default'].'</a>';
                            } else {
                                $row[$column['name'].'__'.$column['type']] .= format_moment($timestamp, $format).'</a>';
                            }

                            // 加载moment.js
                            $this->_vars['_js_files'][] = 'moment_js';
                            break;
                        case 'avatar': // 头像
                            break;
                        case 'img_url': // 外链图片
                            if ($row[$column['name']] != '') {
                                $row[$column['name'].'__'.$column['type']] = '<div class="js-gallery"><img class="image" data-original="'.$row[$column['name']].'" src="'.$row[$column['name']].'"></div>';
                            }
                            break;
                        case 'picture': // 单张图片
                            $row[$column['name'].'__'.$column['type']] = '<div class="js-gallery"><img class="image" data-original="'.get_file_path($row[$column['name']]).'" src="'.get_thumb($row[$column['name']]).'"></div>';
                            break;
                        case 'pictures': // 多张图片
                            if ($row[$column['name']] === '') {
                                $row[$column['name'].'__'.$column['type']] = !empty($column['default']) ? $column['default'] : '暂无图片';
                            } else {
                                $list_img = is_array($row[$column['name']]) ? $row[$column['name']] : explode(',', $row[$column['name']]);
                                $imgs = '<div class="js-gallery">';
                                foreach ($list_img as $k => $img) {
                                    if ($column['param'] != '' && $k == $column['param']) {
                                        break;
                                    }
                                    $imgs .= ' <img class="image" data-original="'.get_file_path($img).'" src="'.get_thumb($img).'">';
                                }
                                $row[$column['name'].'__'.$column['type']] = $imgs.'</div>';
                            }
                            break;
                        case 'files':
                            if ($row[$column['name']] === '') {
                                $row[$column['name'].'__'.$column['type']] = !empty($column['default']) ? $column['default'] : '暂无文件';
                            } else {
                                $list_file = is_array($row[$column['name']]) ? $row[$column['name']] : explode(',', $row[$column['name']]);
                                $files = '<div>';
                                foreach ($list_file as $k => $file) {
                                    if ($column['param'] != '' && $k == $column['param']) {
                                        break;
                                    }
                                    $files .= ' [<a href="'.get_file_path($file).'">'.get_file_name($file).'</a>]';
                                }
                                $row[$column['name'].'__'.$column['type']] = $files.'</div>';
                            }
                            break;
                        case 'select': // 下拉框
                            if ($column['default']) {
                                if (isset($column['default'][$row[$column['name']]])) {
                                    $prepend = $column['default'][$row[$column['name']]] != '' ? $column['default'][$row[$column['name']]] : '空值';
                                } else {
                                    $prepend = '无对应值';
                                }
                                $class   = ($prepend == '无对应值' || $prepend == '空值') ? 'select-edit text-danger' : 'select-edit';
                                $source = json_encode($column['default'], JSON_FORCE_OBJECT);
                                $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);" 
                                    class="'.$class.'"
                                    data-table="'.$this->createTableToken($_table_name == '' ? $this->_table_name : $_table_name, $this->_prefix).'" 
                                    data-type="select" 
                                    data-value="'.$row[$column['name']].'" 
                                    data-source=\''.$source.'\' 
                                    data-pk="'.$row['_primary_key_value'].'" 
                                    data-name="'.$_name.'">'.$prepend.'</a>';
                            }
                            break;
                        case 'select2': // tag编辑(有BUG)
//                            if ($column['default']) {
//                                $source = json_encode($column['default']);
//                                $row[$column['name'].'__'.$column['type']] = '<a href="javascript:void(0);"
//                                    class="select2-edit"
//                                    data-table="'.$this->_table_name.'"
//                                    data-value="'.$row[$column['name']].'"
//                                    data-type="select2"
//                                    data-source=\''.$source.'\'
//                                    data-pk="'.$row['_primary_key_value'].'"
//                                    data-name="'.$column['name'].'">'.$row[$column['name']].'</a>';
//                            }
                            break;
                        case 'callback': // 调用回调方法
                            unset($column['field']);
                            unset($column['table']);
                            $params = array_slice($column, 4);
                            $params = array_filter($params, function($v){return $v !== '';});

                            if (isset($row[$column['name']]) || array_key_exists($column['name'], $row)) {
                                $params = array_merge([$row[$column['name']]], $params);
                            }

                            if (!empty($params)) {
                                foreach ($params as &$param) {
                                    if ($param === '__data__') $param = $row;
                                }
                            }

                            $row[$column['name'].'__'.$column['type']] = call_user_func_array($column['default'], $params);
                            break;
                        case 'popover':
                            $length = empty($column['default']) ? 10 : $column['default'];
                            $placement = empty($column['param']) ? 'top' : $column['param'];
                            $row[$column['name'].'__'.$column['type']] = mb_substr($row[$column['name']], 0, $length, 'utf-8').'... <i class="fa fa-fw fa-question-circle" data-toggle="popover" data-placement="'.$placement.'" data-content="'.$row[$column['name']].'"></i>';
                            break;
                        case 'text':
                        default: // 默认
                            // 设置默认值
                            if (!isset($row[$column['name']]) && !empty($column['default'])) {
                                $row[$column['name']] = $column['default'];
                            }

                            if (is_array($column['type']) && !empty($column['type'])) {
                                if (isset($column['type'][$row[$column['name']]])) {
                                    $row[$column['name']] = $column['type'][$row[$column['name']]];
                                }
                            } else {
                                if (!empty($column['param'])) {
                                    if (isset($column['param'][$row[$column['name']]])) {
                                        $row[$column['name']] = $column['param'][$row[$column['name']]];
                                    }
                                } else {
                                    if (isset($row[$column['name']]) && $row[$column['name']] == '' && $column['default'] != '') {
                                        $row[$column['name']] = $column['default'];
                                    }
                                }
                            }
                    }
                }
            }
        }
    }

    /**
     * 分析行class
     * @param mixed $row 行数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    private function parseTrClass($row)
    {
        $_tr_class = [];
        foreach ($this->_tr_class as $tr_class => $conditions) {
            $match = true;
            foreach ($conditions as $condition) {
                if (is_callable($condition[0])) {
                    $params = array_merge([$row], $condition[1]);
                    $match = call_user_func_array($condition[0], $params) ? $match : false;
                    continue;
                }
                if (!isset($row[$condition[0]])) {
                    $match = false; continue;
                }
                $match = $this->parseCondition($row, $condition) ? $match : false;
            }
            if ($match) {
                $_tr_class[] = $tr_class;
            }
        }

        return $_tr_class;
    }

    /**
     * 分析条件
     * @param mixed $row 行数据
     * @param array $condition 对比条件
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    private function parseCondition($row, $condition = [])
    {
        $match = true;
        switch ($condition[1]) {
            case 'eq':
                $row[$condition[0]] != $condition[2] && $match = false;
                break;
            case 'neq':
                $row[$condition[0]] == $condition[2] && $match = false;
                break;
            case 'gt':
                $row[$condition[0]] <= $condition[2] && $match = false;
                break;
            case 'lt':
                $row[$condition[0]] >= $condition[2] && $match = false;
                break;
            case 'egt':
                $row[$condition[0]] < $condition[2] && $match = false;
                break;
            case 'elt':
                $row[$condition[0]] > $condition[2] && $match = false;
                break;
            case 'in':
                !in_array($row[$condition[0]], $condition[2]) && $match = false;
                break;
            case 'not in':
                in_array($row[$condition[0]], $condition[2]) && $match = false;
                break;
            case 'between':
                ($row[$condition[0]] < $condition[2][0] || $row[$condition[0]] > $condition[2][1]) && $match = false;
                break;
            case 'not between':
                ($row[$condition[0]] >= $condition[2][0] && $row[$condition[0]] <= $condition[2][1]) && $match = false;
                break;
        }
        return $match;
    }

    /**
     * 创建筛选Token
     * @param string $table 表名
     * @param string $field 字段
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|string
     */
    private function createFilterToken($table = '', $field = '')
    {
        $table_token = substr(sha1($table.'-'.$field.'-'.session('user_auth.last_login_ip').'-'.session('user_auth.uid').'-'.session('user_auth.last_login_time')), 0, 8);
        session($table_token, ['table' => $table, 'field' => $field]);
        return $table_token;
    }

    /**
     * 编译表格数据
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function compileTable(){
        // 设置表名
        $this->_vars['_table'] = $this->_table_name;

        // 处理字段筛选
        if ($this->_vars['filter_columns']) {
            // 要筛选的字段
            $filter_columns = [];
            // 要筛选的字段条件
            $filter_maps    = [];
            // 处理字段筛选条件
            if (!empty($this->_vars['filter_map'])) {
                foreach ($this->_vars['filter_map'] as $fields => $map) {
                    if (strpos($fields, ',')) {
                        $fields = explode(',', $fields);
                        foreach ($fields as $field) {
                            if (isset($filter_maps[$field])) {
                                // 如果某字段的条件已存在，则合并条件
                                $filter_maps[$field] = array_merge($filter_maps[$field], $map);
                            } else {
                                $filter_maps[$field] = $map;
                            }
                        }
                    } else {
                        if (isset($filter_maps[$fields])) {
                            // 如果某字段的条件已存在，则合并条件
                            $filter_maps[$fields] = array_merge($filter_maps[$fields], $map);
                        } else {
                            $filter_maps[$fields] = $map;
                        }
                    }
                }
                // 将条件转换为json格式
                foreach ($filter_maps as &$filter_map) {
                    $filter_map = json_encode($filter_map);
                }
            }

            // 组合字段筛选
            foreach ($this->_vars['filter_columns'] as $key => $value) {
                if (is_numeric($key)) {
                    if (strpos($value, '.')) {
                        list($table, $field) = explode('.', $value);
                        $_filter_token = $this->createFilterToken($table, $field);
                        $filter_columns[$field] = [
                            'token'   => $_filter_token,
                            'type'    => $this->_filter_type[$value],
                            'filter'  => $value,
                            'map'     => isset($filter_maps[$field]) ? $filter_maps[$field] : '',
                            'options' => isset($this->_filter_options[$field]) ? $this->_filter_options[$field] : '',
                            'list'    => isset($this->_filter_list[$field]) ? $this->_filter_list[$field] : ''
                        ];
                    } else {
                        $_filter_token = $this->createFilterToken($this->_table_name, $value);
                        $filter_columns[$value] = [
                            'token'   => $_filter_token,
                            'type'    => $this->_filter_type[$value],
                            'filter'  => $value,
                            'map'     => isset($filter_maps[$value]) ? $filter_maps[$value] : '',
                            'options' => isset($this->_filter_options[$value]) ? $this->_filter_options[$value] : '',
                            'list'    => isset($this->_filter_list[$value]) ? $this->_filter_list[$value] : ''
                        ];
                    }
                } else {
                    if (strpos($value, '.')) {
                        list($table, $field) = explode('.', $value);
                        $_filter_token = $this->createFilterToken($table, $field);
                        $filter_columns[$key] = [
                            'token'   => $_filter_token,
                            'type'    => $this->_filter_type[$value],
                            'filter'  => $value,
                            'map'     => isset($filter_maps[$key]) ? $filter_maps[$key] : '',
                            'options' => isset($this->_filter_options[$key]) ? $this->_filter_options[$key] : '',
                            'list'    => isset($this->_filter_list[$key]) ? $this->_filter_list[$key] : ''
                        ];
                    } else {
                        $_filter_token = $this->createFilterToken($value, $key);
                        $filter_columns[$key] = [
                            'token'   => $_filter_token,
                            'type'    => $this->_filter_type[$value],
                            'filter'  => $value . '.' . $key,
                            'map'     => isset($filter_maps[$key]) ? $filter_maps[$key] : '',
                            'options' => isset($this->_filter_options[$key]) ? $this->_filter_options[$key] : '',
                            'list'    => isset($this->_filter_list[$key]) ? $this->_filter_list[$key] : ''
                        ];
                    }
                }
            }
            $this->_vars['filter_columns'] = $filter_columns;
        }

        // 处理字段筛选默认选项
        $this->_vars['_filter_content'] = implode('|', $this->_vars['_filter_content']);
        $this->_vars['_field_display']  = implode(',', $this->_vars['_field_display']);
        $this->_vars['_filter']         = implode('|', $this->_vars['_filter']);

        // 处理字段排序
        if ($this->_vars['order_columns']) {
            $order_columns = [];
            foreach ($this->_vars['order_columns'] as $key => $value) {
                if (is_numeric($key)) {
                    if (strpos($value, '.')) {
                        $tmp = explode('.', $value);
                        $order_columns[$tmp[1]] = $value;
                    } else {
                        $order_columns[$value] = $value;
                    }
                } else {
                    if (strpos($value, '.')) {
                        $order_columns[$key] = $value;
                    } else {
                        $order_columns[$key] = $value. '.' .$key;
                    }
                }
            }
            $this->_vars['order_columns'] = $order_columns;
        }

        // 编译顶部按钮
        if ($this->_vars['top_buttons']) {
            foreach ($this->_vars['top_buttons'] as &$button) {
                // 处理表名变量值
                if (strpos($button['href'], '__table__')) {
                    $button['href'] = preg_replace(
                        '/__table__/i',
                        $this->createTableToken($this->_table_name, $this->_prefix),
                        $button['href']
                    );
                }

                $button['attribute'] = $this->compileHtmlAttr($button);
                $new_button = "<a {$button['attribute']}>";
                if (isset($button['icon']) && $button['icon'] != '') {
                    $new_button .= '<i class="'.$button['icon'].'"></i> ';
                }
                $new_button .= "{$button['title']}</a>";
                $button = $new_button;
            }
        }

        // 编译顶部下拉菜单
        if ($this->_vars['_select_list']) {
            foreach ($this->_vars['_select_list'] as $name => &$select) {
                // 当前url参数
                $url_params = $this->request->param();

                // 要搜索的字段
                $select_field = $this->request->param('_select_field', '');
                $select_field = $select_field != '' ? explode('|', $select_field) : [];

                // 对应的值
                $select_value = $this->request->param('_select_value', '');
                $select_value = $select_value != '' ? explode('|', $select_value) : [];

                // 合并默认值
                if ($this->_select_list_default) {
                    foreach ($this->_select_list_default as $field => $value) {
                        if (!in_array($field, $select_field)) {
                            array_push($select_field, $field);
                            array_push($select_value, $value);
                        }
                    }
                }

                // 当前选中值
                if (in_array($name, $select_field)) {
                    $select['current'] = $select_value[array_search($name, $select_field)];
                }

                // 剔除要忽略的参数
                if ($select['ignore'] !== '') {
                    $ignores = explode(',', $select['ignore']);
                    foreach ($ignores as $ignore) {
                        if (array_search($ignore, $select_field) !== false) {
                            $pos = array_search($ignore, $select_field);
                            array_splice($select_field, $pos, 1);
                            array_splice($select_value, $pos, 1);
                        }
                    }
                }

                // 生成除默认选项的下拉项的跳转url
                if (!empty($select_field)) {
                    if (!in_array($name, $select_field)) {
                        array_push($select_field, $name);
                    }
                    $url_params['_select_field'] = implode('|', $select_field);
                    foreach ($select['options'] as $key => $option) {
                        $select_value[array_search($name, $select_field)] = $key;
                        $url_params['_select_value'] = implode('|', $select_value);
                        $select['url'][$key]   = url('').'?'.http_build_query($url_params);
                    }
                } else {
                    $url_params['_select_field'] = $name;
                    foreach ($select['options'] as $key => $option) {
                        $url_params['_select_value'] = $key; // 添加下拉菜单项查询参数
                        $select['url'][$key]   = url('').'?'.http_build_query($url_params);
                    }
                }

                // 生成默认选项的url
                if (isset($this->_select_list_default[$name])) {
                    $url_params['_select_field'] = implode('|', $select_field);
                    $select_value[array_search($name, $select_field)] = '_all';
                    $url_params['_select_value'] = implode('|', $select_value);
                } else {
                    if (array_search($name, $select_field) !== false) {
                        $pos = array_search($name, $select_field);
                        unset($select_value[$pos]);
                        unset($select_field[$pos]);
                        if (empty($select_field)) {
                            unset($url_params['_select_field']);
                            unset($url_params['_select_value']);
                        } else {
                            $url_params['_select_field'] = implode('|', $select_field);
                            $url_params['_select_value'] = implode('|', $select_value);
                        }
                    }
                }
                $select['default_url'] = url('').'?'.http_build_query($url_params);
            }
        }

        // 处理搜索框
        if ($this->_search) {
            $_temp_fields = [];
            foreach ($this->_search['fields'] as $key => $field) {
                if (is_numeric($key)) {
                    if (strpos($field, '.')) {
                        $_field = explode('.', $field)[1];
                    } else {
                        $_field = $field;
                    }
                    $_temp_fields[$field] = isset($this->_field_name[$_field]) ? $this->_field_name[$_field] : '';
                } else {
                    $_temp_fields[$key]   = $field;
                }
            }
            $this->_vars['search'] = [
                'fields'      => $_temp_fields,
                'field_all'   => implode('|', array_keys($_temp_fields)),
                'placeholder' => $this->_search['placeholder'] != '' ? $this->_search['placeholder'] : '请输入'. implode('/', $_temp_fields),
                'url'         => $this->_search['url'] == '' ? $this->request->baseUrl(true) : $this->_search['url']
            ];
        }

        // 编译表格数据row_list的值
        $this->compileRows();

        // 处理页面标题
        if ($this->_vars['page_title'] == '') {
            $location = get_location('', false, false);
            if ($location) {
                $curr_location = end($location);
                $this->_vars['page_title'] = $curr_location['title'];
            }
        }

        // 处理是否有分页数据
        if (!$this->_has_pages) {
            $this->_vars['pages'] = '';
        }

        // 处理js和css合并的参数
        if (!empty($this->_vars['_js_files'])) {
            $this->_vars['_js_files'] = array_unique($this->_vars['_js_files']);
        }
        if (!empty($this->_vars['_css_files'])) {
            $this->_vars['_css_files'] = array_unique($this->_vars['_css_files']);
            sort($this->_vars['_css_files']);
        }
        if (!empty($this->_vars['_js_init'])) {
            $this->_vars['_js_init'] = array_unique($this->_vars['_js_init']);
            sort($this->_vars['_js_init']);
            $this->_vars['_js_init'] = json_encode($this->_vars['_js_init']);
        }
    }

    /**
     * 加载模板输出
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $config   模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        // 编译表格数据
        $this->compileTable();

        if ($template != '') {
            $this->_template = $template;
        }

        if (!empty($vars)) {
            $this->_vars = array_merge($this->_vars, $vars);
        }

        // 实例化视图并渲染
        return parent::fetch($this->_template, $this->_vars, $config);
    }
}
