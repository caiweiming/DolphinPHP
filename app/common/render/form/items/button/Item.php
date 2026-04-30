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

namespace app\common\render\form\items\button;

use app\common\abstract\FormItem;

/**
 * 按钮
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'title' => '',
        'label' => '',
        'class' => '',
        'icon'  => '',
        'color' => 'primary',
        'href'  => false,
        'a'     => false,
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

        if ($params['icon'] != '') {
            if ($params['label'] != '') {
                $params['icon'] = '<i class="dp-icon '.$params['icon'].'"></i>';
            } else {
                $params['icon'] = '<i class="'.$params['icon'].'"></i>';
                $params['class'] .= ' btn-icon';
            }
        }

        if (isset($params['svg']) && $params['svg'] != '') {
            $params['icon'] = $params['svg'];
        }

        if (false !== $params['href']) {
            $params['a'] = true;
        }

        return $params;
    }
}
