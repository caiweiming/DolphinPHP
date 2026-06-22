<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio 基础单选能力块
 */
final class RadioBasicSection
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
            'title' => (string) ($section['title'] ?? '基础单选'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'gender'],
                ['name' => 'options', 'value' => '使用键值对定义互斥候选项'],
                ['name' => 'value', 'value' => '1 表示默认选中“男”'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'gender',
        'label' => '性别',
        'tips' => '请选择用户性别',
        'options' => [
            1 => '男',
            2 => '女',
        ],
        'value' => 1,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('gender', '性别', '请选择用户性别')
    ->options([
        1 => '男',
        2 => '女',
    ])
    ->value(1);
CODE,
            'notes' => [
                '基础单选适合选项少且语义明确的场景，开发者一眼就能看到所有候选值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioBasicSection.php',
                    'label' => '基础单选能力块',
                    'description' => '组装 radio 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_basic_', false), '基础单选')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('gender', '性别', '请选择用户性别')
                    ->options([
                        1 => '男',
                        2 => '女',
                    ])
                    ->value(1)
            )
            ->fetch();
    }
}
