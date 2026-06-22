<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\datetime;

use app\common\render\Form;
use app\common\render\form\items\datetime\Datetime;

/**
 * datetime 样式变体能力块
 */
final class DatetimeStyleSection
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
                ['name' => 'rounded', 'value' => '更柔和的日期时间输入框视觉'],
                ['name' => 'flush', 'value' => '紧凑表单里的贴线式日期时间输入'],
            ],
            'array_code' => <<<'CODE'
[
    ['datetime', 'rounded_datetime', '圆角时间点', '用于卡片式设置区域'],
    ['datetime', 'flush_datetime', '扁平时间点', '用于紧凑编辑区'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::datetime('rounded_datetime', '圆角时间点', '用于卡片式设置区域')->rounded();
Field::datetime('flush_datetime', '扁平时间点', '用于紧凑编辑区')->flush();
CODE,
            'notes' => [
                'datetime 的样式能力和 date、time、text 保持一致，便于整套后台表单统一视觉。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/datetime/DatetimeStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 rounded、flush 两种常见日期时间输入视觉变体。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_datetime_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Datetime::make('rounded_datetime', '圆角时间点', '用于卡片式设置区域')
                    ->rounded()
                    ->placeholder('请选择日期时间')
            )
            ->item(
                Datetime::make('flush_datetime', '扁平时间点', '用于紧凑编辑区')
                    ->flush()
                    ->placeholder('请选择日期时间')
            )
            ->fetch();
    }
}
