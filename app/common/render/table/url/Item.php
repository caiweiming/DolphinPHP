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

namespace app\common\render\table\url;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 链接
 */
class Item extends TableItem
{
    /**
     * handle
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $table->extraJs(
            $this->createTemplet($column, [
                'href'   => $this->getUrl($column),
                'dialog' => $this->getDialog($column),
                'target' => $column['options']['target'] ?? '_blank',
            ])
        );
        return $column;
    }

    /**
     * 获取链接
     * @param array $column
     * @return mixed
     */
    private function getUrl(array $column): mixed
    {
        $href = $column['options']['href'] ?? null;
        if (is_string($href)) {
            $href = preg_replace('/__(.*?)__/', '{{=d.$1}}', $href);
        }
        return $href;
    }

    /**
     * 获取弹窗配置
     * @param array $column
     * @return array|string[]
     */
    private function getDialog(array $column): array
    {
        $dialog = $column['options']['pop'] ?? false;
        if (empty($dialog)) {
            return [];
        }

        return match (true) {
            is_string($dialog) => ['title' => $dialog],
            is_array($dialog) => $dialog,
            true === $dialog => ['type' => 2],
            default => []
        };
    }
}
