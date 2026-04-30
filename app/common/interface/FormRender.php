<?php
declare(strict_types=1);

namespace app\common\interface;

/**
 * 表单渲染器接口
 */
interface FormRender extends ZRender
{
    /**
     * 初始化表单
     * @param string $id 表单ID
     * @param string $title 表单标题
     * @return static
     */
    public function init(string $id, string $title = ''): static;
    
    /**
     * 添加表单项
     * @param mixed $type 表单项类型或完整配置
     * @param mixed $name 字段名
     * @param string $label 标签
     * @param string $tips 提示信息
     * @param mixed $value 默认值
     * @param mixed $options 选项配置
     * @return string|static
     */
    public function item(
        mixed $type, 
        mixed $name = '', 
        string $label = '', 
        string $tips = '', 
        mixed $value = '', 
        mixed $options = []
    ): string|static;
    
    /**
     * 设置表单数据
     * @param array|object $data 表单数据
     * @return static
     */
    public function data(array|object $data = []): static;
} 