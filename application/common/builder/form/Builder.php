<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\common\builder\form;

use app\common\builder\ZBuilder;
use think\Exception;
use think\facade\Env;

/**
 * 表单构建器
 * @package app\common\builder\type
 * @author 蔡伟明 <314013107@qq.com>
 */
class Builder extends ZBuilder
{
    /**
     * @var string 模板路径
     */
    private $_template = '';

    /**
     * @var array 模板变量
     */
    private $_vars = [
        'page_title'      => '',    // 页面标题
        'page_tips'       => '',    // 页面提示
        'tips_type'       => '',    // 提示类型
        'btn_hide'        => [],    // 要隐藏的按钮
        'btn_title'       => [],    // 按钮标题
        'form_items'      => [],    // 表单项目
        'tab_nav'         => [],    // 页面Tab导航
        'post_url'        => '',    // 表单提交地址
        'form_data'       => [],    // 表单数据
        'extra_html'      => '',    // 额外HTML代码
        'extra_js'        => '',    // 额外JS代码
        'extra_css'       => '',    // 额外CSS代码
        'ajax_submit'     => true,  // 是否ajax提交
        'hide_header'     => false, // 是否隐藏表单头部标题
        'header_title'    => '',    // 表单头部标题
        'js_list'         => [],    // 需要引入的js文件名
        'css_list'        => [],    // 需要引入的css文件名
        'field_triggers'  => [],    // 需要触发的表单项名
        'field_hide'      => '',    // 需要隐藏的表单项
        'field_values'    => '',    // 触发表单项的值
        'field_clear'     => [],    // 字段清除
        '_js_files'       => [],    // 需要加载的js（合并输出）
        '_js_init'        => [],    // 初始化的js（合并输出）
        '_css_files'      => [],    // 需要加载的css（合并输出）
        '_layout'         => [],    // 布局参数
        'btn_extra'       => [],    // 额外按钮
        'submit_confirm'  => false, // 提交确认
        'extend_js_list'  => [],    // 扩展表单项js列表
        'extend_css_list' => [],    // 扩展表单项css列表
        '_method'         => 'post',// 表单提交方式
        'empty_tips'      => '暂无数据',// 没有表单项时的提示信息
        '_token_name'     => '__token__', // 表单令牌名称
        '_token_value'    => '', // 表单令牌值
    ];

