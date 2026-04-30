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
 * 表单项链接
 * @package app\common\render\form\traits
 */
trait Href
{
    /**
     * 设置表单项链接
     * @param mixed $href 链接
     * @return $this
     */
    public function href(mixed $href = true): static
    {
        $this->config['href'] = $href;
        return $this;
    }
}
