<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase password 组件页组装器
 */
final class PasswordComponentPage
{
    /**
     * @param array<string, mixed> $component
     * @param list<array<string, string>> $sectionCatalog
     * @return array<string, mixed>
     */
    public function build(array $component, array $sectionCatalog): array
    {
        $sections = [];

        foreach ($sectionCatalog as $sectionMeta) {
            $builderClass = (string) ($sectionMeta['builder'] ?? '');
            if ($builderClass === '') {
                continue;
            }

            $sections[] = app($builderClass)->build($component, $sectionMeta);
        }

        return [
            'component' => $component,
            'overview' => [
                'summary' => 'Password 组件用于承载密码、密钥、口令等敏感单行输入，适合登录、注册、重置密码和安全校验类后台表单场景。',
                'scenarios' => [
                    '登录密码、注册密码、API 密钥等需要隐藏输入内容的场景',
                    '希望同时展示密码显示切换、强度提示和长度要求的安全配置表单',
                    '需要结合只读、禁用、校验状态展示密码输入规则的后台场景',
                ],
                'capabilities' => [
                    '基础密码输入',
                    '占位符与提示',
                    '长度限制',
                    '显示切换与强度提示',
                    '前后缀与样式',
                    '只读与禁用',
                    '校验与反馈状态',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'password',
        'label' => '密码',
        'switch' => true,
        'strength' => true,
        'min_length' => 6,
        'max_length' => 20,
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::password('password', '密码')
    ->switch()
    ->strength()
    ->minLength(6)
    ->maxLength(20);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
                ['key' => 'basic.textarea', 'title' => 'textarea 多行文本', 'status' => 'available'],
                ['key' => 'basic.number', 'title' => 'number 数字输入', 'status' => 'available'],
            ],
            'doc_links' => (array) ($component['doc_links'] ?? []),
            'sidebar_source_refs' => array_slice($this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections), 0, 3),
            'source_refs' => $this->mergeSourceRefs((array) ($component['source_refs'] ?? []), $sections),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paramGroups(): array
    {
        return [
            [
                'title' => '基础参数',
                'items' => [
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义密码字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常仅用于非真实密码展示场景。'],
                    ['name' => 'required', 'summary' => '标记为必填密码字段。'],
                    ['name' => 'placeholder', 'summary' => '引导用户按要求输入密码。'],
                ],
            ],
            [
                'title' => '安全与交互',
                'items' => [
                    ['name' => 'switch', 'summary' => '开启显示/隐藏密码切换按钮。'],
                    ['name' => 'strength', 'summary' => '显示密码强度条与强度文案。'],
                    ['name' => 'min_length / max_length', 'summary' => '限制密码长度范围。'],
                ],
            ],
            [
                'title' => '样式与状态',
                'items' => [
                    ['name' => 'prefix / suffix', 'summary' => '补充图标、按钮或说明文字。'],
                    ['name' => 'rounded / flush / float', 'summary' => '控制输入框视觉风格。'],
                    ['name' => 'readonly / disabled', 'summary' => '只读和禁用态展示。'],
                    ['name' => 'valid(true/false)', 'summary' => '展示成功/失败的校验反馈样式。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'password / text', 'summary' => '需要隐藏输入内容时用 password，普通明文输入用 text。'],
                    ['name' => '强度提示', 'summary' => '用户注册、重置密码等场景建议开启。'],
                    ['name' => '口令存储', 'summary' => '真实密码入库前应做哈希处理，不应直接明文保存。'],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $componentSources
     * @param list<array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    private function mergeSourceRefs(array $componentSources, array $sections): array
    {
        $result = $componentSources;
        $seen = [];

        foreach ($componentSources as $source) {
            $path = (string) ($source['path'] ?? '');
            if ($path !== '') {
                $seen[$path] = true;
            }
        }

        foreach ($sections as $section) {
            foreach ((array) ($section['source_refs'] ?? []) as $source) {
                $path = (string) ($source['path'] ?? '');
                if ($path === '' || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $result[] = $source;
            }
        }

        return $result;
    }
}
