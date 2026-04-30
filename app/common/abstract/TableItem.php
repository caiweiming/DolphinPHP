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

namespace app\common\abstract;

use Exception;
use app\common\interface\TableItem as TableItemInterface;

/**
 * 表格项抽象类
 */
abstract class TableItem implements TableItemInterface
{
    /**
     * 模板文件缓存
     * @var array
     */
    private array $templateCache = [];

    /**
     * 处理值
     * @param mixed $data
     * @param array $column
     * @return mixed
     */
    public function handleValue(mixed $data, array $column = []): mixed
    {
        return $data;
    }

    /**
     * 创建列模板
     * @param array $column
     * @param array $params
     * @param string $template
     * @return string
     * @throws Exception
     */
    protected function createTemplet(array &$column = [], array $params = [], string $template = ''): string
    {
        if ($column['type'] != '') {
            // 模板id
            $templetId                 = $column['table_id'] . '_templet_' . $column['type'] . '_' . $column['field'] . '_' . dp_rand_str();
            $column['cols']['templet'] = '#' . $templetId;

            // 模板文件
            if ($template === '') {
                $template = dp_table_path() . $column['type'] . '/item.html';
                if (!is_file($template)) {
                    $typePath = str_replace(['\\', '/', '.'], DIRECTORY_SEPARATOR, (string)$column['type']);
                    $template = dp_extend_table_path() . $typePath . DIRECTORY_SEPARATOR . 'item.html';
                }
            }

            if (!is_file($template)) {
                throw new Exception(lang('dp#template not exists', ['file' => $template]));
            }

            if (!isset($this->templateCache[$template])) {
                $this->templateCache[$template] = file_get_contents($template);
            }

            $params = [
                'id'        => $templetId,
                'field'     => $column['field'],
                'title'     => $column['title'],
                'tableId'   => $column['table_id'],
                'crudToken' => $column['crud_token'],
                ...$params
            ];

            $view = app('view', [], true);
            return $view->display($this->templateCache[$template], $params);
        } else {
            return '';
        }
    }
}
