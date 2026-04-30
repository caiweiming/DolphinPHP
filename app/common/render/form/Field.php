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
declare (strict_types=1);

namespace app\common\render\form;

use app\common\render\form\items\button\Button;
use app\common\render\form\items\date_range\DateRange;
use app\common\render\form\items\datetime_range\DatetimeRange;
use app\common\render\form\items\text\Text;
use app\common\render\form\items\textarea\Textarea;
use app\common\render\form\items\switch\Toggle;
use app\common\render\form\items\table\Table;
use app\common\render\form\items\select2\Select2;
use app\common\render\form\items\password\Password;
use app\common\render\form\items\button_group\ButtonGroup;
use app\common\render\form\items\checkbox\Checkbox;
use app\common\render\form\items\checkbox_group\CheckboxGroup;
use app\common\render\form\items\color\Color;
use app\common\render\form\items\color_select\ColorSelect;
use app\common\render\form\items\cropper\Cropper;
use app\common\render\form\items\date\Date;
use app\common\render\form\items\time\Time;
use app\common\render\form\items\datetime\Datetime;
use app\common\render\form\items\file\File;
use app\common\render\form\items\hidden\Hidden;
use app\common\render\form\items\html\Html;
use app\common\render\form\items\image\Image;
use app\common\render\form\items\image_select\ImageSelect;
use app\common\render\form\items\linkage\Linkage;
use app\common\render\form\items\linkages\Linkages;
use app\common\render\form\items\mask\Mask;
use app\common\render\form\items\number\Number;
use app\common\render\form\items\radio\Radio;
use app\common\render\form\items\radio_group\RadioGroup;
use app\common\render\form\items\icon\Icon;
use app\common\render\form\items\bmap\Bmap;
use app\common\render\form\items\qmap\Qmap;
use app\common\render\form\items\amap\Amap;
use app\common\render\form\items\select\Select;
use app\common\render\form\items\select_group\SelectGroup;
use app\common\render\form\items\static\StaticText;
use app\common\render\form\items\tabs\Tabs;
use app\common\render\form\items\tags\Tags;
use app\common\render\form\items\ueditor\Ueditor;
use app\common\render\form\items\vditor\Vditor;
use BadMethodCallException;

