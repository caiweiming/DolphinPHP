<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;

/**
 * select2 业务表单片段能力块
 */
final class Select2ProfileFormSection
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
                ['name' => '静态 + 远程组合', 'value' => '部门、用户、角色这些高频字段通常会混合使用静态 options 和远程 ajax'],
                ['name' => '多选角色', 'value' => '角色、标签、协作成员等字段非常适合 Select2 多选'],
                ['name' => '业务可抄性', 'value' => '示例更接近真实后台用户资料或组织架构表单'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'profile_user_id',
        'label' => '负责人',
        'tips' => '通过内置 ajax 搜索用户',
        'ajax' => [
            'url' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
            'table' => 'admin_user',
            'title' => 'username',
            'search' => 'username|nickname',
        ],
    ],
    [
        'type' => 'select2',
        'name' => 'profile_role_ids',
        'label' => '角色',
        'multiple' => true,
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'author' => '作者',
        ],
        'value' => 'admin,editor',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('profile_user_id', '负责人', '通过内置 ajax 搜索用户')
    ->ajax([
        'url' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
        'table' => 'admin_user',
        'title' => 'username',
        'search' => 'username|nickname',
    ])
    ->placeholder('请输入关键字搜索用户');

Field::select2('profile_role_ids', '角色')
    ->multiple()
    ->options([
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
    ])
    ->value('admin,editor');
CODE,
            'notes' => [
                '业务片段示例更适合开发者直接照抄，尤其是用户、角色、部门、标签这类后台高频字段。',
                '这里同样显式保留了 showcase 演示 URL，确保“源码展示”和“页面预览”使用的是同一套可运行配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2ProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '把常见 select2 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();
        $showcaseAjaxUrl = (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']);

        return Form::make(uniqid('showcase_select2_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'select2',
                    'name' => 'profile_user_id',
                    'label' => '负责人',
                    'tips' => '通过内置 ajax 搜索用户',
                    'placeholder' => '请输入关键字搜索用户',
                    'ajax' => [
                        'url' => $showcaseAjaxUrl,
                        'table' => 'admin_user',
                        'title' => 'username',
                        'search' => 'username|nickname',
                    ],
                ],
                [
                    'type' => 'select2',
                    'name' => 'profile_role_ids',
                    'label' => '角色',
                    'multiple' => true,
                    'options' => [
                        'admin' => '管理员',
                        'editor' => '编辑',
                        'author' => '作者',
                    ],
                    'value' => 'admin,editor',
                ],
            ])
            ->fetch();
    }
}
