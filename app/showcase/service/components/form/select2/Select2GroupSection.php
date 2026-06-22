<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;

/**
 * select2 分组选项能力块
 */
final class Select2GroupSection
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
                ['name' => 'group + multiple', 'value' => 'select2 中分组和多选可以同时使用，适合地区、部门树扁平分组场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'region_city',
        'label' => '地区城市',
        'tips' => '按区域分组展示城市',
        'group' => [
            '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
            '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
        ],
        'multiple' => true,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('region_city', '地区城市', '按区域分组展示城市')
    ->options([
        '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
        '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
    ])
    ->multiple();
CODE,
            'notes' => [
                '当前 `Select2` 门面类没有 `group()` 方法；链式写法里直接传多维 `options`，底层会自动转换为 optgroup。',
                '当选项数较多但仍有明确业务归类时，分组 + 搜索通常是比普通 select 更好的交互方式。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2GroupSection.php',
                    'label' => '分组选项能力块',
                    'description' => '展示 select2 如何通过 group 渲染 optgroup 结构。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_group_', false), '分组选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select2',
                'name' => 'region_city',
                'label' => '地区城市',
                'tips' => '按区域分组展示城市',
                'group' => [
                    '广东' => ['gz' => '广州', 'sz' => '深圳', 'sh' => '上海'],
                    '湖北' => ['wh' => '武汉', 'hs' => '黄石', 'dy' => '大冶'],
                ],
                'multiple' => true,
            ])
            ->fetch();
    }
}
