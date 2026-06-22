<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 多选默认值能力块
 */
final class SelectMultipleValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple_value'),
            'title' => (string) ($section['title'] ?? '多选默认值'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple + value', 'value' => '多选回填常使用逗号分隔字符串，如 gz,sh'],
                ['name' => '编辑态回显', 'value' => '适合标签、权限、适用城市等多值字段回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'selected_cities',
        'label' => '已开通城市',
        'tips' => '编辑套餐时回显已开通城市',
        'multiple' => true,
        'options' => [
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
        ],
        'value' => 'gz,sh',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('selected_cities', '已开通城市', '编辑套餐时回显已开通城市')
    ->multiple()
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->value('gz,sh');
CODE,
            'notes' => [
                '和 test/form-select.php 一样，多选默认值示例直接使用逗号分隔字符串，更贴近现有框架习惯。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectMultipleValueSection.php',
                    'label' => '多选默认值能力块',
                    'description' => '展示 select 多选模式下的默认值回显方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_multiple_value_', false), '多选默认值')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('selected_cities', '已开通城市', '编辑套餐时回显已开通城市')
                    ->multiple()
                    ->options([
                        'gz' => '广州',
                        'sz' => '深圳',
                        'sh' => '上海',
                    ])
                    ->value('gz,sh')
            )
            ->fetch();
    }
}
