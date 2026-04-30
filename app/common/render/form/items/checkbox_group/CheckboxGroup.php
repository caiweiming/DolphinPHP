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

namespace app\common\render\form\items\checkbox_group;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;

/**
 * 多选标签组组件（卡片式布局）
 */
class CheckboxGroup extends FormType
{
    use Disabled;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'checkbox_group';
}
