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

namespace app\common\render\form\items\datetime;

use app\common\abstract\FormItem;
use app\common\render\Form as FormRender;

/**
 * 日期时间选择
 */
class Item extends FormItem
{
    /**
     * 默认配置
     * @var array
     */
    private array $default = [
        'icon'    => 'left',
        'options' => [],
        'buttons' => '',
        'inline'  => false,
    ];

    /**
     * 渲染
     * @param array $params 参数
     * @param bool|FormRender $escapeOptions 是否转义选项
     * @return array
     */
    public function handle(array $params = [], bool|FormRender $escapeOptions = true): array
    {
        // 处理默认参数
        $params = array_merge($this->default, $params);

        // 处理内嵌
        $params['inline'] = $params['options']['inline'] ?? false;

        // 设置选择的日期
        if (!empty($params['value'])) {
            if (is_array($params['value'])) {
                $params['options']['selectedDates'] = $params['value'];
                $params['value'] = implode(',', $params['value']);
            } else {
                $params['options']['selectedDates'] = explode(',', $params['value']);
            }
        }

        // 开启时间选择
        if (!isset($params['options']['timepicker'])) {
            $params['options']['timepicker'] = true;
        }

        if (false === $escapeOptions) {
            return $params;
        }

        $params['options'] = dp_parse_options($params['options']);
        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'   => [
                '__LIBS__/air-datepicker/air-datepicker.js',
                '__LIBS__/air-datepicker/locale/zh.js',
            ],
            'css'  => [
                '__LIBS__/air-datepicker/air-datepicker.css'
            ],
            'init' => ['datetime-picker']
        ];
    }
}
