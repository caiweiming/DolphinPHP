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

namespace app\common\render\form\items\checkbox;

use app\common\abstract\FormItem;

/**
 * 多选框
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

        $this->parseRemark($params);
        return $params;
    }

    /**
     * 分析选项描述
     * @param array $params
     */
    private function parseRemark(array &$params): void
    {
        $params['remark'] = $params['remark'] ?? [];
        foreach ($params['options'] as $key => $option) {
            if (str_contains($option, '#')) {
                list($option, $remark) = explode('#', $option);
                $params['remark'][$key]  = $remark;
                $params['options'][$key] = $option;
            }
        }
    }
}
