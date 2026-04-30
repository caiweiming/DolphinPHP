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

namespace app\common\render\form\items\color_select;

use app\common\abstract\FormItem;

/**
 * 颜色选择器组件
 */
class Item extends FormItem
{
    /**
     * 预定义的主题色
     */
    private const THEME_COLORS = [
        'dark'   => ['class' => 'bg-dark'],
        'white'  => ['class' => 'bg-white', 'label_class' => 'form-colorinput-light'],
        'blue'   => ['class' => 'bg-blue'],
        'azure'  => ['class' => 'bg-azure'],
        'indigo' => ['class' => 'bg-indigo'],
        'purple' => ['class' => 'bg-purple'],
        'pink'   => ['class' => 'bg-pink'],
        'red'    => ['class' => 'bg-red'],
        'orange' => ['class' => 'bg-orange'],
        'yellow' => ['class' => 'bg-yellow'],
        'lime'   => ['class' => 'bg-lime'],
        'green'  => ['class' => 'bg-green'],
        'teal'   => ['class' => 'bg-teal'],
        'cyan'   => ['class' => 'bg-cyan'],
    ];

    /**
     * 渲染颜色选择器
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $params['options'] = $this->formatColorOptions($params['options'] ?? []);
        $params['circle']  = $params['circle'] ?? false;
        if (isset($params['disabled']) && true === $params['disabled']) {
            $params['disabled'] = array_keys($params['options']);
        }

        // 处理多选
        $params['multiple'] = $params['multiple'] ?? false;
        if ($params['multiple']) {
            $params['name'] = $params['name'] . '[]';
        }

        return $params;
    }

    /**
     * 格式化颜色选项
     * @param array $options
     * @return array
     */
    private function formatColorOptions(array $options): array
    {
        $formattedOptions = [];

        foreach ($options as $key => $value) {
            [$colorKey, $colorValue] = is_numeric($key) ? [$value, $value] : [$key, $value];

            if (is_array($colorValue)) {
                $formattedOptions[$colorKey] = $colorValue;
                continue;
            }

            $formattedOptions[$colorKey] = $this->processColorValue($colorValue);
        }

        return $formattedOptions;
    }

    /**
     * 处理字符串类型的颜色值
     * @param string $color
     * @return string[]
     */
    private function processColorValue(string $color): array
    {
        // 检查是否是预定义的主题色
        if (isset(self::THEME_COLORS[$color])) {
            return self::THEME_COLORS[$color];
        }

        // 处理自定义颜色值
        return [
            'class'       => '',
            'style'       => 'background-color:' . $color,
            'label_class' => '',
            'circle'      => false
        ];
    }
}
