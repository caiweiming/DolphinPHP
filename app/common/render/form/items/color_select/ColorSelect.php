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

namespace app\common\render\form\items\color_select;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Multiple;

/**
 * 颜色选择组件
 */
class ColorSelect extends FormType
{
    use Disabled;
    use Multiple;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'color_select';

    /**
     * 设置圆形样式
     * @param bool $circle 是否为圆形
     * @return $this
     */
    public function circle(bool $circle = true): static
    {
        $this->config['circle'] = $circle;
        return $this;
    }
}
