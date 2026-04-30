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

namespace app\common\render\form\items\time;

use app\common\abstract\FormItem;
use app\common\render\form\items\datetime\Item as DatetimeItem;

/**
 * 时间选择
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
        $item = new DatetimeItem();
        $params = $item->handle($params, false);

        // 处理默认参数
        $params['options']['onlyTimepicker'] = true;
        $params['options']['buttons'] = '';
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
            'js'    => [
                '__LIBS__/air-datepicker/air-datepicker.js',
                '__LIBS__/air-datepicker/locale/zh.js',
            ],
            'css'   => [
                '__LIBS__/air-datepicker/air-datepicker.css'
            ],
            'init'  => ['datetime-picker']
        ];
    }
}
