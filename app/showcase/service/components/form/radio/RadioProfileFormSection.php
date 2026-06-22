<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;

/**
 * radio 业务表单片段能力块
 */
final class RadioProfileFormSection
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
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'inline + required', 'value' => '状态、可见范围等高频单选场景'],
                ['name' => 'value 回填', 'value' => '编辑用户资料时明确回显当前选择'],
                ['name' => '多字段组合', 'value' => '把多个 radio 组合成完整的业务配置片段'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'profile_gender',
        'label' => '性别',
        'inline' => true,
        'options' => [
            'male' => '男',
            'female' => '女',
        ],
        'value' => 'male',
    ],
    [
        'type' => 'radio',
        'name' => 'profile_status',
        'label' => '账号状态',
        'inline' => true,
        'required' => true,
        'options' => [
            'active' => '启用',
            'locked' => '锁定',
        ],
        'value' => 'active',
    ],
    [
        'type' => 'radio',
        'name' => 'profile_visibility',
        'label' => '资料可见范围',
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

Field::radio('profile_gender', '性别')
    ->inline()
    ->options([
        'male' => '男',
        'female' => '女',
    ])
    ->value('male');

Field::radio('profile_status', '账号状态')
    ->inline()
    ->required()
    ->options([
        'active' => '启用',
        'locked' => '锁定',
    ])
    ->value('active');

Field::radio('profile_visibility', '资料可见范围')
    ->options([
        'private' => '仅自己',
        'team' => '团队可见',
        'public' => '公开',
    ])
    ->value('team');
CODE,
            'notes' => [
                '业务片段示例更适合开发者直接照抄再改字段名，比单个孤立示例更接近真实开发。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '把常见 radio 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'radio',
                    'name' => 'profile_gender',
                    'label' => '性别',
                    'inline' => true,
                    'options' => [
                        'male' => '男',
                        'female' => '女',
                    ],
                    'value' => 'male',
                ],
                [
                    'type' => 'radio',
                    'name' => 'profile_status',
                    'label' => '账号状态',
                    'inline' => true,
                    'required' => true,
                    'options' => [
                        'active' => '启用',
                        'locked' => '锁定',
                    ],
                    'value' => 'active',
                ],
                [
                    'type' => 'radio',
                    'name' => 'profile_visibility',
                    'label' => '资料可见范围',
                    'options' => [
                        'private' => '仅自己',
                        'team' => '团队可见',
                        'public' => '公开',
                    ],
                    'value' => 'team',
                ],
            ])
            ->fetch();
    }
}
