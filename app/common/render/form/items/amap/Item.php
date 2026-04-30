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

namespace app\common\render\form\items\amap;

use app\common\abstract\FormItem;

/**
 * 高德地图组件
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'options'       => [
            'key'         => '',
            'zoom'        => 15,
            'center'      => '',
            'height'      => 360,
            'region'      => '',
            'placeholder' => '搜索地址',
        ],
        'address_field' => '',
        'address'       => '',
        'readonly'      => false,
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $params = array_merge($this->default, $params);

        if (isset($params['options']) && is_string($params['options'])) {
            $raw = trim($params['options']);
            if ($raw !== '' && str_contains($raw, ',')) {
                [$key, $security] = array_map('trim', explode(',', $raw, 2));
                $params['options'] = [
                    'key'            => $key,
                    'securityJsCode' => $security,
                ];
            } else {
                $params['options'] = ['key' => $raw];
            }
        }

        $fieldName = $params['id'] ?? $params['name'] ?? '';
        if ($params['address_field'] === '' && $fieldName !== '') {
            $params['address_field'] = $fieldName . '_address';
        }

        $params['readonly']      = (bool)($params['readonly'] ?? false);
        $params['address_value'] = $params['address'] ?? '';
        $params['height']        = $params['options']['height'] ?? 360;
        $params['placeholder']   = $params['options']['placeholder'] ?? '搜索地址';

        $params['options'] = dp_parse_options($params['options'] ?? []);

        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'init' => ['amap']
        ];
    }
}
