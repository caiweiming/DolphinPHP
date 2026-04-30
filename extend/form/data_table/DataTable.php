<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 自定义表单扩展项：数据表格组件类型封装
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace form\data_table;

use app\common\abstract\FormType;

/**
 * 数据表格组件类型封装
 */
class DataTable extends FormType
{
    /**
     * 组件类型
     * @var string
     */
    protected string $type = 'data_table';

    /**
     * 设置列配置
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns = []): static
    {
        return $this->setOptionConfig('columns', $columns);
    }

    /**
     * 设置“添加新行”按钮文案
     * @param string $text
     * @return $this
     */
    public function addText(string $text = ''): static
    {
        return $this->setOptionConfig('add_text', $text);
    }

    /**
     * 设置空数据提示文案
     * @param string $text
     * @return $this
     */
    public function emptyText(string $text = ''): static
    {
        return $this->setOptionConfig('empty_text', $text);
    }

    /**
     * 设置表格容器 class
     * @param string $class
     * @return $this
     */
    public function tableClass(string $class = ''): static
    {
        return $this->setOptionConfig('table_class', $class);
    }

    /**
     * 设置删除是否需要确认
     * @param bool $confirm
     * @return $this
     */
    public function confirmDelete(bool $confirm = true): static
    {
        return $this->setOptionConfig('confirm_delete', $confirm);
    }

    /**
     * 设置是否启用拖拽排序行
     * @param bool $sortable
     * @return $this
     */
    public function sortable(bool $sortable = true): static
    {
        return $this->setOptionConfig('sortable', $sortable);
    }

    /**
     * 设置拖拽排序后是否提示通知
     * @param bool $notify
     * @return $this
     */
    public function sortNotify(bool $notify = true): static
    {
        return $this->setOptionConfig('sort_notify', $notify);
    }

    /**
     * 设置拖拽排序通知文案
     * @param string $message
     * @return $this
     */
    public function sortNotifyMessage(string $message = ''): static
    {
        return $this->setOptionConfig('sort_notify_message', $message);
    }

    /**
     * 设置 options 子配置，避免覆盖其他选项
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function setOptionConfig(string $key, mixed $value): static
    {
        $options = $this->config['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $options[$key]           = $value;
        $this->config['options'] = $options;
        return $this;
    }
}
