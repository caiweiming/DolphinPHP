<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase image 组件页组装器
 */
final class ImageComponentPage
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
                'summary' => 'Image 组件用于承载后台图片上传与回显，适合头像、封面图、商品图集、活动海报等需要预览、管理和多图提交的表单场景。',
                'scenarios' => [
                    '头像、封面图、商品图集等需要上传并即时预览图片的场景',
                    '希望在一个字段里完成单图、多图、上传驱动切换和目录控制的后台表单',
                    '需要通过原生 options 控制数量、大小、类型和自动上传策略的图片上传场景',
                ],
                'capabilities' => [
                    '基础上传',
                    '默认值与回填',
                    '多图与数量限制',
                    '上传驱动与目录',
                    '按钮控制',
                    '只读与原生配置',
                    '上传约束',
                    '上传地址与附加参数',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'cover',
        'label' => '封面图',
        'tips' => '上传一张封面图',
        'dir' => 'covers',
        'options' => [
            'maxFileSize' => '2MB',
            'autoUpload' => false,
        ],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('cover', '封面图', '上传一张封面图')
    ->dir('covers')
    ->options([
        'maxFileSize' => '2MB',
        'autoUpload' => false,
    ]);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'media.file', 'title' => 'file 文件上传', 'status' => 'available'],
                ['key' => 'media.cropper', 'title' => 'cropper 图片裁剪', 'status' => 'available'],
                ['key' => 'media.image_select', 'title' => 'image_select 图片选择器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义图片上传字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，支持单图 id 或多图 id 数组。'],
                    ['name' => 'driver / dir', 'summary' => '控制上传驱动和存储目录。'],
                    ['name' => 'readonly', 'summary' => '切换为只读预览模式，关闭上传与删除交互。'],
                ],
            ],
            [
                'title' => '交互控制',
                'items' => [
                    ['name' => 'upload / browser / delete / clear', 'summary' => '控制上传、浏览、删除和清空按钮的显示策略。'],
                    ['name' => 'multiple', 'summary' => '开启多图上传，并可直接指定最大文件数量。'],
                    ['name' => 'required', 'summary' => '标记为必传图片字段。'],
                    ['name' => 'tips', 'summary' => '补充推荐尺寸、数量上限和格式说明。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options.maxFileSize', 'summary' => '限制单张图片大小，支持数字字节或带单位字符串。'],
                    ['name' => 'options.allowedTypes', 'summary' => '限制允许上传的图片类型。'],
                    ['name' => 'options.autoUpload', 'summary' => '控制是否选择后立即上传。'],
                    ['name' => 'options.url / extraData', 'summary' => '自定义上传地址与补充请求参数。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'image / cropper', 'summary' => '只需要上传与预览时用 image；需要裁剪时用 cropper。'],
                    ['name' => 'image / file', 'summary' => '只接受图片时用 image；任意文件上传时用 file。'],
                    ['name' => '多图存储', 'summary' => '多图字段提交为数组，落库前通常需要统一转换为逗号字符串或 JSON。'],
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
