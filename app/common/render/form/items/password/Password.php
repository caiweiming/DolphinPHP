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

namespace app\common\render\form\items\password;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Flush;
use app\common\render\form\traits\HasFloat;
use app\common\render\form\traits\HasReadonly;
use app\common\render\form\traits\LengthLimit;
use app\common\render\form\traits\Placeholder;
use app\common\render\form\traits\Prefix;
use app\common\render\form\traits\Rounded;
use app\common\render\form\traits\Suffix;
use app\common\render\form\traits\Validatable;

/**
 * 密码框组件
 */
class Password extends FormType
{
    use Disabled;
    use Placeholder;
    use Validatable;
    use Rounded;
    use Flush;
    use HasReadonly;
    use HasFloat;
    use LengthLimit;
    use Prefix;
    use Suffix;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'password';

    /**
     * 设置显示/隐藏密码开关
     * @param bool $switch
     * @return $this
     */
    public function switch(bool $switch = true): static
    {
        $this->config['switch'] = $switch;
        return $this;
    }

    /**
     * 设置密码强度提示
     * @param bool $strength
     * @return $this
     */
    public function strength(bool $strength = true): static
    {
        $this->config['strength'] = $strength;
        return $this;
    }

}
