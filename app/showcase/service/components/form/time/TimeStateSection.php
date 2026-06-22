<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 只读与禁用能力块
 */
final class TimeStateSection
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
                ['name' => 'readonly', 'value' => '允许查看当前时间，但禁止直接编辑'],
                ['name' => 'disabled', 'value' => '完全锁定字段交互，适合已生效的营业设置'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'readonly_time',
        'label' => '只读时间',
        'value' => '09:00',
        'readonly' => true,
    ],
    [
        'type' => 'time',
        'name' => 'disabled_time',
        'label' => '禁用时间',
        'value' => '23:00',
        'disabled' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('readonly_time', '只读时间')
    ->value('09:00')
    ->readonly();

Field::time('disabled_time', '禁用时间')
    ->value('23:00')
    ->disabled();
CODE,
            'notes' => [
                '只读更适合“看得见但不能改”，禁用更适合“当前不参与交互”的业务状态。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeStateSection.php',
                    'label' => '只读与禁用能力块',
                    'description' => '展示 time 在 readonly 与 disabled 场景下的表现差异。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_state_', false), '只读与禁用')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Time::make('readonly_time', '只读时间')
                    ->value('09:00')
                    ->readonly()
            )
            ->item(
                Time::make('disabled_time', '禁用时间')
                    ->value('23:00')
                    ->disabled()
            )
            ->fetch();
    }
}
