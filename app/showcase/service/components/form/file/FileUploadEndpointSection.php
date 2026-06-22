<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\file;

use app\common\render\Form;

/**
 * file 上传地址与附加参数能力块
 */
final class FileUploadEndpointSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'upload_endpoint'),
            'title' => (string) ($section['title'] ?? '上传地址与附加参数'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.url', 'value' => '自定义上传地址；未传时默认走框架上传接口'],
                ['name' => 'options.extraData', 'value' => '为上传请求补充业务参数，如模型类型、场景标识、租户信息'],
                ['name' => 'options.autoUpload', 'value' => '控制选择附件后立即上传还是等待手动触发'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'file',
        'name' => 'contract_archive',
        'label' => '归档附件',
        'tips' => '演示自定义上传地址与附加参数',
        'options' => [
            'url' => (string) dp_url('showcase/admin.demo_api/upload'),
            'extraData' => [
                '_ajax' => 1,
                '_from' => 'file',
                'upload_context' => 'contract-archive',
                'scene' => 'archive',
            ],
            'autoUpload' => true,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::file('contract_archive', '归档附件', '演示自定义上传地址与附加参数')
    ->options([
        'url' => (string) dp_url('showcase/admin.demo_api/upload'),
        'extraData' => [
            '_ajax' => 1,
            '_from' => 'file',
            'upload_context' => 'contract-archive',
            'scene' => 'archive',
        ],
        'autoUpload' => true,
    ]);
CODE,
            'notes' => [
                '当附件上传需要对接业务网关或归档服务时，最直接的入口就是 `options.url` 和 `options.extraData`。',
                '如果你要接第三方存储中台，这个示例可以直接作为最小骨架照抄。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/file/FileUploadEndpointSection.php',
                'label' => '上传地址与附加参数能力块',
                'description' => '展示 file 如何指定自定义上传地址、附加参数和自动上传策略。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_file_section_endpoint_', false), '上传地址与附加参数')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'file',
                'name' => 'contract_archive',
                'label' => '归档附件',
                'tips' => '演示自定义上传地址与附加参数',
                'options' => [
                    'url' => (string) dp_url('showcase/admin.demo_api/upload'),
                    'extraData' => [
                        '_ajax' => 1,
                        '_from' => 'file',
                        'upload_context' => 'contract-archive',
                        'scene' => 'archive',
                    ],
                    'autoUpload' => true,
                ],
            ])
            ->fetch();
    }
}
