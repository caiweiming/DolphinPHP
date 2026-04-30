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

namespace app\common\render\form\items\color;

use app\common\abstract\FormType;

/**
 * 取色器组件
 */
class Color extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'color';

    /**
     * 设置外层容器CSS类
     * @param string $class CSS类名
     * @return $this
     */
    public function groupClass(string $class = ''): static
    {
        $this->config['group_class'] = $class;
        return $this;
    }
}
