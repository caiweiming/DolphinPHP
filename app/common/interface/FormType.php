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

namespace app\common\interface;

/**
 * 表单项接口
 * @package app\common\interface
 */
interface FormType
{
    /**
     * 设置表单项名称和标题
     * @param string $name 表单项name值
     * @param string $label 表单项label值
     * @param string $tips
     * @return static
     */
    public static function make(string $name, string $label = '', string $tips = ''): static;

    /**
     * 设置表单项label
     * @param string $label
     * @return $this
     */
    public function label(string $label): static;

    /**
     * 设置表单项提示信息
     * @param string $tips
     * @return $this
     */
    public function tips(string $tips): static;

    /**
     * 设置表单项默认值
     * @param mixed $value
     * @return $this
     */
    public function value(mixed $value): static;

    /**
     * 设置表单项是否必填
     * @param bool $required
     * @return $this
     */
    public function required(bool $required = true): static;

    /**
     * 设置表单项大小
     * @param string $value lg|sm
     * @return $this
     */
    public function size(string $value = ''): static;

    /**
     * 设置表单项宽度
     * @param string $value 宽度值：6|6,lg-2
     * @param bool $inner 是否为内部宽度
     * @return $this
     */
    public function width(string $value = '', bool $inner = false): static;

    /**
     * 设置表单项模板
     * @param string $value
     * @return $this
     */
    public function template(string $value = ''): static;

    /**
     * 设置表单项html
     * @param string $value
     * @return $this
     */
    public function html(string $value = ''): static;

    /**
     * 获取配置数组
     * @return array
     */
    public function toArray(): array;

    /**
     * 获取表单项类型
     * @return string
     */
    public function getType(): string;

    /**
     * 获取表单项name值
     * @return string
     */
    public function getName(): string;
}
