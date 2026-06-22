<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\number;

use app\common\render\Form;
use app\common\render\form\items\number\Number;

/**
 * number 默认值能力块
 */
final class NumberDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与业务默认数'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '用于提供业务默认数或编辑态回显'],
                ['name' => 'min/max', 'value' => '常与默认值一起定义可调节范围'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'number',
        'name' => 'sort',
        'label' => '排序',
        'value' => 100,
        'min_length' => 0,
        'max_length' => 9999,
        'tips' => '数字越小越靠前',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::number('sort', '排序', '数字越小越靠前')
    ->value(100)
    ->min(0)
    ->max(9999);
CODE,
            'notes' => [
                '排序值、库存初始值、默认积分等字段通常应直接带一个业务默认数，减少用户反复输入。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/number/NumberDefaultValueSection.php',
                    'label' => '默认值能力块',
                    'description' => '展示 value 回显与业务默认数的常见配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_number_section_default_', false), '默认值与业务默认数')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Number::make('sort', '排序', '数字越小越靠前')
                    ->value(100)
                    ->min(0)
                    ->max(9999)
            )
            ->fetch();
    }
}
