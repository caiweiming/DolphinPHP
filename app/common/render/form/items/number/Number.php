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

namespace app\common\render\form\items\number;

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
 * 数字框组件
 */
class Number extends FormType
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
    protected string $type = 'number';

    /**
     * 设置步进值
     * @param string|int $step
     * @return $this
     */
    public function step(string|int $step = ''): static
    {
        $this->config['step'] = $step !== '' ? intval($step) : '';
        return $this;
    }

    /**
     * 设置最大长度
     * @param string|int $length 最大长度
     * @return $this
     */
    public function max(string|int $length = ''): static
    {
        return $this->maxLength($length);
    }

    /**
     * 设置最小长度
     * @param string|int $length 最小长度
     * @return $this
     */
    public function min(string|int $length = ''): static
    {
        return $this->minLength($length);
    }
}
