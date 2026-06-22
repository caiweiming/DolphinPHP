<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 基础用法能力块
 */
final class DateBasicSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? '基础用法'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'name', 'value' => 'birthday / start_date'],
                ['name' => 'label', 'value' => '生日 / 生效日期'],
            ],
            'array_code' => <<<'CODE'
[
    ['date', 'birthday', '生日', '请选择生日'],
    ['date', 'start_date', '生效日期', '请选择生效日期'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('birthday', '生日', '请选择生日');
Field::date('start_date', '生效日期', '请选择生效日期');
CODE,
            'notes' => [
                '基础 date 适合最常见的单日期录入场景。',
                '底层仍复用统一日期选择器，只是默认关闭时间选择。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DateBasicSection.php',
                    'label' => '基础用法能力块',
                    'description' => '组装 date 的基础示例与代码片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_basic_', false), '基础用法')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Date::make('birthday', '生日', '请选择生日')->placeholder('例如 1998-08-18'))
            ->item(Date::make('start_date', '生效日期', '请选择生效日期')->placeholder('例如 2026-06-01'))
            ->fetch();
    }
}
