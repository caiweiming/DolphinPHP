<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;

/**
 * select 业务表单片段能力块
 */
final class SelectProfileFormSection
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
                ['name' => '单选 + 多选组合', 'value' => '角色、状态这类单选字段与标签、城市这类多选字段搭配使用'],
                ['name' => '默认值回显', 'value' => '编辑资料时明确回显当前角色与已选标签'],
                ['name' => '业务可抄性', 'value' => '示例更接近真实后台资料表单，可直接替换字段名复用'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'profile_role',
        'label' => '角色',
        'options' => [
            'member' => '普通成员',
            'editor' => '编辑成员',
            'owner' => '负责人',
        ],
        'value' => 'editor',
    ],
    [
        'type' => 'select',
        'name' => 'profile_tags',
        'label' => '标签',
        'multiple' => true,
        'options' => [
            'vip' => '重点客户',
            'trial' => '试用中',
            'renewal' => '待续费',
        ],
        'value' => 'vip,renewal',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('profile_role', '角色')
    ->options([
        'member' => '普通成员',
        'editor' => '编辑成员',
        'owner' => '负责人',
    ])
    ->value('editor');

Field::select('profile_tags', '标签')
    ->multiple()
    ->options([
        'vip' => '重点客户',
        'trial' => '试用中',
        'renewal' => '待续费',
    ])
    ->value('vip,renewal');
CODE,
            'notes' => [
                '业务片段示例更适合开发者直接照抄，尤其是角色、标签、城市这类后台高频字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '把常见 select 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'select',
                    'name' => 'profile_role',
                    'label' => '角色',
                    'options' => [
                        'member' => '普通成员',
                        'editor' => '编辑成员',
                        'owner' => '负责人',
                    ],
                    'value' => 'editor',
                ],
                [
                    'type' => 'select',
                    'name' => 'profile_tags',
                    'label' => '标签',
                    'multiple' => true,
                    'options' => [
                        'vip' => '重点客户',
                        'trial' => '试用中',
                        'renewal' => '待续费',
                    ],
                    'value' => 'vip,renewal',
                ],
            ])
            ->fetch();
    }
}
