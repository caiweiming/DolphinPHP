<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\time;

use app\common\render\Form;
use app\common\render\form\items\time\Time;

/**
 * time 样式变体能力块
 */
final class TimeStyleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'style'),
            'title' => (string) ($section['title'] ?? '样式变体'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'rounded', 'value' => '更柔和的时间输入框视觉'],
                ['name' => 'flush', 'value' => '紧凑表单里的贴线式时间输入'],
            ],
            'array_code' => <<<'CODE'
[
    ['time', 'rounded_time', '圆角时间', '用于卡片式设置区域'],
    ['time', 'flush_time', '扁平时间', '用于紧凑编辑区'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::time('rounded_time', '圆角时间', '用于卡片式设置区域')->rounded();
Field::time('flush_time', '扁平时间', '用于紧凑编辑区')->flush();
CODE,
            'notes' => [
                'time 的样式能力和 date、text 保持一致，便于整套后台表单统一视觉。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/time/TimeStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 rounded、flush 两种常见时间输入视觉变体。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_time_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Time::make('rounded_time', '圆角时间', '用于卡片式设置区域')
                    ->rounded()
                    ->placeholder('请选择时间')
            )
            ->item(
                Time::make('flush_time', '扁平时间', '用于紧凑编辑区')
                    ->flush()
                    ->placeholder('请选择时间')
            )
            ->fetch();
    }
}
