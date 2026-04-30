<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 自定义表单扩展项：选择表格组件类型封装
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace form\select_table;

use app\common\abstract\FormType;

/**
 * 选择表格组件类型封装
 */
class SelectTable extends FormType
{
    /**
     * 组件类型
     * @var string
     */
    protected string $type = 'select_table';

    /**
     * 设置 options 子配置
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

    /**
     * 设置展示列
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns = []): static
    {
        return $this->setOptionConfig('columns', $columns);
    }

    /**
     * 设置弹窗 URL
     * @param string $url
     * @return $this
     */
    public function popupUrl(string $url = ''): static
    {
        return $this->setPopupConfig('url', $url);
    }

    /**
     * 设置弹窗标题
     * @param string $title
     * @return $this
     */
    public function popupTitle(string $title = ''): static
    {
        return $this->setPopupConfig('title', $title);
    }

    /**
     * 设置弹窗宽度
     * @param string $width
     * @return $this
     */
    public function popupWidth(string $width = ''): static
    {
        return $this->setPopupConfig('width', $width);
    }

    /**
     * 设置弹窗高度
     * @param string $height
     * @return $this
     */
    public function popupHeight(string $height = ''): static
    {
        return $this->setPopupConfig('height', $height);
    }

    /**
     * 设置弹窗内表格ID
     * @param string $tableId
     * @return $this
     */
    public function popupTableId(string $tableId = ''): static
    {
        return $this->setPopupConfig('table_id', $tableId);
    }

    /**
     * 设置选择模式
     * @param string $mode single|multiple
     * @return $this
     */
    public function selectionMode(string $mode = 'multiple'): static
    {
        return $this->setOptionConfig('selection_mode', $mode);
    }

    /**
     * 设置附加提交字段（不显示，仅提交）
     * @param array $fields
     * @return $this
     */
    public function extraFields(array $fields = []): static
    {
        return $this->setOptionConfig('extra_fields', $fields);
    }

    /**
     * 设置 popup 整体配置
     * @param array $popup
     * @return $this
     */
    public function popup(array $popup = []): static
    {
        return $this->setOptionConfig('popup', $popup);
    }

    /**
     * 设置 popup 子配置
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function setPopupConfig(string $key, mixed $value): static
    {
        $options = $this->config['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $popup = $options['popup'] ?? [];
        if (!is_array($popup)) {
            $popup = [];
        }

        $popup[$key]             = $value;
        $options['popup']        = $popup;
        $this->config['options'] = $options;
        return $this;
    }
}
