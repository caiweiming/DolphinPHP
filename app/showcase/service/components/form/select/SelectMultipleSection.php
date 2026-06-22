<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 多选能力块
 */
final class SelectMultipleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple'),
            'title' => (string) ($section['title'] ?? 'multiple 多选'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple', 'value' => 'true 后启用多选模式并自动追加 name[]'],
                ['name' => 'placeholder', 'value' => '多选时仍建议给出明确的占位提示'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'cities',
        'label' => '覆盖城市',
        'tips' => '可一次选择多个开通城市',
        'multiple' => true,
        'options' => [
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('cities', '覆盖城市', '可一次选择多个开通城市')
    ->multiple()
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->placeholder('请选择覆盖城市');
CODE,
            'notes' => [
                '多选模式提交的是数组，后端处理时应按数组字段接收。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectMultipleSection.php',
                    'label' => '多选能力块',
                    'description' => '展示 select 开启 multiple 后的多选结构。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_multiple_', false), 'multiple 多选')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('cities', '覆盖城市', '可一次选择多个开通城市')
                    ->multiple()
                    ->options([
                        'gz' => '广州',
                        'sz' => '深圳',
                        'sh' => '上海',
                    ])
                    ->placeholder('请选择覆盖城市')
            )
            ->fetch();
    }
}
