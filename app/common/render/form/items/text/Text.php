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

namespace app\common\render\form\items\text;

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
 * 单行文本框组件
 */
class Text extends FormType
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
    protected string $type = 'text';

    /**
     * 设置前后缀类型
     * @param string $type 前后缀类型：text-文本，icon-图标，button-按钮
     * @return $this
     */
    public function groupType(string $type = ''): static
    {
        $this->config['group_type'] = $type;
        return $this;
    }

    /**
     * 设置额外class
     * @param string $class
     * @return $this
     */
    public function extraClass(string $class = ''): static
    {
        $this->config['extra_class'] = $class;
        return $this;
    }

    /**
     * 设置数据列表
     * @param array $datalist
     * @return $this
     */
    public function datalist(array $datalist = []): static
    {
        $this->config['datalist'] = $datalist;
        return $this;
    }

    /**
     * maxLength的别名
     * @param string|int $length
     * @return $this
     */
    public function max(string|int $length): static
    {
        return $this->maxLength($length);
    }

    /**
     * minLength的别名
     * @param string|int $length
     * @return $this
     */
    public function min(string|int $length): static
    {
        return $this->minLength($length);
    }
}
