<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 默认值与回填能力块
 */
final class RadioGroupDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => 'business 表示编辑页默认回显“商务版”'],
                ['name' => 'options', 'value' => '建议使用稳定的业务值，便于编辑态直接回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'current_plan',
        'label' => '当前套餐',
        'tips' => '编辑企业资料时回显已生效套餐',
        'options' => [
            'starter' => '起步版',
            'business' => '商务版',
            'enterprise' => '企业版',
        ],
        'value' => 'business',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('current_plan', '当前套餐', '编辑企业资料时回显已生效套餐')
    ->options([
        'starter' => '起步版',
        'business' => '商务版',
        'enterprise' => '企业版',
    ])
    ->value('business');
CODE,
            'notes' => [
                'radio_group 的 value 常直接对应数据库里的套餐编码，最适合做编辑态回显。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 radio_group 在编辑态场景下如何回显当前选中项。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('current_plan', '当前套餐', '编辑企业资料时回显已生效套餐')
                    ->options([
                        'starter' => '起步版',
                        'business' => '商务版',
                        'enterprise' => '企业版',
                    ])
                    ->value('business')
            )
            ->fetch();
    }
}
