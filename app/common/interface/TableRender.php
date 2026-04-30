<?php
declare(strict_types=1);

namespace app\common\interface;

/**
 * 表格渲染器接口
 */
interface TableRender extends ZRender
{
    /**
     * 设置表格数据
     * @param mixed $data 表格数据
     * @return static
     */
    public function data(mixed $data): static;
    
    /**
     * 添加表格列
     * @param string|array $field 字段名或配置数组
     * @param string $title 列标题
     * @param mixed $type 列类型
     * @param mixed $options 选项配置
     * @param array $cols 列配置
     * @return static
     */
    public function column(
        string|array $field = '',
        string $title = '',
        mixed $type = 'normal',
        mixed $options = [],
        array $cols = []
    ): static;
    
    /**
     * 设置表格选项
     * @param array $options 选项配置
     * @return static
     */
    public function options(array $options): static;
} 