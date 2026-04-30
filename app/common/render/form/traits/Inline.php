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
 * 内嵌显示
 * @package app\common\render\form\traits
 */
trait Inline
{
    /**
     * 设置是否内嵌显示
     * @param bool $inline 是否内嵌
     * @return $this
     */
    public function inline(bool $inline = true): static
    {
        $this->config['inline'] = $inline;
        return $this;
    }
}
