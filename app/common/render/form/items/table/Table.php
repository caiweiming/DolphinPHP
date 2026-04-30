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

namespace app\common\render\form\items\table;

use app\common\abstract\FormType;

/**
 * 表格展示组件
 */
class Table extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'table';

    /**
     * 设置表头（支持多表头）
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers = []): static
    {
        $this->config['headers'] = $headers;
        return $this;
    }

    /**
     * 设置表格数据行
     * @param array $rows
     * @return $this
     */
    public function rows(array $rows = []): static
    {
        $this->config['rows'] = $rows;
        return $this;
    }

    /**
     * rows 别名
     * @param array $rows
     * @return $this
     */
    public function data(array $rows = []): static
    {
        return $this->rows($rows);
    }

    /**
     * 设置表格 class
     * @param string $class
     * @return $this
     */
    public function tableClass(string $class = ''): static
    {
        $this->config['table_class'] = $class;
        return $this;
    }

    /**
     * 设置外层容器 class
     * @param string $class
     * @return $this
     */
    public function wrapperClass(string $class = ''): static
    {
        $this->config['wrapper_class'] = $class;
        return $this;
    }

    /**
     * 设置空数据文案
     * @param string $text
     * @return $this
     */
    public function emptyText(string $text = ''): static
    {
        $this->config['empty_text'] = $text;
        return $this;
    }

    /**
     * 设置单元格内容是否原样输出
     * @param bool $raw
     * @return $this
     */
    public function raw(bool $raw = true): static
    {
        $this->config['raw'] = $raw;
        return $this;
    }
}
