<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;

/**
 * radio_group 业务表单片段能力块
 */
final class RadioGroupProfileFormSection
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
                ['name' => '套餐 + 风格组合', 'value' => '把计费与主题两个关键单选块组合进同一业务片段'],
                ['name' => 'value 回填', 'value' => '编辑工作区时明确回显当前套餐和主题风格'],
                ['name' => '富文案卡片', 'value' => '让开发者直接参考如何写带说明的业务选项'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'workspace_plan',
        'label' => '工作区套餐',
        'options' => [
            'starter' => '<div class="fw-semibold">起步版</div><div class="text-secondary small">适合小团队验证流程</div>',
            'team' => '<div class="fw-semibold">团队版</div><div class="text-secondary small">适合 10-50 人协作</div>',
            'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-secondary small">支持组织级权限与专属服务</div>',
        ],
        'value' => 'team',
    ],
    [
        'type' => 'radio_group',
        'name' => 'workspace_theme',
        'label' => '工作区风格',
        'options' => [
            'light' => '<div class="fw-semibold">明亮协作</div><div class="text-secondary small">信息密度高，适合后台操作</div>',
            'focus' => '<div class="fw-semibold">沉浸专注</div><div class="text-secondary small">弱化干扰，突出内容处理</div>',
        ],
        'value' => 'light',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('workspace_plan', '工作区套餐')
    ->options([
        'starter' => '<div class="fw-semibold">起步版</div><div class="text-secondary small">适合小团队验证流程</div>',
        'team' => '<div class="fw-semibold">团队版</div><div class="text-secondary small">适合 10-50 人协作</div>',
        'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-secondary small">支持组织级权限与专属服务</div>',
    ])
    ->value('team');

Field::radioGroup('workspace_theme', '工作区风格')
    ->options([
        'light' => '<div class="fw-semibold">明亮协作</div><div class="text-secondary small">信息密度高，适合后台操作</div>',
        'focus' => '<div class="fw-semibold">沉浸专注</div><div class="text-secondary small">弱化干扰，突出内容处理</div>',
    ])
    ->value('light');
CODE,
            'notes' => [
                '业务片段示例比单个字段更接近真实开发环境，开发者可以直接拿去改成自己的套餐与主题配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupProfileFormSection.php',
                    'label' => '业务表单片段能力块',
                    'description' => '把常见 radio_group 用法组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->items([
                [
                    'type' => 'radio_group',
                    'name' => 'workspace_plan',
                    'label' => '工作区套餐',
                    'options' => [
                        'starter' => '<div class="fw-semibold">起步版</div><div class="text-secondary small">适合小团队验证流程</div>',
                        'team' => '<div class="fw-semibold">团队版</div><div class="text-secondary small">适合 10-50 人协作</div>',
                        'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-secondary small">支持组织级权限与专属服务</div>',
                    ],
                    'value' => 'team',
                ],
                [
                    'type' => 'radio_group',
                    'name' => 'workspace_theme',
                    'label' => '工作区风格',
                    'options' => [
                        'light' => '<div class="fw-semibold">明亮协作</div><div class="text-secondary small">信息密度高，适合后台操作</div>',
                        'focus' => '<div class="fw-semibold">沉浸专注</div><div class="text-secondary small">弱化干扰，突出内容处理</div>',
                    ],
                    'value' => 'light',
                ],
            ])
            ->fetch();
    }
}
