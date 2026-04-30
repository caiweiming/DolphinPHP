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
 * 设置表单项是否平铺样式
 * @package app\common\render\form\traits
 */
trait Flush
{
    /**
     * 设置表单项是否平铺样式
     * @param bool $flush
     * @return $this
     */
    public function flush(bool $flush = true): static
    {
        $this->config['flush'] = $flush;
        return $this;
    }
}
