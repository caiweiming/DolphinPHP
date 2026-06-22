<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch 基础用法能力块
 */
final class SwitchBasicSection
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
                ['name' => 'name', 'value' => 'status'],
                ['name' => 'value', 'value' => '1 表示默认开启'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'status',
        'label' => '状态',
        'tips' => '是否启用该用户',
        'value' => 1,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('status', '状态', '是否启用该用户')
    ->value(1);
CODE,
            'notes' => [
                'switch 最基础的能力就是表达一个布尔状态，通常用 1/0 表示开关默认值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 switch 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Toggle::make('status', '状态', '是否启用该用户')
                    ->id('status_basic')
                    ->value(1)
            )
            ->fetch();
    }
}
