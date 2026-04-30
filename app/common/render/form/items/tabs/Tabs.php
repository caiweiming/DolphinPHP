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

namespace app\common\render\form\items\tabs;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;

/**
 * 标签分组组件
 */
class Tabs extends FormType
{
    use Disabled;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'tabs';

    /**
     * 设置标签项等宽填充
     * @param bool $fill
     * @return $this
     */
    public function fill(bool $fill = true): static
    {
        $this->config['fill'] = $fill;
        return $this;
    }

    /**
     * 设置标签栏右对齐
     * @param bool $right
     * @return $this
     */
    public function right(bool $right = true): static
    {
        $this->config['right'] = $right;
        return $this;
    }
}
