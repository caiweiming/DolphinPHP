<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 禁用指定选项能力块
 */
final class SelectOptionDisabledSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'option_disabled'),
            'title' => (string) ($section['title'] ?? '禁用指定选项'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'disabled', 'value' => '可传字符串或数组禁用部分选项，如 gz,sh 或 [\'gz\', \'sh\']'],
                ['name' => '灰度 / 下线项', 'value' => '适合城市、版本、套餐等部分候选项暂不可选的场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'limited_city',
        'label' => '可选城市',
        'tips' => '广州和上海暂未开放',
        'options' => [
            'gz' => '广州',
            'sz' => '深圳',
            'sh' => '上海',
        ],
        'disabled' => ['gz', 'sh'],
        'value' => 'gz',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('limited_city', '可选城市', '广州和上海暂未开放')
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->disabled(['gz', 'sh'])
    ->value('gz');
CODE,
            'notes' => [
                'select 的 disabled 既能禁用整组，也能禁用选项；这个能力比 radio 更适合在长选项列表里做灰度控制。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectOptionDisabledSection.php',
                    'label' => '禁用指定选项能力块',
                    'description' => '展示 select 如何禁用部分不可用选项。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_option_disabled_', false), '禁用指定选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('limited_city', '可选城市', '广州和上海暂未开放')
                    ->options([
                        'gz' => '广州',
                        'sz' => '深圳',
                        'sh' => '上海',
                    ])
                    ->disabled(['gz', 'sh'])
                    ->value('gz')
            )
            ->fetch();
    }
}
