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

namespace app\common\render\form\items\select2;

use app\common\abstract\FormItem;
use app\common\render\form\items\select\Item as SelectItem;

/**
 * 下拉选择2
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
        $item = new SelectItem();
        $params = $item->handle($params);

        // 异步加载
        if (isset($params['ajax'])) {
            if (is_array($params['ajax'])) {
                if (!isset($params['ajax']['url'])) {
                    $params['ajax']['url'] = dp_url('admin/api/getSelectAjax');
                }

                $params['ajax']['url']   = (string) $params['ajax']['url'];
                $params['ajax']['token'] = dp_data_token($params['ajax']);
            } else {
                $params['ajax'] = ['url' => $params['ajax']];
            }
        }

        // select2参数
        // 参考：https://select2.org/configuration/options-api
        if (!empty($params['_options'])) {
            $params['_options'] = dp_parse_options($params['_options']);
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
                '__LIBS__/select2/js/select2.full.min.js',
                '__LIBS__/select2/js/i18n/zh-CN.js',
            ],
            'css'   => [
                '__LIBS__/select2/css/select2.min.css',
                '__LIBS__/select2/themes/bootstrap5/select2-bootstrap-5-theme.min.css',
            ],
            'init'  => ['select2']
        ];
    }
}
