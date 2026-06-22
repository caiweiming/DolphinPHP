<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;

/**
 * select 分组选项能力块
 */
final class SelectGroupSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'group'),
            'title' => (string) ($section['title'] ?? '分组选项'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'group', 'value' => '显式传入分组选项结构，最终渲染为 optgroup'],
                ['name' => '多维 options', 'value' => '当前组件也支持多维 options 自动转换为 group'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'region_city',
        'label' => '地区城市',
        'tips' => '按区域分组展示城市',
        'options' => [
            '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
            '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('region_city', '地区城市', '按区域分组展示城市')
    ->options([
        '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
        '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
    ]);
CODE,
            'notes' => [
                '分组更适合选项数较多、但又有明显业务归类的场景，比如地区、部门、产品线。',
                '如果你已经在业务里拿到的是多维 options 数组，可以直接传给 options，组件会自动转换成 optgroup，无需额外手工组装 group。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectGroupSection.php',
                    'label' => '分组选项能力块',
                    'description' => '展示 select 如何通过 group 渲染 optgroup 结构。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_group_', false), '分组选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select',
                'name' => 'region_city',
                'label' => '地区城市',
                'tips' => '按区域分组展示城市',
                'group' => [
                    '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
                    '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
                ],
            ])
            ->fetch();
    }
}
