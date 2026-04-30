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

namespace app\common\render\form\items\select2;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Multiple;
use app\common\render\form\traits\Placeholder;

/**
 * select2组件
 */
class Select2 extends FormType
{
    use Disabled;
    use Placeholder;
    use Multiple;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'select2';

    /**
     * 设置异步加载参数
     * @param mixed $ajax
     * @return $this
     */
    public function ajax(mixed $ajax = []): static
    {
        $this->config['ajax'] = $ajax;
        return $this;
    }
}
