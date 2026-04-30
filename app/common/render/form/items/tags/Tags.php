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

namespace app\common\render\form\items\tags;

use app\common\abstract\FormType;

/**
 * 标签组件
 */
class Tags extends FormType
{

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'tags';

    /**
     * 设置表单项是否只读
     * @return $this
     */
    public function readonly(): static
    {
        $this->config['props'] = 'readonly';
        return $this;
    }
}
