<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase hidden 组件页组装器
 */
final class HiddenComponentPage
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
                'summary' => 'Hidden 组件用于在表单中携带不对用户展示但需要随表单提交的数据，适合编辑主键、上下文标识、状态标记等后台表单场景。',
                'scenarios' => [
                    '编辑主键、上下文标识、状态标记等无需展示但需要提交的数据场景',
                    '编辑页需要携带记录 id、租户 id、来源标记等后台上下文参数',
                    '希望开发者明确区分“可提交但不可见”和“可见但不可编辑”两类字段的场景',
                ],
                'capabilities' => [
                    '基础隐藏值',
                    '多字段传递',
                    '默认值与回填',
                    '编辑态主键传递',
                    '安全提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'hidden',
        'name' => 'id',
        'value' => 10001,
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::hidden('id')
    ->value(10001);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.static', 'title' => 'static 静态文本', 'status' => 'available'],
                ['key' => 'basic.html', 'title' => 'html HTML 内容', 'status' => 'available'],
                ['key' => 'basic.text', 'title' => 'text 单行文本框', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定 hidden 最终提交到后端的键名。'],
                    ['name' => 'value', 'summary' => '隐藏值本身，常用于主键、上下文标记和来源标记。'],
                    ['name' => 'id', 'summary' => '可选 DOM id，便于脚本读取或动态改值。'],
                ],
            ],
            [
                'title' => '使用边界',
                'items' => [
                    ['name' => '展示特性', 'summary' => 'hidden 不显示 label、tips 和内容本身，只负责提交数据。'],
                    ['name' => '默认回填', 'summary' => '编辑态通常直接通过 value 注入当前记录主键或上下文值。'],
                    ['name' => '动态赋值', 'summary' => '需要前端临时写值时，可通过 id 或自定义属性配合脚本处理。'],
                ],
            ],
            [
                'title' => '安全建议',
                'items' => [
                    ['name' => '不可直接信任', 'summary' => 'hidden 值可被浏览器工具修改，后端必须重新校验。'],
                    ['name' => '敏感信息', 'summary' => '不要用 hidden 传递密码、密钥或不可暴露的敏感数据。'],
                    ['name' => '主键更新', 'summary' => '更新动作应优先使用 URL 或服务端上下文中的真实主键。'],
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
