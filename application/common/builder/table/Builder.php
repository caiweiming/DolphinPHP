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

namespace app\common\builder\table;

use app\common\builder\ZBuilder;
use think\Cache;

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
        '_field_display'     => '',       // 字段筛选的默认选项
        '_filter_content'    => '',       // 字段筛选的默认选中值
        '_filter'            => '',       // 字段筛选的默认字段名
        'top_buttons'        => [],       // 顶部栏按钮
        'right_buttons'      => [],       // 表格右侧按钮
        'search'             => [],       // 搜索参数
        'columns'            => [],       // 表格列集合
        'pages'              => '',       // 分页数据
        'row_list'           => [],       // 表格数据列表
        '_page_info'         => '',       // 分页信息
        'primary_key'        => 'id',     // 表格主键名称
        '_table'             => '',       // 表名
        'js_list'            => [],       // js文件名
        'css_list'           => [],       // css文件名
        'validate'           => '',       // 快速编辑的验证器名
    ];

    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function _initialize()
    {
        $this->_module     = $this->request->module();
        $this->_controller = $this->request->controller();
        $this->_action     = $this->request->action();
        $this->_table_name = strtolower($this->_module.'_'.trim(preg_replace("/[A-Z]/", "_\\0", $this->_controller), "_"));
        $this->_template   = APP_PATH. 'common/builder/table/layout.html';
    }

    /**
     * 设置页面标题
     * @param string $page_title 页面标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPageTitle($page_title = '')
    {
        $this->_vars['page_title'] = $page_title;
        return $this;
    }

    /**
     * 隐藏第一列多选框
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function hideCheckbox()
    {
        $this->_vars['hide_checkbox'] = true;
        return $this;
    }

    /**
     * 设置页面提示
     * @param string $tips 提示信息
     * @param string $type 提示类型：success/info/warning/danger，默认info
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPageTips($tips = '', $type = 'info')
    {
        $this->_vars['page_tips'] = $tips;
        $this->_vars['tips_type'] = $type;
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
        $column = is_array($column) ? $column : explode(',', $column);
        $this->_vars['order_columns'] = array_merge($this->_vars['order_columns'], $column);
        return $this;
    }

    /**
     * 添加表头筛选
     * @param array|string $columns 表头筛选字段，多个以逗号隔开
     * @param array $options 选项，供有些字段值需要另外显示的，比如字段值是数字，但显示的时候是其他文字。
     * @param array $default 默认选项，['字段名' => '字段值,字段值...']
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addFilter($columns = [], $options = [], $default = [])
    {
        $columns = is_array($columns) ? $columns : explode(',', $columns);
        $this->_vars['filter_columns'] = array_merge($this->_vars['filter_columns'], $columns);
        // 存储对应的字段选项
        if (!empty($options) && is_array($options)) {
            foreach ($columns as $key => $column) {
                if (is_numeric($key)) {
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
        if (is_array($fields)) {
            $this->_vars['filter_map'] = array_merge($this->_vars['filter_map'], $fields);
        } else {
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
        return $this;
    }

    /**
     * 时间段过滤
     * @param string $field 字段名
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTimeFilter($field = '')
    {
        if ($field != '') {
            $this->_vars['_js_files'][]  = 'datepicker_js';
            $this->_vars['_css_files'][] = 'datepicker_css';
            $this->_vars['_js_init']     = json_encode(['datepicker']);
            $this->_vars['_filter_time'] = $field;
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
        $this->_vars['validate']        = $validate;
        $this->_vars['validate_fields'] = $fields;
        return $this;
    }

    /**
     * 替换右侧按钮
     * @param array $map 条件，格式为：['字段名' => '字段值', '字段名' => '字段值'....]
     * @param string $content 要替换的内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function replaceRightButton($map = [], $content = '')
    {
        $this->_replace_right_buttons[] = [
            'map'     => $map,
            'content' => $content
        ];
        return $this;
    }

    /**
     * 自动创建新增页面
     * @param array $items 表单项
     * @param string $table 表名
     * @param string $validate 验证器名
     * @param string $auto_time 自动添加时间，默认有两个create_time和update_time
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function autoAdd($items = [], $table = '', $validate = '', $auto_time = '')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 默认属性
        $btn_attribute = [
            'title' => '新增',
            'icon'  => 'fa fa-plus-circle',
            'class' => 'btn btn-primary',
            'href'  => url(
                $this->_module.'/'.$this->_controller.'/add'
            ),
        ];

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
            'validate'  => $validate == true ? ucfirst($this->_controller) : $validate,
            'auto_time' => $auto_time
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
        return $this;
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
        // 判断当前用户是否有权限，没有权限则不生成按钮
        if (session('user_auth.role') != 1) {
            if (isset($attribute['href']) && isset($attribute['href']) != '') {
                preg_match('/admin\.php\/(.*)/', $attribute['href'], $match);
                $url_value = explode('/', $match[1]);
                if (strpos($url_value[2], '.')) {
                    $url_value[2] = substr($url_value[2], 0, strpos($url_value[2], '.'));
                }
                $url_value = $url_value[0].'/'.$url_value[1].'/'.$url_value[2];
            } else {
                $url_value = $this->_module.'/'.$this->_controller.'/'.$type;
            }
            $url_value      = strtolower($url_value);
            $user_menu_auth = session('user_menu_auth');
            if (!isset($user_menu_auth[$url_value])) {
                return $this;
            }
        }

        // 按钮属性
        $btn_attribute = [];

        // 表单名，用于替换
        $table = isset($attribute['table']) ? $attribute['table'] : '__table__';

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
                    'href'  => url(
                        $this->_module.'/'.$this->_controller.'/add',
                        ['plugin_name' => $plugin_name]
                    ),
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
                    'href'        => url(
                        $this->_module.'/'.$this->_controller.'/enable',
                        ['table' => $table, 'field' => $field]
                    ),
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
                    'href'        => url(
                        $this->_module.'/'.$this->_controller.'/disable',
                        ['table' => $table, 'field' => $field]
                    ),
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
                    'href'        => url(
                        $this->_module.'/'.$this->_controller.'/delete',
                        ['table' => $table]
                    ),
                ];
                break;

            // 自定义按钮
            case 'custom':
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

        // 是否为弹出框方式
        if ($pop) {
            $btn_attribute['class'] .= ' pop';
            $btn_attribute['href'] .= '?_pop=1';
        }

        $this->_vars['top_buttons'][] = $btn_attribute;
        return $this;
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
        if ($buttons) {
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
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function autoEdit($items = [], $table = '', $validate = '', $auto_time = '')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 默认属性
        $btn_attribute = [
            'title' => '编辑',
            'icon'  => 'fa fa-pencil',
            'class' => 'btn btn-xs btn-default',
            'href'  => url(
                $this->_module.'/'.$this->_controller.'/edit',
                ['id' => '__id__']
            ),
            'target' => '_self'
        ];

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
            'validate'  => $validate == true ? ucfirst($this->_controller) : $validate,
            'auto_time' => $auto_time
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
        return $this;
    }

    /**
     * 添加一个右侧按钮
     * @param string $type 按钮类型：edit/enable/disable/delete/custom
     * @param array $attribute 按钮属性
     * @param bool $pop 是否使用弹出框形式
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addRightButton($type = '', $attribute = [], $pop = false)
    {
        // 判断当前用户是否有权限，没有权限则不生成按钮
        if (session('user_auth.role') != 1) {
            if (isset($attribute['href']) && isset($attribute['href']) != '') {
                preg_match('/admin\.php\/(.*)/', $attribute['href'], $match);
                $url_value = explode('/', $match[1]);
                if (strpos($url_value[2], '.')) {
                    $url_value[2] = substr($url_value[2], 0, strpos($url_value[2], '.'));
                }
                $url_value = $url_value[0].'/'.$url_value[1].'/'.$url_value[2];
            } else {
                $url_value = $this->_module.'/'.$this->_controller.'/'.$type;
            }
            $url_value      = strtolower($url_value);
            $user_menu_auth = session('user_menu_auth');
            if (!isset($user_menu_auth[$url_value])) {
                return $this;
            }
        }

        // 按钮属性
        $btn_attribute = [];

        // 表单名，用于替换
        $table = isset($attribute['table']) ? $attribute['table'] : '__table__';

        // 这个专门为插件准备的属性，是插件名称
        $plugin_name = isset($attribute['plugin_name']) ? $attribute['plugin_name'] : $this->_plugin_name;

        switch ($type) {
            // 编辑按钮
            case 'edit':
                // 默认属性
                $btn_attribute = [
                    'title' => '编辑',
                    'icon'  => 'fa fa-pencil',
                    'class' => 'btn btn-xs btn-default',
                    'href'  => url(
                        $this->_module.'/'.$this->_controller.'/edit',
                        [
                            'id'          => '__id__',
                            'plugin_name' => $plugin_name
                        ]
                    ),
                    'target' => '_self'
                ];
                break;

            // 启用按钮
            case 'enable':
                // 默认属性
                $btn_attribute = [
                    'title' => '启用',
                    'icon'  => 'fa fa-check',
                    'class' => 'btn btn-xs btn-default ajax-get confirm',
                    'href'  => url(
                        $this->_module.'/'.$this->_controller.'/enable',
                        [
                            'ids'   => '__id__',
                            'table' => $table
                        ]
                    ),
                ];
                break;

            // 禁用按钮
            case 'disable':
                // 默认属性
                $btn_attribute = [
                    'title' => '禁用',
                    'icon'  => 'fa fa-ban',
                    'class' => 'btn btn-xs btn-default ajax-get confirm',
                    'href'  => url(
                        $this->_module.'/'.$this->_controller.'/disable',
                        [
                            'ids'   => '__id__',
                            'table' => $table
                        ]
                    ),
                ];
                break;

            // 删除按钮(不可恢复)
            case 'delete':
                // 默认属性
                $btn_attribute = [
                    'title' => '删除',
                    'icon'  => 'fa fa-times',
                    'class' => 'btn btn-xs btn-default ajax-get confirm',
                    'href'  => url(
                        $this->_module.'/'.$this->_controller.'/delete',
                        [
                            'ids'   => '__id__',
                            'table' => $table
                        ]
                    ),
                ];
                break;

            // 自定义按钮
            case 'custom':
                // 默认属性
                $btn_attribute = [
                    'title' => '自定义按钮',
                    'icon'  => 'fa fa-smile-o',
                    'class' => 'btn btn-xs btn-default',
                    'href'  => 'javascript:void(0);'
                ];
                break;
        }

        // 合并自定义属性
        if ($attribute && is_array($attribute)) {
            $btn_attribute = array_merge($btn_attribute, $attribute);
        }

        // 是否为弹出框方式
        if ($pop) {
            $btn_attribute['class'] .= ' pop';
            $btn_attribute['href'] .= '?_pop=1';
        }

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
        if ($buttons) {
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
     * 设置搜索参数
     * @param array $fields 参与搜索的字段
     * @param string $placeholder 提示符
     * @param string $url 提交地址
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setSearch($fields = [], $placeholder = '', $url = '')
    {
        if (!empty($fields)) {
            $this->_vars['search'] = [
                'fields'      => $fields,
                'field_all'   => empty($fields) ? '' : implode('|', array_keys($fields)),
                'placeholder' => $placeholder != '' ? $placeholder : '请输入'. implode('/', $fields),
                'url'         => $url == '' ? $this->request->url(true) : $url
            ];
        }
        return $this;
    }

    /**
     * 引入模块js文件
     * @param string $files_name js文件名，多个文件用逗号隔开
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function js($files_name = '')
    {
        $this->loadFile('js', $files_name);
        return $this;
    }

    /**
     * 引入模块css文件
     * @param string $files_name css文件名，多个文件用逗号隔开
     * @author caiweiming <314013107@qq.com>
     * @return $this
     */
    public function css($files_name = '')
    {
        $this->loadFile('css', $files_name);
        return $this;
    }

    /**
     * 引入css或js文件
     * @param string $type 类型：css/js
     * @param string $files_name 文件名，多个用逗号隔开
     * @author caiweiming <314013107@qq.com>
     */
    private function loadFile($type = '', $files_name = '')
    {
        if ($files_name != '') {
            if (!is_array($files_name)) {
                $files_name = explode(',', $files_name);
            }
            foreach ($files_name as $item) {
                $this->_vars[$type.'_list'][] = $item;
            }
        }
    }

    /**
     * 设置数据库表名
     * @param string $table 数据库表名，不含前缀
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTableName($table = '')
    {
        $this->_table_name = $table;
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
        $this->_plugin_name = $plugin_name;
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
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addColumn($name = '', $title = '', $type = '', $default = '', $param = '', $class = '')
    {
        $column = [
            'name'    => $name,
            'title'   => $title,
            'type'    => $type,
            'default' => $default,
            'param'   => $param,
            'class'   => $class
        ];

        $this->_vars['columns'][] = $column;
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
     * 设置表格数据列表
     * @param array $row_list 表格数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setRowList($row_list = [])
    {
        if (is_array($row_list) && !empty($row_list)) {
            $this->_vars['row_list'] = $row_list;
        } elseif (is_object($row_list) && !$row_list->isEmpty()) {
            $this->_vars['row_list']   = is_object(current($row_list->getIterator())) ? $row_list : $row_list->all();
            $this->_vars['_page_info'] = $row_list;
            // 设置分页
            $this->setPages($row_list->render());
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
        $this->_vars['primary_key'] = $key;
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
        $this->_vars['pages'] = $pages;
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
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setExtraHtml($extra_html = '')
    {
        $this->_vars['extra_html'] = $extra_html;
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
        $this->_vars['extra_js'] = $extra_js;
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
        $this->_vars['extra_css'] = $extra_css;
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
        $this->_template = $template;
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
            // 编译右侧按钮
            if ($this->_vars['right_buttons']) {
                // 默认给列添加个空的右侧按钮
                if (!isset($row['right_button'])) {
                    $row['right_button'] = '';
                }

                // 如有替换右侧按钮，执行修改
                $_replace_button = false;
                if (!empty($this->_replace_right_buttons)) {
                    foreach ($this->_replace_right_buttons as $replace_right_button) {
                        // 是否能匹配到条件
                        $_button_match = true;
                        foreach ($replace_right_button['map'] as $field => $item) {
                            if (isset($row[$field]) && $row[$field] != $item) {
                                $_button_match = false;
                            }
                        }

                        if ($_button_match) {
                            $row['right_button'] = $replace_right_button['content'];
                            $_replace_button       = true;
                            break;
                        }
                    }
                }

                // 没有替换按钮，则按常规解析按钮url
                if (!$_replace_button) {
                    foreach ($this->_vars['right_buttons'] as $button_type => $button) {
                        // 处理主键变量值
                        $button['href'] = preg_replace(
                            '/__id__/i',
                            $row[$this->_vars['primary_key']],
                            $button['href']
                        );

                        // 处理表名变量值
                        $button['href'] = preg_replace(
                            '/__table__/i',
                            $this->_table_name,
                            $button['href']
                        );

                        // 编译按钮属性
                        $button['attribute'] = $this->compileHtmlAttr($button);
                        $row['right_button'] .= '<a '.$button['attribute'].' data-toggle="tooltip"><i class="'.$button['icon'].'"></i></a> ';
                    }
                    $row['right_button'] = '<div class="btn-group">'. $row['right_button'] .'</div>';
                }
            }

            // 编译单元格数据类型
            if ($this->_vars['columns']) {
                // 另外拷贝一份主键值，以免将主键设置为快速编辑的时候解析出错
                $row['_primary_key_value'] = $row[$this->_vars['primary_key']];
                foreach ($this->_vars['columns'] as $column) {
                    $_name       = $column['name'];
                    $_table_name = $this->_table_name;

                    // 判断是否有字段别名
                    if (strpos($column['name'], '|')) {
                        list($column['name'], $_name) = explode('|', $column['name']);
                        // 判断是否有表名
                        if (strpos($_name, '.')) {
                            list($_table_name, $_name) = explode('.', $_name);
                        }
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
                                        $pattern[] = '/__'. $match .'__/i';
                                        $replace_to[] = $row[$match];
                                    }
                                    $url = preg_replace(
                                        $pattern,
                                        $replace_to,
                                        $url
                                    );
                                }
                                $row[$column['name']] = '<a href="'. $url .'"
                                    title="'. $row[$column['name']] .'"
                                    target="'.$target.'">'.$row[$column['name']].'</a>';
                            }
                            break;
                        case 'switch': // 开关
                            switch ($row[$column['name']]) {
                                case '0': // 关闭
                                    $row[$column['name']] = '<label class="css-input switch switch-sm switch-primary" title="开启/关闭"><input type="checkbox" data-table="'.$this->_table_name.'" data-id="'.$row['_primary_key_value'].'" data-field="'.$column['name'].'"><span></span></label>';
                                    break;
                                case '1': // 开启
                                    $row[$column['name']] = '<label class="css-input switch switch-sm switch-primary" title="开启/关闭"><input type="checkbox" data-table="'.$this->_table_name.'" data-id="'.$row['_primary_key_value'].'" data-field="'.$column['name'].'" checked=""><span></span></label>';
                                    break;
                            }
                            break;
                        case 'status': // 状态
                            switch ($row[$column['name']]) {
                                case '0': // 禁用
                                    $status_info = isset($column['param'][0]) ? $column['param'][0] : '禁用';
                                    $row[$column['name']] = '<span class="label label-warning">'.$status_info.'</span>';
                                    break;
                                case '1': // 启用
                                    $status_info = isset($column['param'][1]) ? $column['param'][1] : '启用';
                                    $row[$column['name']] = '<span class="label label-success">'.$status_info.'</span>';
                                    break;
                            }
                            break;
                        case 'yesno': // 是/否
                            switch ($row[$column['name']]) {
                                case '0': // 否
                                    $row[$column['name']] = '<i class="fa fa-ban text-danger"></i>';
                                    break;
                                case '1': // 是
                                    $row[$column['name']] = '<i class="fa fa-check text-success"></i>';
                                    break;
                            }
                            break;
                        case 'text.edit': // 可编辑的单行文本
                            $row[$column['name']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$_table_name.'" 
                                data-type="text" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'textarea.edit': // 可编辑的多行文本
                            $row[$column['name']] = '<a href="javascript:void(0);" 
                                class="textarea-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$_table_name.'" 
                                data-type="textarea" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'password': // 密码框
                            $column['param'] = $column['param'] != '' ? $column['param'] : $column['name'];
                            $row[$column['name']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$_table_name.'" 
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
                            $row[$column['name']] = '<a href="javascript:void(0);" 
                                class="text-edit" 
                                data-placeholder="请输入'.$column['title'].'" 
                                data-table="'.$_table_name.'" 
                                data-type="'.$column['type'].'" 
                                data-value="'.$row[$column['name']].'" 
                                data-pk="'.$row['_primary_key_value'].'" 
                                data-name="'.$_name.'">'.$row[$column['name']].'</a>';
                            break;
                        case 'icon': // 图标
                            if ($row[$column['name']] === '') {
                                $row[$column['name']] = '<i class="'.$column['default'].'"></i>';
                            } else {
                                $row[$column['name']] = '<i class="'.$row[$column['name']].'"></i>';
                            }
                            break;
                        case 'byte': // 字节
                            if ($row[$column['name']] === '') {
                                $row[$column['name']] = $column['default'];
                            } else {
                                $row[$column['name']] = format_bytes($row[$column['name']], $column['param']);
                            }
                            break;
                        case 'date': // 日期
                        case 'datetime': // 日期时间
                        case 'time': // 时间
                            // 默认格式
                            $format = 'Y-m-d H:i';
                            if ($column['type'] == 'date')     $format = 'Y-m-d';
                            if ($column['type'] == 'datetime') $format = 'Y-m-d H:i';
                            if ($column['type'] == 'time')     $format = 'H:i';
                            // 格式
                            $format = $column['param'] == '' ? $format : $column['param'];
                            if ($row[$column['name']] == '') {
                                $row[$column['name']] = $column['default'];
                            } else {
                                $row[$column['name']] = format_time($row[$column['name']], $format);
                            }
                            break;
                        case 'date.edit': // 可编辑日期时间，默认发送的是格式化好的
                        case 'datetime.edit': // 可编辑日期时间，默认发送的是格式化好的
                        case 'time.edit': // 可编辑时间，默认发送的是格式化好的
                            // 默认格式
                            $format = 'YYYY-MM-DD HH:mm';
                            if ($column['type'] == 'date.edit')     $format = 'YYYY-MM-DD';
                            if ($column['type'] == 'datetime.edit') $format = 'YYYY-MM-DD HH:mm';
                            if ($column['type'] == 'time.edit')     $format = 'HH:mm';

                            // 格式
                            $format = $column['param'] == '' ? $format : $column['param'];
                            // 时间戳
                            $timestamp = $row[$column['name']];
                            $row[$column['name']] = '<a href="javascript:void(0);" 
                                class="combodate-edit" 
                                data-format="'.$format.'" 
                                data-name="'.$_name.'" 
                                data-template="'.$format.'" 
                                data-callback="" 
                                data-table="'.$_table_name.'" 
                                data-type="combodate" 
                                data-pk="'.$row['_primary_key_value'].'">';
                            if ($row[$column['name']] == '') {
                                $row[$column['name']] .= $column['default'].'</a>';
                            } else {
                                $row[$column['name']] .= format_moment($timestamp, $format).'</a>';
                            }
                            break;
                        case 'avatar': // 头像
                            break;
                        case 'picture': // 单张图片
                            $row[$column['name']] = '<a href="'.get_file_path($row[$column['name']]).'" target="_blank" title="'.get_file_name($row[$column['name']]).'"><img class="image" src="'.get_file_path($row[$column['name']]).'"></a>';
                            break;
                        case 'pictures': // 多张图片
                            if ($row[$column['name']] === '') {
                                $row[$column['name']] = !empty($column['default']) ? $column['default'] : '暂无图片';
                            } else {
                                $list_img = is_array($row[$column['name']]) ? $row[$column['name']] : explode(',', $row[$column['name']]);
                                $imgs = '';
                                foreach ($list_img as $key => $img) {
                                    if ($column['param'] != '' && $key == $column['param']) {
                                        break;
                                    }
                                    $imgs .= ' <a href="'.get_file_path($img).'" target="_blank" title="'.get_file_name($img).'"><img class="image" src="'.get_file_path($img).'"></a>';
                                }
                                $row[$column['name']] = $imgs;
                            }
                            break;
                        case 'select': // 下拉框
                            if ($column['default']) {
                                $prepend = isset($column['default'][$row[$column['name']]]) ? $column['default'][$row[$column['name']]] : '';
                                $source = json_encode($column['default'], JSON_FORCE_OBJECT);
                                $row[$column['name']] = '<a href="javascript:void(0);" 
                                    class="select-edit" 
                                    data-table="'.$_table_name.'" 
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
//                                $row[$column['name']] = '<a href="javascript:void(0);"
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
                            if ($column['param'] == '') {
                                $params = [$row[$column['name']]];
                            } else if ($column['param'] === '__data__') {
                                $params = [$row[$column['name']], $row];
                            } else {
                                $params = [$row[$column['name']], $column['param']];
                            }
                            $row[$column['name']] = call_user_func_array($column['default'], $params);
                            break;
                        case 'text':
                        default: // 默认
                            if (!isset($row[$column['name']]) && !empty($column['default'])) {
                                $row[$column['name']] = $column['default'];
                            }
                            if (!empty($column['param'])) {
                                if (isset($column['param'][$row[$column['name']]])) {
                                    $row[$column['name']] = $column['param'][$row[$column['name']]];
                                }
                            }
                    }
                }
            }
        }
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
                        $filter_columns[$field] = [
                            'table'   => $table,
                            'field'   => $field,
                            'filter'  => $table . '.' . $field,
                            'map'     => isset($filter_maps[$field]) ? $filter_maps[$field] : '',
                            'options' => isset($this->_filter_options[$value]) ? $this->_filter_options[$value] : '',
                        ];
                    } else {
                        $filter_columns[$value] = [
                            'table'   => $this->_table_name,
                            'field'   => $value,
                            'filter'  => $value,
                            'map'     => isset($filter_maps[$value]) ? $filter_maps[$value] : '',
                            'options' => isset($this->_filter_options[$value]) ? $this->_filter_options[$value] : '',
                        ];
                    }
                } else {
                    if (strpos($value, '.')) {
                        list($table, $field) = explode('.', $value);
                        $filter_columns[$key] = [
                            'table'   => $table,
                            'field'   => $field,
                            'filter'  => $table . '.' . $field,
                            'map'     => isset($filter_maps[$key]) ? $filter_maps[$key] : '',
                            'options' => isset($this->_filter_options[$field]) ? $this->_filter_options[$field] : '',
                        ];
                    } else {
                        $filter_columns[$key] = [
                            'table'   => $value,
                            'field'   => $key,
                            'filter'  => $value . '.' . $key,
                            'map'     => isset($filter_maps[$key]) ? $filter_maps[$key] : '',
                            'options' => isset($this->_filter_options[$key]) ? $this->_filter_options[$key] : '',
                        ];
                    }
                }
            }
            $this->_vars['filter_columns'] = $filter_columns;
        }

        // 处理字段筛选默认选项
        if ($this->_vars['_filter_content']) {
            $this->_vars['_filter_content'] = implode('|', $this->_vars['_filter_content']);
        }
        if ($this->_vars['_field_display']) {
            $this->_vars['_field_display'] = implode(',', $this->_vars['_field_display']);
        }
        if ($this->_vars['_filter']) {
            $this->_vars['_filter'] = implode('|', $this->_vars['_filter']);
        }

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
                $button['href'] = preg_replace(
                    '/__table__/i',
                    $this->_table_name,
                    $button['href']
                );

                $button['attribute'] = $this->compileHtmlAttr($button);
                $new_button = "<a {$button['attribute']}>";
                if (isset($button['icon']) && $button['icon'] != '') {
                    $new_button .= '<i class="'.$button['icon'].'"></i> ';
                }
                $new_button .= "{$button['title']}</a>";
                $button = $new_button;
            }
        }

        // 编译表格数据row_list的值
        $this->compileRows();

        // 处理页面标题
        if ($this->_vars['page_title'] == '') {
            $location = get_location();
            $curr_location = end($location);
            $this->_vars['page_title'] = $curr_location['title'];
        }

        // 处理是否有分页数据
        if (!$this->_has_pages) {
            $this->_vars['pages'] = '';
        }
    }

    /**
     * 加载模板输出
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $replace  模板替换
     * @param array  $config   模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        // 编译表格数据
        $this->compileTable();

        if ($template != '') {
            $this->_template = $template;
        }

        // 实例化视图并渲染
        return parent::fetch($this->_template, $this->_vars, $replace, $config);
    }
}