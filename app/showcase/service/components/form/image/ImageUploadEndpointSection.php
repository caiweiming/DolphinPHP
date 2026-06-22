<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\image;

use app\common\render\Form;

/**
 * image 上传地址与附加参数能力块
 */
final class ImageUploadEndpointSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'upload_endpoint'),
            'title' => (string) ($section['title'] ?? '上传地址与附加参数'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.url', 'value' => '自定义上传地址；未传时默认走框架上传接口'],
                ['name' => 'options.extraData', 'value' => '为上传请求补充业务参数，如场景、目录上下文、关联模型'],
                ['name' => 'options.autoUpload', 'value' => '控制选择文件后立即上传还是等待手动触发'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'image',
        'name' => 'activity_cover',
        'label' => '活动封面',
        'tips' => '演示自定义上传地址与附加参数',
        'options' => [
            'url' => (string) dp_url('showcase/admin.demo_api/upload'),
            'extraData' => [
                '_ajax' => 1,
                '_from' => 'image',
                'upload_context' => 'activity-cover',
                'scene' => 'marketing',
            ],
            'autoUpload' => true,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::image('activity_cover', '活动封面', '演示自定义上传地址与附加参数')
    ->options([
        'url' => (string) dp_url('showcase/admin.demo_api/upload'),
        'extraData' => [
            '_ajax' => 1,
            '_from' => 'image',
            'upload_context' => 'activity-cover',
            'scene' => 'marketing',
        ],
        'autoUpload' => true,
    ]);
CODE,
            'notes' => [
                '当上传接口需要区分业务场景时，最直接的做法就是在 `options.url` 和 `options.extraData` 里把上下文写清楚。',
                '如果你要对接自定义上传网关，建议先照抄这个结构，再替换成真实接口地址和附加参数。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/image/ImageUploadEndpointSection.php',
                    'label' => '上传地址与附加参数能力块',
                    'description' => '展示 image 如何指定自定义上传地址、附加参数和自动上传策略。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_image_section_endpoint_', false), '上传地址与附加参数')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'image',
                'name' => 'activity_cover',
                'label' => '活动封面',
                'tips' => '演示自定义上传地址与附加参数',
                'options' => [
                    'url' => (string) dp_url('showcase/admin.demo_api/upload'),
                    'extraData' => [
                        '_ajax' => 1,
                        '_from' => 'image',
                        'upload_context' => 'activity-cover',
                        'scene' => 'marketing',
                    ],
                    'autoUpload' => true,
                ],
            ])
            ->fetch();
    }
}
