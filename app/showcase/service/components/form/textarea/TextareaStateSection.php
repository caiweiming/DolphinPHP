<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 状态能力块
 */
final class TextareaStateSection
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
                ['name' => 'readonly', 'value' => '保留内容展示与选择能力，但不允许编辑'],
                ['name' => 'disabled', 'value' => '直接禁用输入交互'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'readonly_content',
        'label' => '只读内容',
        'value' => "这段内容来自系统同步，不允许人工修改。",
        'readonly' => true,
    ],
    [
        'type' => 'textarea',
        'name' => 'disabled_remark',
        'label' => '禁用备注',
        'value' => "当前节点未开放填写权限。",
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('readonly_content', '只读内容')
    ->value("这段内容来自系统同步，不允许人工修改。")
    ->readonly();

Field::textarea('disabled_remark', '禁用备注')
    ->value("当前节点未开放填写权限。")
    ->disabled();
CODE,
            'notes' => [
                'readonly 更适合展示已有文本并允许复制，disabled 更适合明确表达“当前不可操作”。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaStateSection.php',
                    'label' => '状态能力块',
                    'description' => '展示 readonly 与 disabled 在多行输入中的差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('readonly_content', '只读内容')
                    ->value("这段内容来自系统同步，不允许人工修改。")
                    ->readonly()
                    ->rows(5)
            )
            ->item(
                Textarea::make('disabled_remark', '禁用备注')
                    ->value("当前节点未开放填写权限。")
                    ->disabled()
                    ->rows(4)
            )
            ->fetch();
    }
}
