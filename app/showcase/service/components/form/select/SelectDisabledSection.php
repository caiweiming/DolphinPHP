<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 禁用状态能力块
 */
final class SelectDisabledSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'disabled'),
            'title' => (string) ($section['title'] ?? '禁用状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'disabled', 'value' => 'true 时禁用整个下拉框交互'],
                ['name' => 'value', 'value' => '通常仍保留当前值，便于查看历史配置'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'locked_status',
        'label' => '锁定状态',
        'tips' => '当前记录已归档，不允许再次修改',
        'options' => [
            0 => '禁用',
            1 => '启用',
        ],
        'value' => 1,
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('locked_status', '锁定状态', '当前记录已归档，不允许再次修改')
    ->options([
        0 => '禁用',
        1 => '启用',
    ])
    ->value(1)
    ->disabled(true);
CODE,
            'notes' => [
                '整组禁用更适合审批完成、系统同步或仅查看不允许编辑的下拉场景。',
                '如需只禁用部分选项，不要使用 disabled(true)，而是改用“禁用指定选项”里的 disabled([\'value\']) 写法。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectDisabledSection.php',
                    'label' => '禁用状态能力块',
                    'description' => '展示 select 在整体不可编辑场景下的禁用态效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_disabled_', false), '禁用状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('locked_status', '锁定状态', '当前记录已归档，不允许再次修改')
                    ->options([
                        0 => '禁用',
                        1 => '启用',
                    ])
                    ->value(1)
                    ->disabled(true)
            )
            ->fetch();
    }
}
