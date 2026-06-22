<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 状态能力块
 */
final class TextStateSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'state'),
            'title' => (string) ($section['title'] ?? '只读与禁用'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'readonly', 'value' => '保留展示和提交语义，但不允许编辑'],
                ['name' => 'disabled', 'value' => '直接禁用输入交互'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'readonly_code', '只读邀请码', '系统生成，不允许直接修改'],
    ['text', 'disabled_account', '禁用账号', '审批完成前暂不开放编辑'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('readonly_code', '只读邀请码', '系统生成，不允许直接修改')
    ->readonly()
    ->value('INVITE-2026');

Field::text('disabled_account', '禁用账号', '审批完成前暂不开放编辑')
    ->disabled()
    ->value('audit_user');
CODE,
            'notes' => [
                'readonly 用于展示已有值，disabled 用于明确不可操作的字段状态。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextStateSection.php',
                    'label' => '状态能力块',
                    'description' => '展示 readonly 与 disabled 的状态差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('readonly_code', '只读邀请码', '系统生成，不允许直接修改')
                    ->readonly()
                    ->value('INVITE-2026')
            )
            ->item(
                Text::make('disabled_account', '禁用账号', '审批完成前暂不开放编辑')
                    ->disabled()
                    ->value('audit_user')
            )
            ->fetch();
    }
}
