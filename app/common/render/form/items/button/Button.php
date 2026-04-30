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

namespace app\common\render\form\items\button;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Href;
use app\common\render\form\traits\Target;

/**
 * 按钮组件
 */
class Button extends FormType
{
    use Disabled;
    use Href;
    use Target;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'button';

    /**
     * 设置颜色
     * @param string $color
     * @return $this
     */
    public function color(string $color = ''): static
    {
        $this->config['color'] = $color;
        return $this;
    }

    /**
     * 设置按钮形状
     * @param string $shape square-方形，pill-药丸形
     * @return $this
     */
    public function shape(string $shape = ''): static
    {
        $this->config['shape'] = $shape;
        return $this;
    }

    /**
     * 方形按钮
     * @return $this
     */
    public function square(): static
    {
        $this->config['shape'] = 'square';
        return $this;
    }

    /**
     * 药丸按钮
     * @return $this
     */
    public function pill(): static
    {
        $this->config['shape'] = 'pill';
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon
     * @return $this
     */
    public function icon(string $icon = ''): static
    {
        $this->config['icon'] = $icon;
        return $this;
    }

    /**
     * 设置svg图标
     * @param string $svg
     * @return $this
     */
    public function svg(string $svg = ''): static
    {
        $this->config['svg'] = $svg;
        return $this;
    }
}