/**
 * Field 门面类
 * 提供统一的静态创建入口
 *
 * @method static Text text(string $name, string $label = '', string $tips = '') 单行文本框
 * @method static Textarea textarea(string $name, string $label = '', string $tips = '') 多行文本框
 * @method static Toggle switch (string $name, string $label = '', string $tips = '') 开关
 * @method static Table table(string $name, string $label = '', string $tips = '') 表格展示组件
 * @method static Select2 select2(string $name, string $label = '', string $tips = '') Select2选择器
 * @method static Password password(string $name, string $label = '', string $tips = '') 密码框
 * @method static Button button(string $name, string $label = '', string $tips = '') 按钮组件
 * @method static ButtonGroup buttonGroup(string $name, string $label = '', string $tips = '') 按钮组组件
 * @method static Checkbox checkbox(string $name, string $label = '', string $tips = '') 多选框组件
 * @method static CheckboxGroup checkboxGroup(string $name, string $label = '', string $tips = '') 多选标签组组件（卡片式布局）
 * @method static Color color(string $name, string $label = '', string $tips = '') 取色器组件
 * @method static ColorSelect colorSelect(string $name, string $label = '', string $tips = '') 颜色选择组件
 * @method static Cropper cropper(string $name, string $label = '', string $tips = '') 图片裁剪器组件
 * @method static Date date(string $name, string $label = '', string $tips = '') 日期选择器
 * @method static DateRange dateRange(string $name, string $label = '', string $tips = '') 日期范围选择器
 * @method static Datetime datetime(string $name, string $label = '', string $tips = '') 日期时间选择器
 * @method static DatetimeRange datetimeRange(string $name, string $label = '', string $tips = '') 日期时间范围选择器
 * @method static Time time(string $name, string $label = '', string $tips = '') 时间选择器
 * @method static File file(string $name, string $label = '', string $tips = '') 文件上传组件
 * @method static Hidden hidden(string $name, string $label = '', string $tips = '') 隐藏域组件
 * @method static Html html(string $name, string $label = '', string $tips = '') HTML组件
 * @method static Image image(string $name, string $label = '', string $tips = '') 图片上传组件
 * @method static ImageSelect imageSelect(string $name, string $label = '', string $tips = '') 图片选择组件
 * @method static Mask mask(string $name, string $label = '', string $tips = '') 格式文本组件
 * @method static Number number(string $name, string $label = '', string $tips = '') 数字框组件
 * @method static Radio radio(string $name, string $label = '', string $tips = '') 单选框组件
 * @method static RadioGroup radioGroup(string $name, string $label = '', string $tips = '') 单选标签组组件
 * @method static Icon icon(string $name, string $label = '', string $tips = '') 图标选择器组件
 * @method static Linkage linkage(string $name, string $label = '', string $tips = '') 多级联动组件
 * @method static Linkages linkages(string $name, string $label = '', string $tips = '') 快速联动组件
 * @method static Amap amap(string $name, string $label = '', string $tips = '') 高德地图组件
 * @method static Bmap bmap(string $name, string $label = '', string $tips = '') 百度地图组件
 * @method static Qmap qmap(string $name, string $label = '', string $tips = '') 腾讯地图组件
 * @method static Select select(string $name, string $label = '', string $tips = '') 下拉选择组件
 * @method static SelectGroup selectGroup(string $name, string $label = '', string $tips = '') 标签多选组件
 * @method static StaticText static (string $name, string $label = '', string $tips = '') 静态文本组件
 * @method static Tabs tabs(string $name, string $label = '', string $tips = '') 标签分组组件
 * @method static Tags tags(string $name, string $label = '', string $tips = '') 标签组件
 * @method static Ueditor ueditor(string $name, string $label = '', string $tips = '') UEditor编辑器组件
 * @method static Vditor vditor(string $name, string $label = '', string $tips = '') Vditor编辑器组件
 *
 * @package app\common\render\form
 */
class Field
{
    /**
     * 组件类映射表
     * @var array<string, class-string>
     */
    private static array $componentMap = [
        'text'           => Text::class,
        'textarea'       => Textarea::class,
        'switch'         => Toggle::class,
        'table'          => Table::class,
        'select2'        => Select2::class,
        'password'       => Password::class,
        'button'         => Button::class,
        'button_group'   => ButtonGroup::class,
        'checkbox'       => Checkbox::class,
        'checkbox_group' => CheckboxGroup::class,
        'color'          => Color::class,
        'color_select'   => ColorSelect::class,
        'cropper'        => Cropper::class,
        'date'           => Date::class,
        'date_range'     => DateRange::class,
        'datetime'       => Datetime::class,
        'datetime_range' => DatetimeRange::class,
        'time'           => Time::class,
        'file'           => File::class,
        'hidden'         => Hidden::class,
        'html'           => Html::class,
        'image'          => Image::class,
        'image_select'   => ImageSelect::class,
        'mask'           => Mask::class,
        'number'         => Number::class,
        'radio'          => Radio::class,
        'radio_group'    => RadioGroup::class,
        'icon'           => Icon::class,
        'linkage'        => Linkage::class,
        'linkages'       => Linkages::class,
        'amap'           => Amap::class,
        'qmap'           => Qmap::class,
        'bmap'           => Bmap::class,
        'select'         => Select::class,
        'select_group'   => SelectGroup::class,
        'static'         => StaticText::class,
        'tabs'           => Tabs::class,
        'tags'           => Tags::class,
        'ueditor'        => Ueditor::class,
        'vditor'         => Vditor::class,
    ];

    /**
     * 魔术方法：动态调用组件创建方法
     * @param string $method 方法名（对应组件类型）
     * @param array $arguments 参数列表
     * @return mixed
     * @throws BadMethodCallException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $component = self::$componentMap[$method] ?? null;
        if ($component === null) {
            $normalized = dp_normalize_extension_path($method);
            $component  = self::$componentMap[$normalized] ?? null;
        }

        if ($component === null) {
            throw new BadMethodCallException("未定义的表单组件方法: $method");
        }

        return $component::make(...$arguments);
    }
}
