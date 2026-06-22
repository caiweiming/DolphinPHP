<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;

/**
 * image 默认值与回填能力块
 */
final class ImageDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '支持单图 id 或多图 id 数组回显'],
                ['name' => 'readonly', 'value' => '编辑态回显常和只读预览组合出现'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'poster',
        'label' => '海报图',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('poster', '海报图')
    ->value(0);
CODE,
            'notes' => [
                '组件回显底层会把文件 id 转成附件信息；示例里用 `0` 触发占位图，避免依赖测试库附件数据。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 image 在默认值回显场景下的结构和占位表现。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Image::make('poster', '海报图')->value(0))
            ->fetch();
    }
}
