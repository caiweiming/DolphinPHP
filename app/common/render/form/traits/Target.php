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
 * 表单项目标
 * @package app\common\render\form\traits
 */
trait Target
{
    /**
     * 设置表单项目标
     * @param mixed $target 链接打开方式
     * @return $this
     */
    public function target(mixed $target = true): static
    {
        $this->config['target'] = $target;
        return $this;
    }
}
