<?php
declare (strict_types=1);

namespace form\transfer;

use app\common\abstract\FormItem;

/**
 * 穿梭框
 * @package form\transfer
 */
class Item extends FormItem
{
    /**
     * 入口方法
     * @param mixed $params
     * @return array
     */
    public function handle(array $params = []): array
    {
        $params['props'] = dp_parse_options($params['props'] ?? []);
        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'  => [
                '__EXTEND_FORM__/transfer/bootstrap-duallistbox/jquery.bootstrap-duallistbox.min.js',
                '__EXTEND_FORM__/transfer/transfer.js'
            ],
            'css' => [
                '__EXTEND_FORM__/transfer/bootstrap-duallistbox/bootstrap-duallistbox.min.css',
                '__EXTEND_FORM__/transfer/transfer.css'
            ]
        ];
    }
}