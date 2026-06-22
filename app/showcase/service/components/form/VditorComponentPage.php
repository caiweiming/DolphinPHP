<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase vditor 组件页组装器
 */
final class VditorComponentPage
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
                'summary' => 'Vditor 组件用于承载 Markdown 内容编辑场景，适合技术文档、博客正文、知识库条目等需要 Markdown 语法与即时预览能力的后台表单。',
                'scenarios' => [
                    '技术文档、博客正文、知识库条目等需要 Markdown 编辑的场景',
                    '希望保留 Markdown 源文本，同时支持即时渲染、分屏预览和图片上传的内容场景',
                    '需要通过模式、工具栏和上传参数精细化调整编辑器体验的后台内容表单',
                ],
                'capabilities' => [
                    '基础编辑器',
                    '默认值与回填',
                    '编辑模式',
                    '上传驱动与目录',
                    '工具栏配置',
                    '原生 options 配置',
                    '内容安全提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'content',
        'label' => '内容',
        'driver' => 'local',
        'dir' => 'articles',
        'options' => [
            'height' => 420,
            'mode' => 'wysiwyg',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('content', '内容')
    ->driver('local')
    ->dir('articles')
    ->options([
        'height' => 420,
        'mode' => 'wysiwyg',
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'rich.ueditor', 'title' => 'ueditor 富文本编辑器', 'status' => 'available'],
                ['key' => 'media.image', 'title' => 'image 图片上传', 'status' => 'available'],
                ['key' => 'media.file', 'title' => 'file 文件上传', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义 Markdown 字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是 Markdown 字符串。'],
                    ['name' => 'driver / dir', 'summary' => '控制编辑器内图片上传所使用的驱动和目录。'],
                    ['name' => 'required', 'summary' => '标记为必填 Markdown 内容字段。'],
                ],
            ],
            [
                'title' => '编辑器配置',
                'items' => [
                    ['name' => 'options.mode', 'summary' => '切换 `wysiwyg`、`ir`、`sv` 三种编辑模式。'],
                    ['name' => 'options.height', 'summary' => '控制编辑器可视区域高度。'],
                    ['name' => 'options.toolbar', 'summary' => '定义工具栏按钮集合。'],
                    ['name' => 'options.counter / preview', 'summary' => '控制字数统计、预览行为等增强能力。'],
                ],
            ],
            [
                'title' => '上传与资源',
                'items' => [
                    ['name' => 'options.upload.url', 'summary' => '默认会自动回填统一上传地址，也可通过 options 自定义。'],
                    ['name' => 'options.upload.extraData', 'summary' => '补充业务请求参数；默认还会附带 `_from / _ajax` 这类上传识别参数。'],
                    ['name' => '显式 driver 优先', 'summary' => '字段显式配置 driver 后，会覆盖系统默认上传驱动。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'vditor / ueditor', 'summary' => '需要保留 Markdown 源文本、支持程序员写作体验时优先用 vditor。'],
                    ['name' => '内容存储', 'summary' => 'Markdown 正文可直接存文本，但渲染输出前仍需做安全过滤。'],
                    ['name' => '团队协作', 'summary' => '知识库、文档、博客后台更适合统一采用 Markdown 编辑器。'],
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
