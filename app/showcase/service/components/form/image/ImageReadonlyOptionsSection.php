<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;
use app\common\render\form\items\image\Image;

/**
 * image 只读与原生配置能力块
 */
final class ImageReadonlyOptionsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'readonly_options'),
            'title' => (string) ($section['title'] ?? '只读与原生配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'readonly', 'value' => '切换为只读预览模式，自动关闭上传/删除交互'],
                ['name' => 'options.autoUpload', 'value' => '控制选择图片后是否立即上传'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'readonly_cover',
        'label' => '只读封面',
        'readonly' => true,
        'value' => 0,
        'options' => [
            'autoUpload' => false,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('readonly_cover', '只读封面')
    ->value(0)
    ->readonly()
    ->options([
        'autoUpload' => false,
    ]);
CODE,
            'notes' => [
                '只读模式经常出现在详情页或审批页，这时组件更像是带灯箱预览的图片展示器。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageReadonlyOptionsSection.php',
                    'label' => '只读与原生配置能力块',
                    'description' => '展示 image 在只读场景和原生上传策略配置下的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_readonly_', false), '只读与原生配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Image::make('readonly_cover', '只读封面')
                    ->value(0)
                    ->readonly()
                    ->options([
                        'autoUpload' => false,
                    ])
            )
            ->fetch();
    }
}
