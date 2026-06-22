<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;

/**
 * image 上传约束能力块
 */
final class ImageConstraintSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'constraint'),
            'title' => (string) ($section['title'] ?? '上传约束'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.allowedTypes', 'value' => '限制允许上传的图片格式'],
                ['name' => 'options.maxFileSize', 'value' => '限制单张图片大小，支持带单位字符串'],
                ['name' => 'options.extraData', 'value' => '给上传接口追加业务参数'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'strict_cover',
        'label' => '受限封面',
        'tips' => '仅允许 jpg/png，单张不超过 2MB',
        'options' => [
            'allowedTypes' => ['image/jpeg', 'image/png'],
            'maxFileSize' => '2MB',
            'extraData' => ['scene' => 'cover'],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('strict_cover', '受限封面', '仅允许 jpg/png，单张不超过 2MB')
    ->options([
        'allowedTypes' => ['image/jpeg', 'image/png'],
        'maxFileSize' => '2MB',
        'extraData' => ['scene' => 'cover'],
    ]);
CODE,
            'notes' => [
                '上传约束最好直接写在示例中，开发者才能一眼看出“限制类型/大小”应该配在哪。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageConstraintSection.php',
                    'label' => '上传约束能力块',
                    'description' => '展示 image 组件如何限制图片类型、大小和上传附加参数。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_constraint_', false), '上传约束')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image',
                'name' => 'strict_cover',
                'label' => '受限封面',
                'tips' => '仅允许 jpg/png，单张不超过 2MB',
                'options' => [
                    'allowedTypes' => ['image/jpeg', 'image/png'],
                    'maxFileSize' => '2MB',
                    'extraData' => ['scene' => 'cover'],
                ],
            ])
            ->fetch();
    }
}
