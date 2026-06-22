<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio 选项配置能力块
 */
final class RadioOptionsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'options'),
            'title' => (string) ($section['title'] ?? '选项配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => '建议用业务常量值作为 key，文案作为 value'],
                ['name' => 'tips', 'value' => '可配合提示文案说明各选项差异'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'permission_level',
        'label' => '权限等级',
        'tips' => '不同等级对应不同后台操作权限',
        'options' => [
            'viewer' => '只读成员',
            'editor' => '编辑成员',
            'owner' => '负责人',
        ],
        'value' => 'editor',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('permission_level', '权限等级', '不同等级对应不同后台操作权限')
    ->options([
        'viewer' => '只读成员',
        'editor' => '编辑成员',
        'owner' => '负责人',
    ])
    ->value('editor');
CODE,
            'notes' => [
                '当单选项承载业务角色或状态枚举时，选项配置本身就是一份可复用的业务字典。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioOptionsSection.php',
                    'label' => '选项配置能力块',
                    'description' => '展示 options 在角色、等级等枚举场景中的写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_options_', false), '选项配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('permission_level', '权限等级', '不同等级对应不同后台操作权限')
                    ->options([
                        'viewer' => '只读成员',
                        'editor' => '编辑成员',
                        'owner' => '负责人',
                    ])
                    ->value('editor')
            )
            ->fetch();
    }
}
