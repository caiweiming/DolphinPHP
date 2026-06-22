<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase text 组件页组装器
 */
final class TextComponentPage
{
    /**
     * 组装 text 组件页
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
                'summary' => 'Text 组件用于承载单行文本输入，适合名称、标题、别名、短链接等场景。',
                'scenarios' => [
                    '用户名、昵称、页面标题等短文本字段',
                    '域名、邮箱、短链标识这类带格式约束的输入',
                    '需要只读、禁用、校验态反馈的后台编辑表单',
                ],
                'capabilities' => [
                    '占位提示',
                    '必填与长度限制',
                    '前后缀',
                    'group_type 分组类型',
                    '只读与禁用',
                    '校验反馈状态',
                    '圆角 / 扁平 / 浮动标签',
                    '自动补全 datalist',
                    '扩展参数与 DOM 控制',
                    '业务组合表单',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['text', 'title', '标题', '请输入页面标题'],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('title', '标题', '请输入页面标题')
    ->placeholder('例如 首页横幅标题')
    ->required()
    ->max(60)
    ->min(4);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'basic.textarea', 'title' => 'textarea 多行文本', 'status' => 'available'],
                ['key' => 'basic.number', 'title' => 'number 数字输入', 'status' => 'available'],
                ['key' => 'basic.switch', 'title' => 'switch 开关', 'status' => 'available'],
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
                    ['name' => 'name', 'summary' => '字段名，决定提交键名与 DOM 标识。'],
                    ['name' => 'label', 'summary' => '表单标题，配合 placeholder 与浮动标签展示。'],
                    ['name' => 'tips', 'summary' => '字段下方提示文案，用于补充输入说明。'],
                    ['name' => 'help', 'summary' => '标签右侧的帮助提示，可用于解释业务限制。'],
                    ['name' => 'placeholder', 'summary' => '未输入时的提示文本。'],
                    ['name' => 'value', 'summary' => '默认值或回显值。'],
                ],
            ],
            [
                'title' => '样式参数',
                'items' => [
                    ['name' => 'rounded', 'summary' => '圆角输入框样式。'],
                    ['name' => 'flush', 'summary' => '扁平下划线风格。'],
                    ['name' => 'float', 'summary' => '浮动标签布局。'],
                    ['name' => 'size', 'summary' => '控制输入框尺寸，常用 sm / lg。'],
                    ['name' => 'extra_class', 'summary' => '给字段追加额外样式类。'],
                ],
            ],
            [
                'title' => '长度与状态',
                'items' => [
                    ['name' => 'required', 'summary' => '标记为必填。'],
                    ['name' => 'min_length/max_length', 'summary' => '限制文本最小和最大长度。'],
                    ['name' => 'readonly/disabled', 'summary' => '控制只读与禁用状态。'],
                    ['name' => 'valid', 'summary' => '展示成功态或失败态反馈样式。'],
                ],
            ],
            [
                'title' => '增强能力',
                'items' => [
                    ['name' => 'prefix/suffix', 'summary' => '在输入框前后展示文本、图标或按钮内容。'],
                    ['name' => 'group_type', 'summary' => '控制前后缀采用 text、icon 或 button 模式。'],
                    ['name' => 'datalist', 'summary' => '提供浏览器原生自动补全选项。'],
                    ['name' => 'id/props', 'summary' => '控制 DOM id 与透传原生属性，便于脚本挂载。'],
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
