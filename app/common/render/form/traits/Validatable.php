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
 * 设置表单项验证样式
 * @package app\common\render\form\traits
 */
trait Validatable
{
    /**
     * 设置表单项验证样式
     * @param bool $valid true-绿色，false-红色
     * @return $this
     */
    public function valid(bool $valid = true): static
    {
        $this->config['valid'] = $valid;
        return $this;
    }
}
