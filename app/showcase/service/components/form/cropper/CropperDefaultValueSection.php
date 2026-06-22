<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 默认值与回填能力块
 */
final class CropperDefaultValueSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '已有图片会自动回显到裁剪预览区'],
                ['name' => 'gallery', 'value' => '回显图片仍可灯箱预览'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'current_avatar',
        'label' => '当前头像',
        'value' => 0,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('current_avatar', '当前头像')
    ->value(0);
CODE,
            'notes' => [
                '示例里用 `0` 触发占位图回显，避免依赖测试环境中的真实附件记录。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperDefaultValueSection.php',
                'label' => '默认值与回填能力块',
                'description' => '展示 cropper 在已有图片回显场景下的表现。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('current_avatar', '当前头像')->value(0))
            ->fetch();
    }
}
