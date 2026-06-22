<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 默认值与回填能力块
 */
final class SelectDefaultValueSection
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
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => 'gz 表示编辑页回显“广州”'],
                ['name' => 'options', 'value' => '建议使用稳定业务值，便于编辑态直接回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'current_city',
        'label' => '所在城市',
        'tips' => '编辑资料时回显当前已选城市',
        'options' => [
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
        ],
        'value' => 'gz',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('current_city', '所在城市', '编辑资料时回显当前已选城市')
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->value('gz');
CODE,
            'notes' => [
                'select 的 value 通常直接对应数据库中的枚举值或外键值，非常适合编辑态回显。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 select 在编辑态场景下如何回显当前选中值。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('current_city', '所在城市', '编辑资料时回显当前已选城市')
                    ->options([
                        'gz' => '广州',
                        'sz' => '深圳',
                        'sh' => '上海',
                    ])
                    ->value('gz')
            )
            ->fetch();
    }
}
