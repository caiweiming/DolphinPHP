<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 基础下拉选择能力块
 */
final class SelectBasicSection
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
            'title' => (string) ($section['title'] ?? '基础下拉选择'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'status'],
                ['name' => 'options', 'value' => '使用键值对定义预置候选项'],
                ['name' => 'value', 'value' => '1 表示默认选中“启用”'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'status',
        'label' => '状态',
        'tips' => '请选择状态',
        'options' => [
            0 => '禁用',
            1 => '启用',
        ],
        'value' => 1,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('status', '状态', '请选择状态')
    ->options([
        0 => '禁用',
        1 => '启用',
    ])
    ->value(1);
CODE,
            'notes' => [
                '基础下拉适合选项数量不多、但又不想占用太多页面空间的后台场景。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectBasicSection.php',
                    'label' => '基础下拉选择能力块',
                    'description' => '组装 select 的最小可用示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_basic_', false), '基础下拉选择')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('status', '状态', '请选择状态')
                    ->options([
                        0 => '禁用',
                        1 => '启用',
                    ])
                    ->value(1)
            )
            ->fetch();
    }
}
