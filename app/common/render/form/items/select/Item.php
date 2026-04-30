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

namespace app\common\render\form\items\select;

use app\common\abstract\FormItem;

/**
 * 下拉选择
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
        // 是否多选
        if (isset($params['multiple']) && true === $params['multiple']) {
            $params['id']   = $params['id'] ?? $params['name'];
            $params['name'] .= '[]';
        }

        // 处理禁用
        if (isset($params['disabled']) && (is_array($params['disabled']) || is_string($params['disabled']))) {
            $params['optionDisabled'] = $params['disabled'];
            $params['disabled']       = false;
        }

        if (isset($params['options']) && is_array($params['options']) && $this->isMultidimensional($params['options'])) {
            $params['group'] = $params['options'];
            unset($params['options']);
        }

        return $params;
    }

    /**
     * 判断是否为多维数组
     * @param array $array
     * @return bool
     */
    private function isMultidimensional(array $array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }
}
