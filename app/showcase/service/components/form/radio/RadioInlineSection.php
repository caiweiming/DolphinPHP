<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio inline 能力块
 */
final class RadioInlineSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'inline'),
            'title' => (string) ($section['title'] ?? 'inline 水平排列'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'inline', 'value' => 'true 后采用水平排列，更适合 2 到 3 个短选项'],
                ['name' => 'options', 'value' => '短选项并排展示时可明显减少纵向高度'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'publish_scope',
        'label' => '发布范围',
        'inline' => true,
        'options' => [
            'private' => '仅自己',
            'team' => '团队可见',
            'public' => '公开',
        ],
        'value' => 'team',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('publish_scope', '发布范围')
    ->inline()
    ->options([
        'private' => '仅自己',
        'team' => '团队可见',
        'public' => '公开',
    ])
    ->value('team');
CODE,
            'notes' => [
                '当单选项数量较少、文案较短时，inline 能明显提升信息密度和操作效率。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioInlineSection.php',
                    'label' => 'inline 能力块',
                    'description' => '展示 radio 以内联形式横向排布的效果。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_inline_', false), 'inline 水平排列')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('publish_scope', '发布范围')
                    ->inline()
                    ->options([
                        'private' => '仅自己',
                        'team' => '团队可见',
                        'public' => '公开',
                    ])
                    ->value('team')
            )
            ->fetch();
    }
}
