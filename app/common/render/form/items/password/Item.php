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

namespace app\common\render\form\items\password;

use app\common\abstract\FormItem;
use app\common\render\form\items\text\Item as TextItem;
use Exception;

/**
 * 密码框
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
        $item   = new TextItem();
        $params = $item->handle($params);
        if (!empty($params['switch'])) {
            $params['input_class'] .= ' input-group input-group-flat';
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
            'init' => ['password']
        ];
    }
}
