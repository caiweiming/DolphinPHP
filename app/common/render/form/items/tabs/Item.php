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

namespace app\common\render\form\items\tabs;

use app\common\abstract\FormItem;
use app\common\abstract\FormType;
use app\common\render\Form as FormRender;
use Exception;

/**
 * 标签分组
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'options'  => [],
        'disabled' => [],
        'right'    => false,
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function handle(array $params = []): array
    {
        // 合并参数
        $params = array_merge($this->default, $params);

        // 处理禁用
        if (is_string($params['disabled'])) {
            $params['disabled'] = explode(',', $params['disabled']);
        }

        foreach ($params['options'] as $key => $option) {
            $option['right']     = $option['right'] ?? false;
            $option['disabled']  = $option['disabled'] ?? ($params['disabled'] === true || in_array($key, $params['disabled']));
            $option['icon_only'] = trim(strip_tags((string) ($option['title'] ?? ''))) === '' && trim((string) ($option['icon'] ?? '')) !== '';
            $option['content']   = $this->parseTabsContent($option);

            $params['options'][$key] = $option;
        }

        $params['options'] = array_values($params['options']);
        return $params;
    }

    /**
     * 解析标签分组内容
     * @param array $option
     * @return mixed|string
     * @throws Exception
     */
    private function parseTabsContent(array $option): mixed
    {
        $content = $option['content'] ?? '';
        if (is_array($content)) {
            $FormRender = FormRender::make('tab_form_' . substr(sha1(json_encode($option)), 0, 8));
            foreach ($content as $key => $item) {
                if ($item instanceof FormType) {
                    $item = $item->toArray();
                }
                if (is_array($item)) {
                    $content[$key] = $FormRender->item($item, true);
                }
            }
            $content = implode('', $content);
        }
        return $content;
    }
}
