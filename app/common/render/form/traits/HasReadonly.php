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
 * 设置表单项是否只读
 * @package app\common\render\form\traits
 */
trait HasReadonly
{
    /**
     * 设置表单项是否只读
     * @param bool $readonly
     * @return $this
     */
    public function readonly(bool $readonly = true): static
    {
        $this->config['readonly'] = $readonly;
        return $this;
    }
}
