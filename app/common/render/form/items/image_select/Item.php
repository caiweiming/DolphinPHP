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

namespace app\common\render\form\items\image_select;

use app\common\abstract\FormItem;

/**
 * 图片选择
 */
class Item extends FormItem
{
    /**
     * 默认配置
     * @var array
     */
    private array $default = [
        'multiple' => false,    // 是否多选
        'disabled' => false,    // 是否禁用
        'class'    => '',      // 自定义CSS类
        'options'  => []       // 图片列表
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        // 处理默认参数
        $params = array_merge($this->default, $params);

        // 处理图片列表
        foreach ($params['options'] as $key => $option) {
            if (is_string($option)) {
                $params['options'][$key] = [
                    'src'   => $option,
                    'class' => $params['class']
                ];
            } else {
                $params['options'][$key]['class'] = $option['class'] ?? $params['class'];
            }
        }

        // 处理禁用
        if (true === $params['disabled']) {
            $params['disabled'] = array_keys($params['options']);
        }

        // 处理多选
        if ($params['multiple']) {
            $params['name'] = $params['name'] . '[]';
        }
        return $params;
    }
}
