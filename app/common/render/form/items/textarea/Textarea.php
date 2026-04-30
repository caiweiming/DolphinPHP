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

namespace app\common\render\form\items\textarea;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Flush;
use app\common\render\form\traits\HasReadonly;
use app\common\render\form\traits\LengthLimit;
use app\common\render\form\traits\Placeholder;

/**
 * 多行文本框组件
 */
class Textarea extends FormType
{
    use Flush;
    use Disabled;
    use HasReadonly;
    use LengthLimit;
    use Placeholder;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'textarea';

    /**
     * 文本框默认行数
     * @param string|int $rows
     * @return $this
     */
    public function rows(string|int $rows = ''): static
    {
        $this->config['rows'] = $rows !== '' ? intval($rows) : '';
        return $this;
    }

    /**
     * 自动调整文本框高度
     * @param bool $autosize
     * @return $this
     */
    public function autosize(bool $autosize = true): static
    {
        $this->config['autosize'] = $autosize;
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
}
