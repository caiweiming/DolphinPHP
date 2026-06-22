<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch 业务综合示例能力块
 */
final class SwitchProfileFormSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务综合示例'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value + tips', 'value' => '适合状态开关、管理员开关等常见布尔字段'],
                ['name' => 'disabled', 'value' => '适合当前账号不可修改的高风险字段'],
                ['name' => '后端默认值', 'value' => '适合直接复制到控制器处理逻辑中'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'user_status',
        'label' => '状态',
        'tips' => '是否启用该用户',
        'value' => 1,
    ],
    [
        'type' => 'switch',
        'name' => 'user_is_admin',
        'label' => '管理员',
        'tips' => '是否为管理员',
        'value' => 0,
    ],
]

// 控制器提交时：
$data['user_status'] = $data['user_status'] ?? 0;
$data['user_is_admin'] = $data['user_is_admin'] ?? 0;
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('user_status', '状态', '是否启用该用户')
    ->value(1);

Field::switch('user_is_admin', '管理员', '是否为管理员')
    ->value(0);
CODE,
            'notes' => [
                '综合示例把最常见的两个布尔字段和后端默认值处理放在一起，开发者几乎可以直接照抄。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常见 switch 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_profile_', false), '业务综合示例')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Toggle::make('user_status', '状态', '是否启用该用户')
                    ->value(1)
            )
            ->item(
                Toggle::make('user_is_admin', '管理员', '是否为管理员')
                    ->value(0)
            )
            ->item(
                Toggle::make('edit_self_locked', '编辑自己时锁定状态', '编辑自己账号时建议禁用该开关防止误操作')
                    ->value(1)
                    ->disabled(true)
            )
            ->fetch();
    }
}
