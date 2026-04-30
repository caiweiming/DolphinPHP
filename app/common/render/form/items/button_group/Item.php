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

namespace app\common\render\form\items\button_group;

use app\common\abstract\FormItem;

/**
 * 按钮组
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'group_class' => 'btn-group',
        'class'       => '',
        'vertical'    => false,
        'icon'        => false
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        // 合并参数
        $params = array_merge($this->default, $params);
        // 处理垂直
        if ($params['vertical']) {
            $params['group_class'] = ' btn-group-vertical';
        }
        return $params;
    }
}
