<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 固定尺寸提示能力块
 */
final class CropperSizeHintSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'size_hint'),
            'title' => (string) ($section['title'] ?? '固定尺寸提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'tips', 'value' => '明确告诉开发者和运营人员目标尺寸、比例与裁切范围'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'wechat_cover',
        'label' => '公众号封面',
        'tips' => '建议上传 900x383 图片，避免文字贴边',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('wechat_cover', '公众号封面', '建议上传 900x383 图片，避免文字贴边');
CODE,
            'notes' => [
                '很多裁剪组件问题不是功能缺失，而是业务没有把目标尺寸说清楚，所以这类提示示例很必要。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperSizeHintSection.php',
                'label' => '固定尺寸提示能力块',
                'description' => '展示 cropper 如何通过提示文案约束业务裁剪结果。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_hint_', false), '固定尺寸提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('wechat_cover', '公众号封面', '建议上传 900x383 图片，避免文字贴边'))
            ->fetch();
    }
}
