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

namespace app\common\render\form\items\switch;

use app\common\abstract\FormItem;

/**
 * 开关
 */
class Item extends FormItem
{
    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        if (empty($params['options'])) {
            $params['options'] = [
                $params['name'] => [
                    'value' => $params['value'] ?? '',
                    'label' => $params['title'] ?? '',
                    'id'    => ''
                ]
            ];
        } else {
            if (is_string($params['options'])) {
                $params['options'] = [
                    $params['name'] => [
                        'value' => $params['value'],
                        'label' => $params['options'],
                        'id'    => '-' . $params['name']
                    ]
                ];
            } else {
                $options = [];
                $values  = isset($params['value']) ? (is_string($params['value']) ? explode(',', $params['value']) : $params['value']) : [];
                $values  = (array)$values;
                foreach ($params['options'] as $key => $option) {
                    if (is_numeric($key)) {
                        $options[$option] = [
                            'value' => in_array($option, $values) ? 1 : 0,
                            'label' => '',
                            'id'    => '-' . $key
                        ];
                    } else {
                        $options[$key] = [
                            'value' => in_array($key, $values) ? 1 : 0,
                            'label' => $option,
                            'id'    => '-' . $key
                        ];
                    }
                }
                $params['options'] = $options;
            }
        }

        // 处理禁用
        if (isset($params['disabled']) && true === $params['disabled']) {
            $params['disabled'] = array_keys($params['options']);
        }
        return $params;
    }
}
