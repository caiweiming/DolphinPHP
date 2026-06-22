<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 基础用法能力块
 */
final class NumberBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础用法'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'age / score'],
                ['name' => 'label', 'value' => '年龄 / 评分'],
            ],
            'array_code' => <<<'CODE'
[
    ['number', 'age', '年龄', '请输入年龄'],
    ['number', 'score', '评分', '请输入评分'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('age', '年龄', '请输入年龄');
Field::number('score', '评分', '请输入评分');
CODE,
            'notes' => [
                '基础 number 适合最直接的整数或小数输入场景。',
                '最终渲染为真正的 `input[type=number]`，便于直接观察原生数值属性。'],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 number 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Number::make('age', '年龄', '请输入年龄')->id('age_basic')->placeholder('例如 28'))
            ->item(Number::make('score', '评分', '请输入评分')->placeholder('例如 98'))
            ->fetch();
    }
}