    /**
     * @var bool 是否组合分组
     */
    private $_is_group = false;

    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function initialize()
    {
        $this->_template = Env::get('app_path'). 'common/builder/form/layout.html';
        $this->_vars['post_url'] = $this->request->url(true);
        $this->_vars['_token_name'] = config('zbuilder.form_token_name');
        $this->_vars['_token_value'] = $this->request->token($this->_vars['_token_name']);
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
     * @param string $title 页面标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setPageTitle($title = '')
    {
        if ($title != '') {
            $this->_vars['page_title'] = trim($title);
        }
        return $this;
    }

    /**
     * 设置表单页提示信息
     * @param string $tips 提示信息
     * @param string $type 提示类型：success,info,danger,warning
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
     * 设置表单提交地址
     * @param string $post_url 提交地址
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setUrl($post_url = '')
    {
        if ($post_url != '') {
            $this->_vars['post_url'] = trim($post_url);
        }
        return $this;
    }

    /**
     * 隐藏按钮
     * @param array|string $btn 要隐藏的按钮，如：['submit']，其中'submit'->确认按钮，'back'->返回按钮
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function hideBtn($btn = [])
    {
        if (!empty($btn)) {
            $this->_vars['btn_hide'] = is_array($btn) ? $btn : explode(',', $btn);
        }
        return $this;
    }

    /**
     * 添加底部额外按钮
     * @param string $btn 按钮内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addBtn($btn = '')
    {
        if ($btn != '') {
            $this->_vars['btn_extra'][] = $btn;
        }
        return $this;
    }

    /**
     * 设置按钮标题
     * @param string|array $btn 按钮名 'submit' -> “提交”，'back' -> “返回”
     * @param string $title 按钮标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setBtnTitle($btn = '', $title = '')
    {
        if (!empty($btn)) {
            if (is_array($btn)) {
                $this->_vars['btn_title'] = $btn;
            } else {
                $this->_vars['btn_title'][trim($btn)] = trim($title);
            }
        }
        return $this;
    }

    /**
     * 设置提交表单时显示确认框
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function submitConfirm()
    {
        $this->_vars['submit_confirm'] = true;
        return $this;
    }

    /**
     * 隐藏表单头部标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function hideHeaderTitle()
    {
        $this->_vars['hide_header'] = true;
        return $this;
    }

    /**
     * 设置表单头部标题
     * @param string $title 标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setHeaderTitle($title = '')
    {
        $this->_vars['header_title'] = trim($title);
        return $this;
    }

    /**
     * 设置表单令牌
     * @param string $name 令牌名称
     * @param string $type 令牌生成方法
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setToken($name = '__token__', $type = 'md5')
    {
        $this->_vars['_token_name']  = $name === '' ? '__token__' : $name;
        $this->_vars['_token_value'] = $this->request->token($this->_vars['_token_name'], $type);
        return $this;
    }

    /**
     * 设置触发
     * @param string $trigger 需要触发的表单项名，目前支持select（单选类型）、text、radio三种
     * @param string $values 触发的值
     * @param string $show 触发后要显示的表单项名，目前不支持普通联动、范围、拖动排序、静态文本
     * @param bool $clear 是否清除值
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTrigger($trigger = '', $values = '', $show = '', $clear = true)
    {
        if (!empty($trigger)) {
            if (is_array($trigger)) {
                foreach ($trigger as $item) {
                    $this->_vars['field_hide']   .= $item[2].',';
                    $this->_vars['field_values'] .= $item[1].',';
                    $this->_vars['field_triggers'][$item[0]][] = [(string)$item[1], $item[2]];
                    $this->_vars['field_clear'][$item[0]] = isset($item[3]) ? ($item[3] === true ? 1 : 0) : 1;
                }
            } else {
                $this->_vars['field_hide']   .= $show.',';
                $this->_vars['field_values'] .= (string)$values.',';
                $this->_vars['field_triggers'][$trigger][] = [(string)$values, $show];
                $this->_vars['field_clear'][$trigger] = $clear === true ? 1 : 0;
            }
        }
        return $this;
    }

    /**
     * 添加触发
     * @param array $triggers 触发数组
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTrigger($triggers = [])
    {
        if (!empty($triggers)) {
            $this->setTrigger($triggers);
        }
        return $this;
    }

    /**
     * 添加数组类型的表单项，基本和Textarea是一样的，但读取的时候会用parse_attr函数转换
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author caiweiming <314013107@qq.com>
     * @return Builder
     */
    public function addArray($name = '', $title = '', $tips = '', $default = '', $extra_attr = '', $extra_class = '') {
        return $this->addTextarea($name, $title, $tips, $default, $extra_attr, $extra_class);
    }

    /**
     * 添加单个档案文件
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addArchive($name = '', $title = '', $tips = '', $extra_class = '')
    {
        $item = [
            'type'        => 'archive',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加多个档案文件
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addArchives($name = '', $title = '', $tips = '', $extra_class = '')
    {
        $item = [
            'type'        => 'archives',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加百度地图
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $ak 百度APPKEY
     * @param string $tips 提示
     * @param string $default 默认坐标
     * @param string $address 默认地址
     * @param string $level 地图显示级别
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addBmap($name = '', $title = '', $ak = '', $tips = '', $default = '', $address = '', $level = '', $extra_class = '')
    {
        $item = [
            'type'        => 'bmap',
            'name'        => $name,
            'title'       => $title,
            'ak'          => $ak,
            'tips'        => $tips,
            'value'       => $default,
            'address'     => $address,
            'level'       => $level == '' ? 12 : $level,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加按钮
     * @param string $name 表单项名，也是按钮id
     * @param array $attr 按钮属性
     * @param string $ele_type 按钮类型，默认为button，也可以为a标签
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this|array
     */
    public function addButton($name = '', $attr = [], $ele_type = 'button')
    {
        $item = [
            'type'     => 'button',
            'name'     => $name,
            'id'       => $name,
            'ele_type' => $ele_type,
            'data'     => '',
        ];
        if ($attr) {
            foreach ($attr as $key => $value) {
                if (substr($key, 0, 5) == 'data-') {
                    $item['data'] .= $key. '=' . $value . ' ';
                }
            }
            $item = array_merge($item, $attr);
        }

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加复选框
     * @param string $name 复选框名
     * @param string $title 复选框标题
     * @param string $tips 提示
     * @param array $options 复选框数据
     * @param string $default 默认值
     * @param array $attr 属性，
     *      color-颜色(default/primary/info/success/warning/danger)，默认primary
     *      size-尺寸(sm,nm,lg)，默认sm
     *      shape-形状(rounded,square)，默认rounded
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addCheckbox($name = '', $title = '', $tips = '', $options = [], $default = '', $attr = [], $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'checkbox',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'options'     => $options == '' ? [] : $options,
            'value'       => $default,
            'attr'        => $attr,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'extra_label_class' => $extra_attr == 'disabled' ? 'css-input-disabled' : '',
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加CKEditor编辑器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $width 编辑器宽度，默认100%
     * @param integer $height 编辑器高度，默认400px
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addCkeditor($name = '', $title = '', $tips = '', $default = '', $width = '100%', $height = 400, $extra_class = '')
    {
        $item = [
            'type'        => 'ckeditor',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'width'       => $width,
            'height'      => $height,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加取色器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $mode 模式：默认为rgba(含透明度)，也可以是rgb
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addColorpicker($name = '', $title = '', $tips = '', $default = '', $mode = 'rgba', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'colorpicker',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'mode'        => $mode,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加日期
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $format 日期格式
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addDate($name = '', $title = '', $tips = '', $default = '', $format = '', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'date',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'format'      => $format == '' ? 'yyyy-mm-dd' : $format,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加日期范围
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $format 格式
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addDaterange($name = '', $title = '', $tips = '', $default = '', $format = '', $extra_attr = '', $extra_class = '')
    {
        if (strpos($name, ',')) {
            list($name_from, $name_to) = explode(',', $name);
            $id_from = $name_from;
            $id_to   = $name_to;
            $id      = $name_from;
        } else {
            $name_from = $name_to = $name . '[]';
            $id_from = $name . '_from';
            $id_to   = $name . '_to';
            $id      = $name;
        }

        if (strpos($default, ',') !== false) {
            list($value_from, $value_to) = explode(',', $default);
        } else {
            $value_from = $default;
            $value_to   = '';
        }

        $item = [
            'type'        => 'daterange',
            'id'          => $id,
            'name_from'   => $name_from,
            'name_to'     => $name_to,
            'id_from'     => $id_from,
            'id_to'       => $id_to,
            'title'       => $title,
            'tips'        => $tips,
            'value_from'  => $value_from,
            'value_to'    => $value_to,
            'format'      => $format == '' ? 'yyyy-mm-dd' : $format,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加日期时间
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $format 日期时间格式
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addDatetime($name = '', $title = '', $tips = '', $default = '', $format = '', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'datetime',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'format'      => $format == '' ? 'YYYY-MM-DD HH:mm' : $format,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加markdown编辑器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param bool $watch 是否实时预览
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addEditormd($name = '', $title = '', $tips = '', $default = '', $watch = true, $extra_class = '')
    {
        $item = [
            'type'        => 'editormd',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'watch'       => $watch,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加单文件上传
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $size 文件大小，单位为kb
     * @param string $ext 文件后缀
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addFile($name = '', $title = '', $tips = '', $default = '', $size = '', $ext = '', $extra_class = '')
    {
        $size = ($size != '' ? $size : config('upload_file_size')) * 1024;
        $ext  = $ext != '' ? $ext : config('upload_file_ext');

        $item = [
            'type'        => 'file',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'size'        => $size,
            'ext'         => $ext,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加多文件上传
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $size 图片大小，单位为kb
     * @param string $ext 文件后缀
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addFiles($name = '', $title = '', $tips = '', $default = '', $size = '', $ext = '', $extra_class = '')
    {
        $size = ($size != '' ? $size : config('upload_file_size')) * 1024;
        $ext  = $ext != '' ? $ext : config('upload_file_ext');

        $item = [
            'type'        => 'files',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'size'        => $size,
            'ext'         => $ext,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加图片相册
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addGallery($name = '', $title = '', $tips = '', $default = '', $extra_class = '')
    {
        $item = [
            'type'        => 'gallery',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加分组
     * @param array $groups 分组数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addGroup($groups = [])
    {
        if (is_array($groups) && !empty($groups)) {
            $this->_is_group = true;
            foreach ($groups as &$group) {
                foreach ($group as $key => $item) {
                    $type = array_shift($item);
                    if (strpos($type, ':')) {
                        list($type, $layout) = explode(':', $type);

                        $layout = explode('|', $layout);
                        $this->_vars['_layout'][$item[0]] = [
                            'xs' => $layout[0],
                            'sm' => isset($layout[1]) ? ($layout[1] == '' ? $layout[0] : $layout[1]) : $layout[0],
                            'md' => isset($layout[2]) ? ($layout[2] == '' ? $layout[0] : $layout[2]) : $layout[0],
                            'lg' => isset($layout[3]) ? ($layout[3] == '' ? $layout[0] : $layout[3]) : $layout[0],
                        ];
                    }
                    $group[$key] = call_user_func_array([$this, 'add'.ucfirst($type)], $item);
                }
            }
            $this->_is_group = false;
        }

        $item = [
            'type'    => 'group',
            'options' => $groups
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加隐藏表单项
     * @param string $name 表单项名
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addHidden($name = '', $default = '', $extra_class = '')
    {
        $item = [
            'type'        => 'hidden',
            'name'        => $name,
            'value'       => $default,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加图标选择器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addIcon($name = '', $title = '', $tips = '', $default = '', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'icon',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加单图片上传
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $size 图片大小，单位为kb，0为不限制
     * @param string $ext 文件后缀
     * @param string $extra_class 额外css类名
     * @param array|string $thumb 缩略图参数
     * @param array|string $watermark 水印参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addImage($name = '', $title = '', $tips = '', $default = '', $size = '', $ext = '', $extra_class = '', $thumb = '', $watermark = '')
    {
        $size = ($size != '' ? $size : config('upload_image_size')) * 1024;
        $ext  = $ext != '' ? $ext : config('upload_image_ext');

        $item = [
            'type'        => 'image',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'size'        => $size,
            'ext'         => $ext,
            'extra_class' => $extra_class,
        ];

        // 处理缩略图参数
        if (isset($thumb['size'])) {
            $item['thumb'] = $thumb['size'].'|'.(isset($thumb['type']) ? $thumb['type'] : 1);
        } else {
            $item['thumb'] = $thumb;
        }

        // 处理水印参数
        if (isset($watermark['img'])) {
            $item['watermark'] = $watermark['img'].'|'.(isset($watermark['pos']) ? $watermark['pos'] : 9).'|'.(isset($watermark['alpha']) ? $watermark['alpha'] : 50);
        } else {
            $item['watermark'] = $watermark;
        }

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加多图片上传
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $size 图片大小，单位为kb，0为不限制
     * @param string $ext 文件后缀
     * @param string $extra_class 额外css类名
     * @param array|string $thumb 缩略图参数
     * @param array|string $watermark 水印参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addImages($name = '', $title = '', $tips = '', $default = '', $size = '', $ext = '', $extra_class = '', $thumb = '', $watermark = '')
    {
        $size = ($size != '' ? $size : config('upload_image_size')) * 1024;
        $ext  = $ext != '' ? $ext : config('upload_image_ext');

        $item = [
            'type'        => 'images',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'size'        => $size,
            'ext'         => $ext,
            'extra_class' => $extra_class,
        ];

        // 处理缩略图参数
        if (isset($thumb['size'])) {
            $item['thumb'] = $thumb['size'].'|'.(isset($thumb['type']) ? $thumb['type'] : 1);
        } else {
            $item['thumb'] = $thumb;
        }

        // 处理水印参数
        if (isset($watermark['img'])) {
            $item['watermark'] = $watermark['img'].'|'.(isset($watermark['pos']) ? $watermark['pos'] : 9).'|'.(isset($watermark['alpha']) ? $watermark['alpha'] : 50);
        } else {
            $item['watermark'] = $watermark;
        }

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 图片裁剪
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param array $options 参数
     * @param string $extra_class 额外css类名
     * @param array|string $thumb 缩略图参数
     * @param array|string $watermark 水印参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addJcrop($name = '', $title = '', $tips = '', $default = '', $options = [], $extra_class = '', $thumb = '', $watermark = '')
    {
        $item = [
            'type'        => 'jcrop',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'options'     => json_encode($options),
            'extra_class' => $extra_class,
        ];

        // 处理缩略图参数
        if (isset($thumb['size'])) {
            $item['thumb'] = $thumb['size'].'|'.(isset($thumb['type']) ? $thumb['type'] : 1);
        } else {
            $item['thumb'] = $thumb;
        }

        // 处理水印参数
        if (isset($watermark['img'])) {
            $item['watermark'] = $watermark['img'].'|'.(isset($watermark['pos']) ? $watermark['pos'] : 9).'|'.(isset($watermark['alpha']) ? $watermark['alpha'] : 50);
        } else {
            $item['watermark'] = $watermark;
        }

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加普通联动表单项
     * @param string $name 表单项名
     * @param string $title 表单项标题
     * @param string $tips 表单项提示说明
     * @param array $options 表单项options
     * @param string $default 默认值
     * @param string $ajax_url 数据异步请求地址
     *      可以用Url方法生成，返回数据格式必须如下：
     *      $arr['code'] = '1'; //判断状态
     *      $arr['msg'] = '请求成功'; //回传信息
     *      $arr['list'] = [
     *          ['key' => 'gz', 'value' => '广州'],
     *          ['key' => 'sz', 'value' => '深圳'],
     *      ]; //数据
     *      return json($arr);
     *      status用于判断是否请求成功，list将作为$next_items第一个表单名的下拉框的内容
     * @param string $next_items 下一级下拉框的表单名
     *      如果有多个关联关系，必须一同写上，用逗号隔开,
     *      比如学院作为联动的一个下拉框，它的下级是专业，那么这里就写上专业下拉框的表单名，如：'zy'
     *      如果还有班级，那么切换学院的时候，专业和班级应该是一同关联的
     *      所以就必须写上专业和班级的下拉框表单名，如：'zy,bj'
     * @param string $param 指定请求参数的key名称，默认为$name的值
     *      比如$param为“key”
     *      那么请求数据的时候会发送参数key=某个下拉框选项值
     * @param string $extra_param 额外参数名，可以同时发送表单中的其他表单项值
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addLinkage($name = '', $title = '', $tips = '', $options = [], $default = '', $ajax_url = '', $next_items = '', $param = '', $extra_param = '')
    {
        $item = [
            'type'        => 'linkage',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'options'     => $options,
            'ajax_url'    => $ajax_url,
            'next_items'  => $next_items,
            'param'       => $param == '' ? $name : $param,
            'extra_param' => $extra_param,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 创建快速多级联动Token
     * @param string $table 表名
     * @param string $option
     * @param string $key
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|string
     */
    private function createLinkagesToken($table = '', $option = '', $key = '')
    {
        $table_token = substr(sha1($table.'-'.$option.'-'.$key.'-'.session('user_auth.last_login_ip').'-'.UID.'-'.session('user_auth.last_login_time')), 0, 8);
        session($table_token, ['table' => $table, 'option' => $option, 'key' => $key]);
        return $table_token;
    }

    /**
     * 添加快速多级联动
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $table 表名
     * @param int $level 级别
     * @param string $default 默认值
     * @param array|string $fields 字段名，默认为id,name,pid
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addLinkages($name = '', $title = '', $tips = '', $table = '', $level = 2, $default = '', $fields = [])
    {
        if ($level > 4) {
            halt('目前最多只支持4级联动');
        }

        // 键字段名，也就是下拉菜单的option元素的value值
        $key    = 'id';
        // 值字段名，也就是下拉菜单显示的各项
        $option = 'name';
        // 父级id字段名
        $pid    = 'pid';

        if (!empty($fields)) {
            if (!is_array($fields)) {
                $fields = explode(',', $fields);
                $key    = isset($fields[0]) ? $fields[0] : $key;
                $option = isset($fields[1]) ? $fields[1] : $option;
                $pid    = isset($fields[2]) ? $fields[2] : $pid;
            } else {
                $key    = isset($fields['id'])   ? $fields['id']   : $key;
                $option = isset($fields['name']) ? $fields['name'] : $option;
                $pid    = isset($fields['pid'])  ? $fields['pid']  : $pid;
            }
        }

        $linkages_token = $this->createLinkagesToken($table, $option, $key);

        $item = [
            'type'   => 'linkages',
            'name'   => $name,
            'title'  => $title,
            'tips'   => $tips,
            'table'  => $table,
            'level'  => $level,
            'key'    => $key,
            'option' => $option,
            'pid'    => $pid,
            'value'  => $default,
            'token'  => $linkages_token,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加格式文本
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $format 格式
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addMasked($name = '', $title = '', $tips = '', $format = '', $default = '', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'masked',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'format'      => $format,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加数字输入框
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $min 最小值
     * @param string $max 最大值
     * @param string $step 步进值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addNumber($name = '', $title = '', $tips = '', $default = '', $min = '', $max = '', $step = '', $extra_attr = '', $extra_class = '')
    {
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        $item = [
            'type'        => 'number',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default == '' ? 0 : $default,
            'min'         => $min,
            'max'         => $max,
            'step'        => $step,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => isset($placeholder) ? $placeholder : '请输入'.$title,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加密码框
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addPassword($name = '', $title = '', $tips = '', $default = '', $extra_attr = '', $extra_class = '')
    {
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        $item = [
            'type'        => 'password',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => isset($placeholder) ? $placeholder : '请输入'.$title,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加单选
     * @param string $name 单选名
     * @param string $title 单选标题
     * @param string $tips 提示
     * @param array $options 单选数据
     * @param string $default 默认值
     * @param array $attr 属性，
     *      color-颜色(default/primary/info/success/warning/danger)，默认primary
     *      size-尺寸(sm,nm,lg)，默认sm
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addRadio($name = '', $title = '', $tips = '', $options = [], $default = '', $attr = [], $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'radio',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'options'     => $options == '' ? [] : $options,
            'value'       => $default,
            'attr'        => $attr,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'extra_label_class' => $extra_attr == 'disabled' ? 'css-input-disabled' : '',
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加范围
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param array $options 参数
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addRange($name = '', $title = '', $tips = '', $default = '', $options = [], $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'range',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];
        $item = array_merge($item, $options);
        if (isset($item['double']) && $item['double'] == 'true') {
            $item['double'] = 'double';
        }

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加普通下拉菜单
     * @param string $name 下拉菜单名
     * @param string $title 标题
     * @param string $tips 提示
     * @param array $options 选项
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addSelect($name = '', $title = '', $tips = '', $options = [], $default = '', $extra_attr = '', $extra_class = '')
    {
        $type = 'select';

        if ($extra_attr != '') {
            if (in_array('multiple', explode(' ', $extra_attr))) {
                $type = 'select2';
            }
        }

        $placeholder = $type == 'select' ? '请选择一项' : '请选择一项或多项';
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        $item = [
            'type'        => $type,
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'options'     => $options,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加拖拽排序
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param array $value 值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addSort($name = '', $title = '', $tips = '', $value = [], $extra_class = '')
    {
        $content = [];

        if (!empty($value)) {
            $content = $value;
        }

        $item = [
            'type'        => 'sort',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => implode(',', array_keys($value)),
            'content'     => $content,
            'extra_class' => $extra_class
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加静态文本
     * @param string $name 静态表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $hidden 需要提交的值
     * @param string $extra_class 额外css类
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addStatic($name = '', $title = '', $tips = '', $default = '', $hidden = '', $extra_class = '')
    {
        $item = [
            'type'        => 'static',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'hidden'      => $hidden === true ? ($default == '' ? true : $default) : $hidden,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加Summernote编辑器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $width 编辑器宽度
     * @param int $height 编辑器高度
     * @param string $extra_class
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addSummernote($name = '', $title = '', $tips = '', $default = '', $width = '100%', $height = 350, $extra_class = '')
    {
        $item = [
            'type'        => 'summernote',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'width'       => $width,
            'height'      => $height,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加开关
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param array $attr 属性，
     *      color-颜色(default/primary/info/success/warning/danger)，默认primary
     *      size-尺寸(sm,nm,lg)，默认sm
     *      shape-形状(rounded,square)，默认rounded
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addSwitch($name = '', $title = '', $tips = '', $default = '', $attr = [], $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'switch',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'attr'        => $attr,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'extra_label_class' => $extra_attr == 'disabled' ? 'css-input-disabled' : '',
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加标签
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addTags($name = '', $title = '', $tips = '', $default = '', $extra_class = '')
    {
        $item = [
            'type'        => 'tags',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => is_array($default) ? implode(',', $default) : $default,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加单行文本框
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param array $group 标签组，可以在文本框前后添加按钮或者文字
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addText($name = '', $title = '', $tips = '', $default = '', $group = [], $extra_attr = '', $extra_class = '')
    {
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        $item = [
            'type'        => 'text',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'group'       => $group,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => isset($placeholder) ? $placeholder : '请输入'.$title,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加多行文本框
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addTextarea($name = '', $title = '', $tips = '', $default = '', $extra_attr = '', $extra_class = '')
    {
        if (preg_match('/(.*)\[:(.*)\]/', $title, $matches)) {
            $title       = $matches[1];
            $placeholder = $matches[2];
        }

        $item = [
            'type'        => 'textarea',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => isset($placeholder) ? $placeholder : '请输入'.$title,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加时间
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $format 日期时间格式
     * @param string $extra_attr 额外属性
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addTime($name = '', $title = '', $tips = '', $default = '', $format = '', $extra_attr = '', $extra_class = '')
    {
        $item = [
            'type'        => 'time',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'format'      => $format == '' ? 'HH:mm:ss' : $format,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加百度编辑器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addUeditor($name = '', $title = '', $tips = '', $default = '', $extra_class = '')
    {
        $item = [
            'type'        => 'ueditor',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加wang编辑器
     * @param string $name 表单项名
     * @param string $title 标题
     * @param string $tips 提示
     * @param string $default 默认值
     * @param string $extra_class 额外css类名
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function addWangeditor($name = '', $title = '', $tips = '', $default = '', $extra_class = '')
    {
        $item = [
            'type'        => 'wangeditor',
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'value'       => $default,
            'extra_class' => $extra_class,
        ];

        if ($this->_is_group) {
            return $item;
        }

        $this->_vars['form_items'][] = $item;
        return $this;
    }

    /**
     * 添加表单项
     * 这个是addCheckbox等方法的别名方法，第一个参数传表单项类型，其余参数与各自方法中的参数一致
     * @param string $type 表单项类型
     * @param string $name 表单项名
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addFormItem($type = '', $name = '')
    {
        if ($type != '') {
            // 获取所有参数值
            $args = func_get_args();
            array_shift($args);

            // 判断是否有布局参数
            if (strpos($type, ':')) {
                list($type, $layout) = explode(':', $type);

                $layout = explode('|', $layout);
                $this->_vars['_layout'][$name] = [
                    'xs' => $layout[0],
                    'sm' => isset($layout[1]) ? ($layout[1] == '' ? $layout[0] : $layout[1]) : $layout[0],
                    'md' => isset($layout[2]) ? ($layout[2] == '' ? $layout[0] : $layout[2]) : $layout[0],
                    'lg' => isset($layout[3]) ? ($layout[3] == '' ? $layout[0] : $layout[3]) : $layout[0],
                ];
            }

            $method = 'add'. ucfirst($type);
            call_user_func_array([$this, $method], $args);
        }
        return $this;
    }

    /**
     * 一次性添加多个表单项
     * @param array $items 表单项
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addFormItems($items = [])
    {
        if (!empty($items)) {
            foreach ($items as $item) {
                call_user_func_array([$this, 'addFormItem'], $item);
            }
        }
        return $this;
    }

    /**
     * 直接设置表单项数据
     * @param array $items 表单项数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setFormItems($items = [])
    {
        if (!empty($items)) {
            foreach ($items as $key =>  $item) {
                switch ($item['type']) {
                    case 'group':
                        foreach ($item['options'] as $options) {
                            foreach ($options as $option) {
                                $this->loadMinify($option['type']);
                            }
                        }
                        break;
                    case 'select':
                        if (isset($item['extra_attr']) && $item['extra_attr'] == 'multiple') {
                            $items[$key]['type'] = 'select2';
                        }
                        break;
                }
                if ($item['type'] == 'group') {

                } else {
                    $this->loadMinify($item['type']);
                }

                // 设置布局参数
                if (isset($item['layout'])) {
                    $this->_vars['_layout'][$item['name']] = [
                        'xs' => $item['layout'],
                        'sm' => $item['layout'],
                        'md' => $item['layout'],
                        'lg' => $item['layout'],
                    ];
                }
            }

            // 额外已经构造好的表单项目与单个组装的的表单项目进行合并
            $this->_vars['form_items'] = array_merge($this->_vars['form_items'], $items);
        }
        return $this;
    }

    /**
     * 扩展额外表单项
     * @param $methodName
     * @param $argument
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     * @throws Exception
     */
    public function __call($methodName, $argument)
    {
        $type = strtolower(substr($methodName, 3));

        if ($type != '') {
            $class_name = 'form\\'.$type.'\\Builder';
            if (!class_exists($class_name)) {
                throw new Exception('类：'.$class_name.'不存在', 7001);
            }

            if (method_exists($class_name, 'item')) {
                $class = new $class_name;
                $form_item = call_user_func_array([$class, 'item'], $argument);
                $form_item['type'] = $type;

                if (!empty($class->js)) {
                    $this->_vars['extend_js_list'][$type] = $this->parseUrl($class->js, $type);
                }
                if (!empty($class->css)) {
                    $this->_vars['extend_css_list'][$type] = $this->parseUrl($class->css, $type);
                }

                if ($this->_is_group) {
                    return $form_item;
                }

                $this->_vars['form_items'][] = $form_item;
            } else {
                throw new Exception('扩展表单项未定义item()方法', 7001);
            }
        }
        return $this;
    }

    /**
     * 解析扩展表单项资源url
     * @param array $urls 资源url
     * @param string $type 表单项类型名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     */
    private function parseUrl($urls = [], $type = '')
    {
        foreach ($urls as $key => $item) {
            if (!preg_match('/__.*?__/', $item)) {
                $urls[$key] = '__EXTEND_FORM__/'.$type.'/'.$item;
            }
            $urls[$key] = str_replace(array_keys(config('template.tpl_replace_string')), array_values(config('template.tpl_replace_string')), $urls[$key]);
        }
        return $urls;
    }

    /**
     * 设置Tab按钮列表
     * @param array $tab_list Tab列表 如：['tab1' => ['title' => '标题', 'url' => 'http://www.dolphinphp.com']]
     * @param string $curr_tab 当前tab名
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
     * 设置表单数据
     * @param array $form_data 表单数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setFormData($form_data = [])
    {
        if (!empty($form_data)) {
            $this->_vars['form_data'] = $form_data;
        }
        return $this;
    }

    /**
     * 设置额外HTML代码
     * @param string $extra_html 额外HTML代码
     * @param string $tag 标记
     * @author 蔡伟明 <314013107@qq.com>
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
     * 表单项布局
     * @param array $column 布局参数 ['表单项名' => 所占宽度,....]
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function layout($column = [])
    {
        if (!empty($column)) {
            foreach ($column as $field => $layout) {
                $layout = explode('|', $layout);
                $this->_vars['_layout'][$field] = [
                    'xs' => $layout[0],
                    'sm' => isset($layout[1]) ? ($layout[1] == '' ? $layout[0] : $layout[1]) : $layout[0],
                    'md' => isset($layout[2]) ? ($layout[2] == '' ? $layout[0] : $layout[2]) : $layout[0],
                    'lg' => isset($layout[3]) ? ($layout[3] == '' ? $layout[0] : $layout[3]) : $layout[0],
                ];
            }
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
     * 设置表单提交方式
     * @param string $value 提交方式
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function method($value = '')
    {
        if ($value != '') {
            $this->_vars['_method'] = $value;
            $this->_vars['ajax_submit'] = strtolower($value) == 'get' ? false : true;
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
            $module = $module == '' ? $this->request->module() : $module;
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
     * 设置ajax方式提交
     * @param bool $ajax_submit 默认true，false为关闭ajax方式提交
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function isAjax($ajax_submit = true)
    {
        $this->_vars['ajax_submit'] = $ajax_submit;
        return $this;
    }

    /**
     * 设置模版路径
     * @param string $template 模板路径
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
     * 根据表单项类型，加载不同js和css文件，并合并
     * @param string $type 表单项类型
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function loadMinify($type = '')
    {
        if ($type != '') {
            switch ($type) {
                case 'colorpicker':
                    $this->_vars['_js_files'][]  = 'colorpicker_js';
                    $this->_vars['_css_files'][] = 'colorpicker_css';
                    $this->_vars['_js_init'][]   = 'colorpicker';
                    break;
                case 'ckeditor':
                    $this->_vars['_ckeditor']  = '1';
                    $this->_vars['_js_init'][] = 'ckeditor';
                    break;
                case 'date':
                case 'daterange':
                    $this->_vars['_js_files'][]  = 'datepicker_js';
                    $this->_vars['_css_files'][] = 'datepicker_css';
                    $this->_vars['_js_init'][]   = 'datepicker';
                    break;
                case 'datetime':
                case 'time':
                    $this->_vars['_js_files'][]  = 'datetimepicker_js';
                    $this->_vars['_css_files'][] = 'datetimepicker_css';
                    $this->_vars['_js_init'][]   = 'datetimepicker';
                    break;
                case 'editormd':
                    $this->_vars['_js_files'][] = 'editormd_js';
                    $this->_vars['_editormd']   = '1';
                    break;
                case 'images':
                    $this->_vars['_js_files'][]  = 'jqueryui_js';
                case 'file':
                case 'files':
                case 'image':
                    $this->_vars['_js_files'][]  = 'webuploader_js';
                    $this->_vars['_css_files'][] = 'webuploader_css';
                    break;
                case 'icon':
                    $this->_vars['_icon'] = '1';
                    break;
                case 'jcrop':
                    $this->_vars['_js_files'][]  = 'jcrop_js';
                    $this->_vars['_css_files'][] = 'jcrop_css';
                    break;
                case 'linkage':
                case 'linkages':
                case 'select':
                case 'select2':
                    $this->_vars['_js_files'][]  = 'select2_js';
                    $this->_vars['_css_files'][] = 'select2_css';
                    $this->_vars['_js_init'][]   = 'select2';
                    break;
                case 'masked':
                    $this->_vars['_js_files'][] = 'masked_inputs_js';
                    break;
                case 'range':
                    $this->_vars['_js_files'][]  = 'rangeslider_js';
                    $this->_vars['_css_files'][] = 'rangeslider_css';
                    $this->_vars['_js_init'][]   = 'rangeslider';
                    break;
                case 'sort':
                    $this->_vars['_js_files'][]  = 'nestable_js';
                    $this->_vars['_css_files'][] = 'nestable_css';
                    break;
                case 'tags':
                    $this->_vars['_js_files'][]  = 'tags_js';
                    $this->_vars['_css_files'][] = 'tags_css';
                    $this->_vars['_js_init'][]   = 'tags-inputs';
                    break;
                case 'ueditor':
                    $this->_vars['_ueditor'] = '1';
                    break;
                case 'wangeditor':
                    $this->_vars['_js_files'][]  = 'wangeditor_js';
                    $this->_vars['_css_files'][] = 'wangeditor_css';
                    break;
                case 'summernote':
                    $this->_vars['_js_files'][]  = 'summernote_js';
                    $this->_vars['_css_files'][] = 'summernote_css';
                    $this->_vars['_js_init'][]   = 'summernote';
                    break;
            }
        } else {
            if ($this->_vars['form_items']) {
                foreach ($this->_vars['form_items'] as &$item) {
                    // 判断是否为分组
                    if ($item['type'] == 'group') {
                        foreach ($item['options'] as &$group) {
                            foreach ($group as $key => $value) {
                                if ($group[$key]['type'] != '') {
                                    $this->loadMinify($group[$key]['type']);
                                }
                            }
                        }
                    } else {
                        if ($item['type'] != '') {
                            $this->loadMinify($item['type']);
                        }
                    }
                }
            }
        }
    }

    /**
     * 设置表单项的值
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function setFormValue()
    {
        if ($this->_vars['form_data']) {
            foreach ($this->_vars['form_items'] as &$item) {
                // 判断是否为分组
                if ($item['type'] == 'group') {
                    foreach ($item['options'] as &$group) {
                        foreach ($group as $key => $value) {
                            // 针对日期范围特殊处理
                            switch ($value['type']) {
                                case 'daterange':
                                    if ($value['name_from'] == $value['name_to']) {
                                        list($group[$key]['value_from'], $group[$key]['value_to']) = $this->_vars['form_data'][$value['id']];
                                    } else {
                                        $group[$key]['value_from'] = $this->_vars['form_data'][$value['name_from']];
                                        $group[$key]['value_to']   = $this->_vars['form_data'][$value['name_to']];
                                    }
                                    break;
                                case 'datetime':
                                case 'date':
                                case 'time':
                                    if (isset($this->_vars['form_data'][$value['name']])) {
                                        $group[$key]['value'] = $this->_vars['form_data'][$value['name']];
                                    } else {
                                        $group[$key]['value'] = isset($value['value']) ? $value['value'] : '';
                                    }

                                    if (is_numeric($group[$key]['value'])) {
                                        if ($value['type'] == 'datetime' || $value['type'] == 'time') {
                                            $group[$key]['value'] = format_moment($group[$key]['value'], $value['format']);
                                        } else {
                                            $group[$key]['value'] = format_date($group[$key]['value'], $value['format']);
                                        }
                                    }
                                    break;
                                case 'bmap':
                                    $group[$key]['address'] = $this->_vars['form_data'][$value['name'].'_address'];
                                default:
                                    if (isset($this->_vars['form_data'][$value['name']])) {
                                        $group[$key]['value'] = $this->_vars['form_data'][$value['name']];
                                    } else {
                                        $group[$key]['value'] = '';
                                    }
                            }
                            if ($group[$key]['type'] == 'static' && $group[$key]['hidden'] != '') {
                                $group[$key]['hidden'] = $this->_vars['form_data'][$value['name']];
                            }
                        }
                    }
                } else {
                    // 针对日期范围特殊处理
                    switch ($item['type']) {
                        case 'daterange':
                            if ($item['name_from'] == $item['name_to']) {
                                list($item['value_from'], $item['value_to']) = $this->_vars['form_data'][$item['id']];
                            } else {
                                $item['value_from'] = $this->_vars['form_data'][$item['name_from']];
                                $item['value_to']   = $this->_vars['form_data'][$item['name_to']];
                            }
                            break;
                        case 'datetime':
                        case 'date':
                        case 'time':
                            if (isset($this->_vars['form_data'][$item['name']])) {
                                $item['value'] = $this->_vars['form_data'][$item['name']];
                            } else {
                                $item['value'] = isset($item['value']) ? $item['value'] : '';
                            }

                            if (is_numeric($item['value'])) {
                                if ($item['type'] == 'datetime' || $item['type'] == 'time') {
                                    $item['value'] = format_moment($item['value'], $item['format']);
                                } else {
                                    $item['value'] = format_date($item['value'], $item['format']);
                                }
                            }
                            break;
                        case 'bmap':
                            $item['address'] = $this->_vars['form_data'][$item['name'].'_address'];
                        default:
                            if (isset($this->_vars['form_data'][$item['name']])) {
                                $item['value'] = $this->_vars['form_data'][$item['name']];
                            } else {
                                $item['value'] = isset($item['value']) ? $item['value'] : '';
                            }

                    }
                    if ($item['type'] == 'static' && $item['hidden'] != '') {
                        $item['hidden'] = $this->_vars['form_data'][$item['name']];
                    }
                    // 处理拖拽排序组件
                    if ($item['type'] == 'sort') {
                        $value = explode(',', $item['value']);
                        $item['content'] = array_merge(array_flip($value), $item['content']);
                    }
                }
            }
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
        if (!empty($vars)) {
            $this->_vars['form_data'] = array_merge($this->_vars['form_data'], $vars);
        }

        // 设置表单值
        $this->setFormValue();

        // 处理不同表单类型加载不同js和css
        $this->loadMinify();

        // 处理页面标题
        if ($this->_vars['page_title'] == '' && defined('ENTRANCE') && ENTRANCE == 'admin') {
            $location = get_location('', false, false);
            if ($location) {
                $curr_location = end($location);
                $this->_vars['page_title'] = $curr_location['title'];
            }
        }

        // 另外设置模板
        if ($template != '') {
            $this->_template = $template;
        }

        // 处理需要隐藏的表单项，去除最后一个逗号
        if ($this->_vars['field_hide'] != '') {
            $this->_vars['field_hide'] = rtrim($this->_vars['field_hide'], ',');
        }
        if ($this->_vars['field_values'] != '') {
            $this->_vars['field_values'] = explode(',', $this->_vars['field_values']);
            $this->_vars['field_values'] = array_filter($this->_vars['field_values'], 'strlen');
            $this->_vars['field_values'] = implode(',', array_unique($this->_vars['field_values']));
        }

        // 处理js和css合并的参数
        if (!empty($this->_vars['_js_files'])) {
            $this->_vars['_js_files'] = array_unique($this->_vars['_js_files']);
            sort($this->_vars['_js_files']);
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

        // 处理额外按钮
        $this->_vars['btn_extra'] = implode(' ', $this->_vars['btn_extra']);

        // 实例化视图并渲染
        return parent::fetch($this->_template, $this->_vars, $config);
    }
}
