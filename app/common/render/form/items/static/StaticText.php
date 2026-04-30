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

namespace app\common\render\form\items\static;

use app\common\abstract\FormType;

/**
 * 密码框组件
 */
class StaticText extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'static';

    /**
     * 设置发送数据
     * @param bool $send
     * @return $this
     */
    public function send(bool $send = true): static
    {
        $this->config['send'] = $send;
        return $this;
    }

    /**
     * 设置支持html
     * @param bool $raw
     * @return $this
     */
    public function raw(bool $raw = true): static
    {
        $this->config['raw'] = $raw;
        return $this;
    }
}
