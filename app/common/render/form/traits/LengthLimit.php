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
 * 长度限制
 * @package app\common\render\form\traits
 */
trait LengthLimit
{
    /**
     * 设置最大长度
     * @param string|int $length 最大长度
     * @return $this
     */
    public function maxLength(string|int $length = ''): static
    {
        $this->config['max_length'] = $length !== '' ? intval($length) : '';
        return $this;
    }

    /**
     * 设置最小长度
     * @param string|int $length 最小长度
     * @return $this
     */
    public function minLength(string|int $length = ''): static
    {
        $this->config['min_length'] = $length !== '' ? intval($length) : '';
        return $this;
    }
}
