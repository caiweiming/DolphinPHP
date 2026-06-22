<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 占位符与图标能力块
 */
final class TimePlaceholderIconSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'placeholder_icon'),
            'title' => (string) ($section['title'] ?? '占位符与图标'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'placeholder', 'value' => '提示用户输入预期的时间格式或业务时点'],
                ['name' => 'icon(left/right)', 'value' => '控制时间图标位于左侧或右侧'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'time',
        'name' => 'notify_time',
        'label' => '提醒时间',
        'tips' => '用于消息推送或定时提醒',
        'placeholder' => '请选择提醒时间',
        'icon' => 'right',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('notify_time', '提醒时间', '用于消息推送或定时提醒')
    ->placeholder('请选择提醒时间')
    ->icon('right');
CODE,
            'notes' => [
                '占位符负责业务引导，图标负责强化“这是时间输入”的视觉暗示。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimePlaceholderIconSection.php',
                    'label' => '占位符与图标能力块',
                    'description' => '展示 placeholder 与左右图标的常见组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_placeholder_', false), '占位符与图标')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Time::make('notify_time', '提醒时间', '用于消息推送或定时提醒')
                    ->placeholder('请选择提醒时间')
                    ->icon('right')
            )
            ->fetch();
    }
}
