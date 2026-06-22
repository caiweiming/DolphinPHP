<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 禁用指定选项能力块
 */
final class Select2OptionDisabledSection
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
                ['name' => 'disabled', 'value' => '可传字符串或数组禁用部分候选项，如 gz,sh 或 [\'gz\', \'sh\']'],
                ['name' => '灰度项控制', 'value' => '适合远程数据尚未开放或静态选项暂不可选的场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
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

Field::select2('limited_city', '可选城市', '广州和上海暂未开放')
    ->options([
        'gz' => '广州',
        'sz' => '深圳',
        'sh' => '上海',
    ])
    ->disabled(['gz', 'sh'])
    ->value('gz');
CODE,
            'notes' => [
                'select2 复用了 select 的禁用选项逻辑，因此开发者可以按同样的参数方式配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2OptionDisabledSection.php',
                    'label' => '禁用指定选项能力块',
                    'description' => '展示 select2 如何禁用部分候选项。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_option_disabled_', false), '禁用指定选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('limited_city', '可选城市', '广州和上海暂未开放')
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
