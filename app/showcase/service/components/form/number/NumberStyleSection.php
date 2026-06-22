<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 样式变体能力块
 */
final class NumberStyleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'style'),
            'title' => (string) ($section['title'] ?? '样式变体'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'rounded', 'value' => '更柔和的数值输入框样式'],
                ['name' => 'flush', 'value' => '贴线式输入布局'],
                ['name' => 'float', 'value' => '浮动标签样式'],
            ],
            'array_code' => <<<'CODE'
[
    ['number', 'rounded_count', '圆角数量', '用于更轻量的数值输入'],
    ['number', 'flush_sort', '扁平排序', '用于紧凑编辑场景'],
    ['number', 'float_price', '浮动价格', '用于现代化录入页面'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('rounded_count', '圆角数量', '用于更轻量的数值输入')->rounded();
Field::number('flush_sort', '扁平排序', '用于紧凑编辑场景')->flush();
Field::number('float_price', '浮动价格', '用于现代化录入页面')->float();
CODE,
            'notes' => [
                'number 和 text 一样支持 rounded、flush、float，适合在不同后台页面气质中复用统一字段能力。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 rounded、flush、float 三种常见数值输入变体。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Number::make('rounded_count', '圆角数量', '用于更轻量的数值输入')->rounded()->placeholder('请输入数量'))
            ->item(Number::make('flush_sort', '扁平排序', '用于紧凑编辑场景')->flush()->placeholder('请输入排序值'))
            ->item(Number::make('float_price', '浮动价格', '用于现代化录入页面')->float()->placeholder('请输入价格'))
            ->fetch();
    }
}
