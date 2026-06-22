<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 基础增强下拉能力块
 */
final class Select2BasicSection
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
            'title' => (string) ($section['title'] ?? '基础增强下拉'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'department_id'],
                ['name' => 'options', 'value' => '使用键值对定义增强下拉的候选项'],
                ['name' => 'placeholder', 'value' => '建议显式设置占位符，便于用户理解搜索入口'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'department_id',
        'label' => '所属部门',
        'tips' => '请选择所属部门',
        'options' => [
            'product' => '产品中心',
            'engineering' => '研发中心',
            'operations' => '运营中心',
        ],
        'placeholder' => '请选择所属部门',
        'value' => 'engineering',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('department_id', '所属部门', '请选择所属部门')
    ->options([
        'product' => '产品中心',
        'engineering' => '研发中心',
        'operations' => '运营中心',
    ])
    ->value('engineering')
    ->placeholder('请选择所属部门');
CODE,
            'notes' => [
                '基础 select2 最适合让开发者看到它与 select 的最直接差异：自带搜索和增强交互。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2BasicSection.php',
                    'label' => '基础增强下拉能力块',
                    'description' => '组装 select2 的最小可用示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_basic_', false), '基础增强下拉')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('department_id', '所属部门', '请选择所属部门')
                    ->options([
                        'product' => '产品中心',
                        'engineering' => '研发中心',
                        'operations' => '运营中心',
                    ])
                    ->value('engineering')
                    ->placeholder('请选择所属部门')
            )
            ->fetch();
    }
}
