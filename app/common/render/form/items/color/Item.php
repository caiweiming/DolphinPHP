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

namespace app\common\render\form\items\color;

use app\common\abstract\FormItem;

/**
 * 取色器
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
        if (isset($params['options'])) {
            $params['options'] = dp_parse_options($params['options']);
        }
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
                '__THEME_LIBS__/coloris/dist/umd/coloris.min.js',
            ],
            'css'  => [
                '__THEME_LIBS__/coloris/dist/coloris.min.css',
            ],
            'init' => ['color-picker']
        ];
    }
}
