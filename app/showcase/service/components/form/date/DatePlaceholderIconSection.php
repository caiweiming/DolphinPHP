<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\date;

use app\common\render\Form;
use app\common\render\form\items\date\Date;

/**
 * date 占位符与图标能力块
 */
final class DatePlaceholderIconSection
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
                ['name' => 'placeholder', 'value' => '指导用户输入预期的日期格式或业务语义'],
                ['name' => 'icon(left/right)', 'value' => '控制日期图标位于左侧或右侧'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'date',
        'name' => 'expire_date',
        'label' => '到期日期',
        'tips' => '用于授权或套餐失效控制',
        'placeholder' => '请选择到期日期',
        'icon' => 'right',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::date('expire_date', '到期日期', '用于授权或套餐失效控制')
    ->placeholder('请选择到期日期')
    ->icon('right');
CODE,
            'notes' => [
                '占位符负责业务引导，图标负责强化“这是日期输入”的视觉暗示。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/date/DatePlaceholderIconSection.php',
                    'label' => '占位符与图标能力块',
                    'description' => '展示 placeholder 与左右图标的常见组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_date_section_placeholder_', false), '占位符与图标')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Date::make('expire_date', '到期日期', '用于授权或套餐失效控制')
                    ->placeholder('请选择到期日期')
                    ->icon('right')
            )
            ->fetch();
    }
}
