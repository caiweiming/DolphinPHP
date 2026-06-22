<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch 默认值能力块
 */
final class SwitchDefaultValueSection
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
            'title' => (string) ($section['title'] ?? '默认值与开关状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '1 为默认开启，0 为默认关闭'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'status',
        'label' => '启用状态',
        'value' => 1,
    ],
    [
        'type' => 'switch',
        'name' => 'notify_enabled',
        'label' => '消息通知',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('status', '启用状态')
    ->value(1);

Field::switch('notify_enabled', '消息通知')
    ->value(0);
CODE,
            'notes' => [
                'value 决定默认勾选状态，开发者最常见的误区是只在前端改默认值，却忽略后端未选中时不提交。',],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchDefaultValueSection.php',
                    'label' => '默认值能力块',
                    'description' => '展示 1/0 对应的默认开启与默认关闭状态。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_default_', false), '默认值与开关状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Toggle::make('status', '启用状态')->id('status_default_value')->value(1))
            ->item(Toggle::make('notify_enabled', '消息通知')->id('notify_enabled_default_value')->value(0))
            ->fetch();
    }
}
