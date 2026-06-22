<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;

/**
 * switch inline 能力块
 */
final class SwitchInlineSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'inline'),
            'title' => (string) ($section['title'] ?? '内联展示 inline'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'inline', 'value' => '多个开关并排展示，适合紧凑配置场景'],
                ['name' => 'options', 'value' => '可用数组一次性渲染多个开关'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'feature_flags',
        'label' => '功能开关',
        'inline' => true,
        'options' => [
            'show_beta' => 'Beta',
            'show_logs' => '日志',
            'show_help' => '帮助',
        ],
        'value' => ['show_beta', 'show_help'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
[
    Field::switch('feature_flags', '功能开关')
        ->inline()
        ->options([
            'show_beta' => 'Beta',
            'show_logs' => '日志',
            'show_help' => '帮助',
        ])
        ->value(['show_beta', 'show_help']),
]
CODE,
            'notes' => [
                'inline 更适合多枚轻量级开关并排展示，避免纵向列表过长。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchInlineSection.php',
                    'label' => 'inline 能力块',
                    'description' => '展示多个开关以内联方式排布的效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_inline_', false), '内联展示 inline')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'switch',
                'name' => 'feature_flags',
                'label' => '功能开关',
                'inline' => true,
                'options' => [
                    'show_beta' => 'Beta',
                    'show_logs' => '日志',
                    'show_help' => '帮助',
                ],
                'value' => ['show_beta', 'show_help'],
            ])
            ->fetch();
    }
}
