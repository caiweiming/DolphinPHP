<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 多选默认值能力块
 */
final class Select2MultipleValueSection
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
                ['name' => '编辑态回显', 'value' => '适合已开通城市、角色、标签等多值字段的编辑页'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
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

Field::select2('selected_cities', '已开通城市', '编辑套餐时回显已开通城市')
    ->multiple()
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->value('gz,sh');
CODE,
            'notes' => [
                '和 select 一样，当前框架里 select2 多选默认值也常用逗号分隔字符串回显。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2MultipleValueSection.php',
                    'label' => '多选默认值能力块',
                    'description' => '展示 select2 多选模式下的默认值回显方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_multiple_value_', false), '多选默认值')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('selected_cities', '已开通城市', '编辑套餐时回显已开通城市')
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
