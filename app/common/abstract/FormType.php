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

use app\common\interface\FormType as FormTypeInterface;
use Exception;

/**
 * 表单项抽象基类
 */
abstract class FormType implements FormTypeInterface
{
    /**
     * 表单项配置
     * @var array
     */
    protected array $config = [];

    /**
     * 表单项类型
     * @var string
     */
    protected string $type = '';

    /**
     * 构造函数
     * @throws Exception
     */
    public function __construct()
    {
        if (empty($this->type)) {
            throw new Exception('表单项类型不能为空，请添加：protected string $type = \'your_type_name\';');
        }
        $this->config['type'] = $this->type;
    }

    /**
     * 创建表单项
     * @param string $name 表单项名称
     * @param string $label 表单项标题
     * @param string $tips 表单项提示信息
     * @return static
     */
    public static function make(string $name, string $label = '', string $tips = ''): static
    {
        $field                  = new static();
        $field->config['name']  = $name;
        $field->config['label'] = $label;
        $field->config['tips']  = $tips;

        return $field;
    }

    /**
     * 设置表单项ID
     * @param string $id
     * @return $this
     */
    public function id(string $id = ''): static
    {
        $this->config['id'] = $id;
        return $this;
    }

    /**
     * 设置表单项标题
     * @param string $label 表单项标题
     * @return $this
     */
    public function label(string $label = ''): static
    {
        $this->config['label'] = $label;
        return $this;
    }

    /**
     * 设置表单项提示信息
     * @param string $tips 表单项提示信息
     * @return $this
     */
    public function tips(string $tips = ''): static
    {
        $this->config['tips'] = $tips;
        return $this;
    }

    /**
     * 设置表单项默认值
     * @param mixed $value 默认值
     * @return $this
     */
    public function value(mixed $value = ''): static
    {
        $this->config['value'] = $value;
        return $this;
    }

    /**
     * 设置表单项是否必填
     * @param bool $required 是否显示必填
     * @return $this
     */
    public function required(bool $required = true): static
    {
        $this->config['required'] = $required;
        return $this;
    }

    /**
     * 设置表单项大小
     * @param string $value lg|sm
     * @return $this
     */
    public function size(string $value = ''): static
    {
        $this->config['size'] = $value;
        return $this;
    }

    /**
     * 设置表单项大小为小
     * @return $this
     */
    public function small(): static
    {
        $this->config['size'] = 'sm';
        return $this;
    }

    /**
     * 设置表单项大小为大
     * @return $this
     */
    public function large(): static
    {
        $this->config['size'] = 'lg';
        return $this;
    }

    /**
     * 设置CSS类名
     * @param string $class CSS类名
     * @return $this
     */
    public function class(string $class = ''): static
    {
        $this->config['class'] = $class;
        return $this;
    }

    /**
     * 设置表单项宽度
     * @param string|int $value 宽度值：6|6,lg-2
     * @param bool $inner 是否为内部宽度
     * @return $this
     */
    public function width(string|int $value = '', bool $inner = false): static
    {
        if ($inner) {
            $this->config['inner_width'] = $value;
        } else {
            $this->config['width'] = $value;
        }
        return $this;
    }

    /**
     * 设置选项列表
     * @param mixed $options
     * @return $this
     */
    public function options(mixed $options = []): static
    {
        $this->config['options'] = $options;
        return $this;
    }

    /**
     * 设置表单项模板
     * @param string $value
     * @return $this
     */
    public function template(string $value = ''): static
    {
        $this->config['template'] = $value;
        return $this;
    }

    /**
     * 设置表单项html
     * @param mixed $value
     * @return $this
     */
    public function html(mixed $value = ''): static
    {
        $this->config['html'] = $value;
        return $this;
    }

    /**
     * 设置表单项属性
     * @param array|string $props
     * @return $this
     */
    public function props(array|string $props = ''): static
    {
        $this->config['props'] = $props;
        return $this;
    }

    /**
     * 设置字段联动规则
     * @param string|array $field 依赖字段名或完整规则数组
     * @param string $operator 运算符：eq/neq/in/notIn/empty/notEmpty/gt/egt/lt/elt
     * @param mixed $value 比较值
     * @param array|string $actions 命中动作（输出键为 then）
     * @param array|string $elseActions 未命中动作（输出键为 else）
     * @return $this
     */
    public function when(
        string|array $field,
        string       $operator = 'eq',
        mixed        $value = null,
        array|string $actions = ['show'],
        array|string $elseActions = []
    ): static
    {
        if (is_array($field)) {
            $this->config['when'] = $field;
            return $this;
        }

        $this->config['when'] = [
            'field' => $field,
            'op'    => $operator,
            'value' => $value,
            'then'  => is_array($actions) ? $actions : [$actions],
            'else'  => is_array($elseActions) ? $elseActions : [$elseActions],
        ];
        return $this;
    }

    /**
     * 设置字段 in 条件联动规则
     * @param string $field 依赖字段名
     * @param array $values 候选值
     * @param array|string $actions 命中动作
     * @param array|string $elseActions 未命中动作
     * @return $this
     */
    public function whenIn(string $field, array $values, array|string $actions = ['show'], array|string $elseActions = []): static
    {
        return $this->when($field, 'in', $values, $actions, $elseActions);
    }

    /**
     * 设置字段 empty 条件联动规则
     * @param string $field 依赖字段名
     * @param array|string $actions 命中动作
     * @param array|string $elseActions 未命中动作
     * @return $this
     */
    public function whenEmpty(string $field, array|string $actions = ['show'], array|string $elseActions = []): static
    {
        return $this->when($field, 'empty', null, $actions, $elseActions);
    }

    /**
     * 获取配置数组
     * @return array
     */
    public function toArray(): array
    {
        $this->handleConfig();
        return $this->config;
    }

    /**
     * 获取表单项类型
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 获取表单项name值
     * @return string
     */
    public function getName(): string
    {
        return $this->config['name'];
    }

    /**
     * 设置任意自定义属性
     * 用于特殊场景
     */
    public function attr(string $name, mixed $value): static
    {
        $this->config[$name] = $value;
        return $this;
    }

    /**
     * 批量设置自定义属性
     */
    public function attrs(array $attrs): static
    {
        $this->config = array_merge($this->config, $attrs);
        return $this;
    }

    /**
     * 处理配置
     */
    protected function handleConfig()
    {
    }
}
