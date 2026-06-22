<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch 提交注意事项能力块
 */
final class SwitchSubmissionNoticeSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'submission_notice'),
            'title' => (string) ($section['title'] ?? '提交注意事项'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => '未选中不提交', 'value' => '后端必须用 ?? 0 补默认值'],
                ['name' => '0/1 语义', 'value' => '通常 1 为开启，0 为关闭'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'notify_enabled',
        'label' => '消息通知',
        'tips' => '后端提交时请使用 $data[\'notify_enabled\'] = $data[\'notify_enabled\'] ?? 0;',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('notify_enabled', '消息通知', '后端提交时请使用 $data[\'notify_enabled\'] = $data[\'notify_enabled\'] ?? 0;')
    ->value(0);

// 控制器中：
$data['notify_enabled'] = $data['notify_enabled'] ?? 0;
CODE,
            'notes' => [
                '这是 switch 最关键的坑点：未选中时字段不会出现在请求里，所以后端必须显式补默认值。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchSubmissionNoticeSection.php',
                    'label' => '提交注意事项能力块',
                    'description' => '强调 switch 未选中不提交的后端处理要求。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_notice_', false), '提交注意事项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Toggle::make('notify_enabled', '消息通知', '后端提交时请使用 $data[\'notify_enabled\'] = $data[\'notify_enabled\'] ?? 0;')
                    ->id('notify_enabled_submission_notice')
                    ->value(0)
            )
            ->fetch();
    }
}
