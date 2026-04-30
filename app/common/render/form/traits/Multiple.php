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

namespace app\common\render\form\traits;

/**
 * 多选
 * @package app\common\render\form\traits
 */
trait Multiple
{
    /**
     * 设置表单项是否支持多选
     * @param mixed $multiple true-多选，false-单选
     * @return $this
     */
    public function multiple(mixed $multiple = true): static
    {
        $this->config['multiple'] = $multiple;
        return $this;
    }
}
