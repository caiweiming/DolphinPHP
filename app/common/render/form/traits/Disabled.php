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
 * 禁用
 * @package app\common\render\form\traits
 */
trait Disabled
{
    /**
     * 设置表单项是否禁用
     * @param mixed $disabled true-禁用，false-启用，或其他参数
     * @return $this
     */
    public function disabled(mixed $disabled = true): static
    {
        $this->config['disabled'] = $disabled;
        return $this;
    }
}
