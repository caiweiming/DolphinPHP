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

namespace app\common\render\form\items\checkbox_group;

use app\common\abstract\FormItem;

/**
 * 多选标签组
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
        if (isset($params['disabled']) && true === $params['disabled']) {
            $params['disabled'] = array_keys($params['options']);
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
            'css' => [
                '__THEME_CSS__/tabler-flags.min.css',
                '__THEME_CSS__/tabler-payments.min.css',
            ],
        ];
    }
}
