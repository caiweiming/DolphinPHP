<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase file 组件页组装器
 */
final class FileComponentPage
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
                'summary' => 'File 组件用于承载后台附件上传与管理，适合合同、文档、资料包、压缩包等非图片资源的单文件或多文件提交场景。',
                'scenarios' => [
                    '合同、资料包、PDF 附件等需要上传并下载文件的场景',
                    '希望在一个字段里完成单文件、多文件、下载、目录控制和驱动切换的后台表单',
                    '需要限制文件类型、大小和附加业务参数的附件上传场景',
                ],
                'capabilities' => [
                    '基础上传',
                    '默认值与回填',
                    '多文件与数量限制',
                    '上传驱动与目录',
                    '按钮控制',
                    '只读与下载',
                    '上传约束',
                    '上传地址与附加参数',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'attachment',
        'label' => '附件',
        'tips' => '支持 PDF、Word、Excel 格式',
        'dir' => 'documents',
        'options' => [
            'allowedTypes' => ['application/pdf', '.doc', '.docx', '.xls', '.xlsx'],
            'maxFileSize' => '10MB',
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('attachment', '附件', '支持 PDF、Word、Excel 格式')
    ->dir('documents')
    ->options([
        'allowedTypes' => ['application/pdf', '.doc', '.docx', '.xls', '.xlsx'],
        'maxFileSize' => '10MB',
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'media.image', 'title' => 'image 图片上传', 'status' => 'available'],
                ['key' => 'media.cropper', 'title' => 'cropper 图片裁剪', 'status' => 'available'],
                ['key' => 'rich.ueditor', 'title' => 'ueditor 富文本编辑器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义附件字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，支持单文件 id 或多文件 id 数组。'],
                    ['name' => 'driver / dir', 'summary' => '控制上传驱动和存储目录。'],
                    ['name' => 'readonly / download', 'summary' => '切换只读预览和下载按钮策略。'],
                ],
            ],
            [
                'title' => '交互控制',
                'items' => [
                    ['name' => 'upload / browser / delete / clear', 'summary' => '控制上传、浏览、删除和清空按钮的显示策略。'],
                    ['name' => 'multiple', 'summary' => '开启多文件上传，并可直接指定最大文件数量。'],
                    ['name' => 'download', 'summary' => '控制是否展示下载按钮。'],
                    ['name' => 'required', 'summary' => '标记为必传附件字段。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.allowedTypes', 'summary' => '限制允许上传的文件类型。'],
                    ['name' => 'options.maxFileSize', 'summary' => '限制单个文件大小，支持数字字节或带单位字符串。'],
                    ['name' => 'options.autoUpload', 'summary' => '控制是否选择文件后立即上传。'],
                    ['name' => 'options.url / extraData', 'summary' => '自定义上传地址与补充请求参数。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'file / image', 'summary' => '上传图片并预览时用 image；非图片附件用 file。'],
                    ['name' => 'file / ueditor', 'summary' => '富文本内嵌资源上传走编辑器，独立附件字段用 file。'],
                    ['name' => '多文件存储', 'summary' => '多文件字段提交为数组，落库前通常需要统一转换为逗号字符串或 JSON。'],
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
