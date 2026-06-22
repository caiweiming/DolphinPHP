<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio 禁用状态能力块
 */
final class RadioDisabledSection
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
                ['name' => 'disabled', 'value' => 'true 时禁用整组 radio，适合只读审批或历史记录页'],
                ['name' => 'value', 'value' => '通常仍保留当前选中值，便于开发者识别现状'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'audit_result',
        'label' => '审核结果',
        'tips' => '当前工单已归档，不允许再次修改',
        'options' => [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已驳回',
        ],
        'value' => 'approved',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('audit_result', '审核结果', '当前工单已归档，不允许再次修改')
    ->options([
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已驳回',
    ])
    ->value('approved')
    ->disabled(true);
CODE,
            'notes' => [
                '禁用态常见于审批完成、系统生成或仅允许查看不允许编辑的场景。',
                'radio 的 disabled(true) 会自动把全部 option key 标记为不可选，适合整组锁定，而不是逐项灰度控制。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioDisabledSection.php',
                    'label' => '禁用状态能力块',
                    'description' => '展示 radio 在不可编辑场景下的禁用态效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_disabled_', false), '禁用状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('audit_result', '审核结果', '当前工单已归档，不允许再次修改')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已驳回',
                    ])
                    ->value('approved')
                    ->disabled(true)
            )
            ->fetch();
    }
}
