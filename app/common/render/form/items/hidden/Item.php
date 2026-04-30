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

namespace app\common\render\form\items\hidden;

use Exception;
use app\common\abstract\FormItem;

/**
 * 隐藏域组件
 */
class Item extends FormItem
{
    /**
     * 渲染
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function handle(array $params = []): array
    {
        return $params;
    }
}
