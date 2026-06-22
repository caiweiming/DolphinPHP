<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;

/**
 * select 扩展参数能力块
 */
final class SelectAdvancedPropsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'advanced_props'),
            'title' => (string) ($section['title'] ?? '扩展参数与 DOM 控制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'help', 'value' => '在标签右侧补充帮助说明'],
                ['name' => 'id / class', 'value' => '自定义 DOM id 和样式类，便于局部样式或脚本挂载'],
                ['name' => 'props', 'value' => '透传原生属性，如 disabled、data-id、data-scene'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'profile_channel',
        'label' => '来源渠道',
        'tips' => '用于标记该客户从哪个入口进入',
        'help' => '一般由运营配置，建议保持和统计后台枚举一致',
        'id' => 'showcase-select-profile-channel',
        'class' => 'test',
        'props' => 'disabled data-id="2" data-scene="profile"',
        'options' => [
            'ads' => '广告投放',
            'seo' => '自然搜索',
            'sales' => '销售录入',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('profile_channel', '来源渠道', '用于标记该客户从哪个入口进入')
    ->id('showcase-select-profile-channel')
    ->class('test')
    ->props('disabled data-id="2" data-scene="profile"')
    ->options([
        'ads' => '广告投放',
        'seo' => '自然搜索',
        'sales' => '销售录入',
    ]);
CODE,
            'notes' => [
                'help 当前更适合数组配置，因为 select 链式 API 没有单独的 help() 方法。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectAdvancedPropsSection.php',
                    'label' => '扩展参数能力块',
                    'description' => '展示 help、id、class、props 等进阶参数。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_advanced_', false), '扩展参数与 DOM 控制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select',
                'name' => 'profile_channel',
                'label' => '来源渠道',
                'tips' => '用于标记该客户从哪个入口进入',
                'help' => '一般由运营配置，建议保持和统计后台枚举一致',
                'id' => 'showcase-select-profile-channel',
                'class' => 'test',
                'props' => 'disabled data-id="2" data-scene="profile"',
                'options' => [
                    'ads' => '广告投放',
                    'seo' => '自然搜索',
                    'sales' => '销售录入',
                ],
            ])
            ->fetch();
    }
}
