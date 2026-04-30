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

namespace app\common\render\form\items\checkbox;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Inline;

/**
 * 多选框组件
 */
class Checkbox extends FormType
{
    use Disabled;
    use Inline;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'checkbox';

    /**
     * 设置选项备注说明
     * @param array $remark 备注数组，键为选项的key，值为备注文本
     * @return $this
     */
    public function remark(array $remark = []): static
    {
        $this->config['remark'] = $remark;
        return $this;
    }
}
