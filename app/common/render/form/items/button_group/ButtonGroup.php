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

namespace app\common\render\form\items\button_group;

use app\common\abstract\FormType;

/**
 * 按钮组组件
 */
class ButtonGroup extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'button_group';

    /**
     * 设置垂直布局
     * @param bool $vertical
     * @return $this
     */
    public function vertical(bool $vertical = true): static
    {
        $this->config['vertical'] = $vertical;
        return $this;
    }

    /**
     * 设置为图标按钮
     * @param bool $icon
     * @return $this
     */
    public function icon(bool $icon = true): static
    {
        $this->config['icon'] = $icon;
        return $this;
    }

    /**
     * 设置按钮组CSS类
     * @param string $class
     * @return $this
     */
    public function groupClass(string $class = ''): static
    {
        $this->config['group_class'] = $class;
        return $this;
    }
}
