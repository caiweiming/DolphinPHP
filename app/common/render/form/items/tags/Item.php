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

namespace app\common\render\form\items\tags;

use app\common\abstract\FormItem;

/**
 * 标签
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
            // 处理白名单
            if (isset($params['options']['whitelist']) && is_string($params['options']['whitelist'])) {
                $params['options']['whitelist'] = explode(',', $params['options']['whitelist']);
            }
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
            'js'    => [
                '__LIBS__/tagify/dist/tagify.js',
                '__LIBS__/tagify/dist/tagify.polyfills.min.js',
                '__LIBS__/dragsort/dist/dragsort.js',
            ],
            'css'   => [
                '__LIBS__/tagify/dist/tagify.css',
                '__LIBS__/dragsort/dist/dragsort.css',
            ],
            'init'  => ['tags']
        ];
    }
}
