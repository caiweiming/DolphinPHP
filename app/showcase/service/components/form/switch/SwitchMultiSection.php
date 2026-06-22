<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;

/**
 * switch 多开关组合能力块
 */
final class SwitchMultiSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multi_switch'),
            'title' => (string) ($section['title'] ?? '多开关组合'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => '可一次性配置多枚开关'],
                ['name' => 'value', 'value' => '用数组指定哪些开关默认开启'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'role_flags',
        'label' => '角色能力',
        'options' => [
            'status' => '启用状态',
            'is_admin' => '管理员权限',
            'can_export' => '允许导出',
        ],
        'value' => ['status', 'can_export'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
[
    Field::switch('role_flags', '角色能力')
        ->options([
            'status' => '启用状态',
            'is_admin' => '管理员权限',
            'can_export' => '允许导出',
        ])
        ->value(['status', 'can_export']),
]
CODE,
            'notes' => [
                '当你需要一组布尔能力时，可以让 switch 一次性渲染多项，避免重复写多段近似配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchMultiSection.php',
                    'label' => '多开关能力块',
                    'description' => '展示通过 options 一次性渲染多枚开关的方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_multi_', false), '多开关组合')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'switch',
                'name' => 'role_flags',
                'label' => '角色能力',
                'options' => [
                    'status' => '启用状态',
                    'is_admin' => '管理员权限',
                    'can_export' => '允许导出',
                ],
                'value' => ['status', 'can_export'],
            ])
            ->fetch();
    }
}
