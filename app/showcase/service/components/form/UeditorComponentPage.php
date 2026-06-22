<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase ueditor 组件页组装器
 */
final class UeditorComponentPage
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
                'summary' => 'Ueditor 组件用于承载所见即所得的富文本编辑场景，适合文章正文、图文详情、帮助中心内容等需要结构化 HTML 内容的后台表单。',
                'scenarios' => [
                    '文章正文、帮助中心、图文详情等需要 HTML 富文本编辑的场景',
                    '希望在编辑器内直接上传图片、附件和视频，并统一接入上传驱动的内容管理场景',
                    '需要通过工具栏、配置接口和原生 options 调整编辑体验的后台内容表单',
                ],
                'capabilities' => [
                    '基础编辑器',
                    '默认值与回填',
                    '编辑器高度',
                    '上传驱动与目录',
                    '自定义 serverUrl',
                    '原生 options 配置',
                    '内容安全提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'content',
        'label' => '内容',
        'driver' => 'local',
        'dir' => 'articles',
        'options' => [
            'initialFrameHeight' => 360,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('content', '内容')
    ->driver('local')
    ->dir('articles')
    ->options([
        'initialFrameHeight' => 360,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'rich.vditor', 'title' => 'vditor Markdown 编辑器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义富文本字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是 HTML 字符串。'],
                    ['name' => 'driver / dir', 'summary' => '控制编辑器内资源上传所使用的驱动和目录。'],
                    ['name' => 'required', 'summary' => '标记为必填内容字段。'],
                ],
            ],
            [
                'title' => '编辑器配置',
                'items' => [
                    ['name' => 'options.initialFrameHeight', 'summary' => '控制编辑器可视区域高度。'],
                    ['name' => 'options.serverUrl', 'summary' => '指定 UEditor 的配置接口地址，默认是 `/_form/ueditor/config`。'],
                    ['name' => 'options.toolbars', 'summary' => '控制工具栏分组与可用操作。'],
                    ['name' => 'options.autoFloatEnabled', 'summary' => '控制工具栏滚动时是否自动悬浮。'],
                ],
            ],
            [
                'title' => '上传与资源',
                'items' => [
                    ['name' => '图片/附件/视频上传', 'summary' => '统一复用 DolphinPHP 上传驱动体系与目录路由。'],
                    ['name' => 'listImage / listFile', 'summary' => '默认配置接口还支持 `listImage / listFile` 资源列表拉取。'],
                    ['name' => '显式 driver 优先', 'summary' => '字段显式配置 driver 后，会覆盖系统默认上传驱动。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'ueditor / vditor', 'summary' => '需要 HTML 富文本和更完整的所见即所得工具栏时优先用 ueditor。'],
                    ['name' => '内容存储', 'summary' => '长内容建议使用 `LONGTEXT` 字段，并在入库前做内容过滤。'],
                    ['name' => '资源治理', 'summary' => '文章正文中的图片、文件最好统一走业务目录，方便后续清理与迁移。'],
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
