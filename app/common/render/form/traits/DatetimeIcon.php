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
 * 设置图标位置-用于日期时间选择
 * @package app\common\render\form\traits
 */
trait DatetimeIcon
{
    /**
     * 设置图标位置
     * @param string $position 位置（left/right）
     * @return $this
     */
    public function icon(string $position = 'left'): static
    {
        $this->config['icon'] = $position;
        return $this;
    }
}
