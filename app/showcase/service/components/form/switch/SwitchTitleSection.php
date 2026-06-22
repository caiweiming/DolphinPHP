<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch title 能力块
 */
final class SwitchTitleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'title'),
            'title' => (string) ($section['title'] ?? '标题说明 title'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'title', 'value' => '展示在开关右侧，用于补充简短说明'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'is_public',
        'label' => '公开',
        'title' => '是否公开显示',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('is_public', '公开')
    ->title('是否公开显示')
    ->value(0);
CODE,
            'notes' => [
                'title 适合用来补充一句紧贴开关本身的语义说明，比只放在 tips 里更直观。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchTitleSection.php',
                    'label' => 'title 能力块',
                    'description' => '展示 title 在开关右侧的说明作用。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_title_', false), '标题说明 title')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Toggle::make('is_public', '公开')
                    ->title('是否公开显示')
                    ->value(0)
            )
            ->fetch();
    }
}
