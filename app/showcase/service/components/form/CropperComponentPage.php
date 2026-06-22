<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * Showcase cropper 组件页组装器
 */
final class CropperComponentPage
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
                'summary' => 'Cropper 组件用于承载上传后裁剪的图片编辑场景，适合头像、封面、分享海报等对尺寸和构图有明确要求的后台表单。',
                'scenarios' => [
                    '头像、海报、封面图等需要上传后即时裁剪的场景',
                    '希望在一个字段里完成上传、预览、旋转、缩放和裁剪提交的后台表单',
                    '需要按不同业务场景切换上传驱动、目录与裁剪操作按钮的图片编辑场景',
                ],
                'capabilities' => [
                    '基础裁剪',
                    '默认值与回填',
                    '按钮组配置',
                    '裁剪动作',
                    '上传驱动与目录',
                    '原生 options 配置',
                    '固定尺寸提示',
                    '业务表单片段',
                ],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'avatar',
        'label' => '头像',
        'tips' => '建议上传 200x200 图片',
        'driver' => 'local',
        'dir' => 'avatars',
        'buttons' => ['upload', 'delete'],
    ],
]
CODE,
                    'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('avatar', '头像')
    ->tips('建议上传 200x200 图片')
    ->driver('local')
    ->dir('avatars')
    ->buttons(['upload', 'delete']);
CODE,
                ],
            ],
            'param_groups' => $this->paramGroups(),
            'sections' => $sections,
            'related_components' => [
                ['key' => 'media.image', 'title' => 'image 图片上传', 'status' => 'available'],
                ['key' => 'media.image_select', 'title' => 'image_select 图片选择器', 'status' => 'available'],
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
                    ['name' => 'name / label / tips', 'summary' => '字段名、标题与提示文案，定义裁剪字段的业务语义。'],
                    ['name' => 'value', 'summary' => '默认值或编辑态回显值，通常是附件 id。'],
                    ['name' => 'driver / dir', 'summary' => '控制裁剪后上传的驱动和目录。'],
                    ['name' => 'required', 'summary' => '标记为必传裁剪图片字段。'],
                ],
            ],
            [
                'title' => '交互控制',
                'items' => [
                    ['name' => 'buttons', 'summary' => '控制上传、网络图片、在线图片、删除等入口按钮。'],
                    ['name' => 'actions', 'summary' => '控制缩放、旋转、翻转等裁剪动作按钮。'],
                    ['name' => 'tips', 'summary' => '补充推荐尺寸和裁剪要求。'],
                    ['name' => 'value 回显', 'summary' => '已有图片会自动作为裁剪源进行回显。'],
                ],
            ],
            [
                'title' => '原生配置',
                'items' => [
                    ['name' => 'options', 'summary' => '透传 cropper 原生配置，如裁剪框比例、最小尺寸等。'],
                    ['name' => 'upload.url', 'summary' => '最终上传仍走统一上传驱动和地址解析。'],
                    ['name' => '网络图片', 'summary' => '通过 `url` 按钮可以直接填入网络图片地址进行裁剪。'],
                ],
            ],
            [
                'title' => '选型建议',
                'items' => [
                    ['name' => 'cropper / image', 'summary' => '需要裁剪时用 cropper；只需要上传与预览时用 image。'],
                    ['name' => '固定尺寸', 'summary' => '有明确头像、海报尺寸要求时优先用 cropper。'],
                    ['name' => '编辑态回显', 'summary' => '如果已有封面图需要重新裁切，cropper 比 image 更合适。'],
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
