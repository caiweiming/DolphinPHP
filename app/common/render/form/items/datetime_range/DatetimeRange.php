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

namespace app\common\render\form\items\datetime_range;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\DatetimeIcon;
use app\common\render\form\traits\HasReadonly;
use app\common\render\form\traits\Placeholder;
use app\common\render\form\traits\Rounded;
use app\common\render\form\traits\Flush;

/**
 * 日期时间范围选择器组件
 */
class DatetimeRange extends FormType
{
    use Placeholder;
    use Rounded;
    use Flush;
    use DatetimeIcon;
    use Disabled;
    use HasReadonly;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'datetime_range';
}
