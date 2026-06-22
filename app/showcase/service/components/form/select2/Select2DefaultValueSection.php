<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 默认值与回填能力块
 */
final class Select2DefaultValueSection
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
                ['name' => 'options', 'value' => '建议使用稳定值，便于静态选项和远程选项统一回显'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
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

Field::select2('current_city', '所在城市', '编辑资料时回显当前已选城市')
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->value('gz');
CODE,
            'notes' => [
                'select2 的回显逻辑和 select 一致，但更常用于后续可能切到远程搜索的数据字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2DefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 select2 在编辑态场景下如何回显当前选中值。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('current_city', '所在城市', '编辑资料时回显当前已选城市')
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
