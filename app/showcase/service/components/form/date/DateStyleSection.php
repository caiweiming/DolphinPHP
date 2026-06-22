<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 样式变体能力块
 */
final class DateStyleSection
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
                ['name' => 'rounded', 'value' => '更柔和的日期输入框视觉'],
                ['name' => 'flush', 'value' => '紧凑表单里的贴线式日期输入'],
            ],
            'array_code' => <<<'CODE'
[
    ['date', 'rounded_date', '圆角日期', '用于卡片式筛选区域'],
    ['date', 'flush_date', '扁平日期', '用于紧凑编辑区'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('rounded_date', '圆角日期', '用于卡片式筛选区域')->rounded();
Field::date('flush_date', '扁平日期', '用于紧凑编辑区')->flush();
CODE,
            'notes' => [
                'date 的样式能力和 text 类似，便于在同一套后台视觉里保持一致。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 rounded、flush 两种常见日期输入视觉变体。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Date::make('rounded_date', '圆角日期', '用于卡片式筛选区域')
                    ->rounded()
                    ->placeholder('请选择日期')
            )
            ->item(
                Date::make('flush_date', '扁平日期', '用于紧凑编辑区')
                    ->flush()
                    ->placeholder('请选择日期')
            )
            ->fetch();
    }
}
