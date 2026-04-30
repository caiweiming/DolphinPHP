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

namespace app\common\render\form\items\select;

use app\common\abstract\FormType;
use app\common\render\form\traits\Disabled;
use app\common\render\form\traits\Multiple;
use app\common\render\form\traits\Placeholder;

/**
 * 下拉选择组件
 */
class Select extends FormType
{
    use Disabled;
    use Placeholder;
    use Multiple;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'select';

    /**
     * 设置分组菜单
     * @param mixed $group
     * @return $this
     */
    public function group(mixed $group = []): static
    {
        $this->config['group'] = $group;
        return $this;
    }
}
