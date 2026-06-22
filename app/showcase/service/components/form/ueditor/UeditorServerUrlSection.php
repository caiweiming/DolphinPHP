<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 自定义 serverUrl 能力块
 */
final class UeditorServerUrlSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'server_url'),
            'title' => (string) ($section['title'] ?? '自定义 serverUrl'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.serverUrl', 'value' => '用于改写编辑器配置接口或代理路由'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'content',
        'label' => '内容',
        'options' => [
            'serverUrl' => '/_form/ueditor/config?action=config&scene=article',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('content', '内容')
    ->options([
        'serverUrl' => '/_form/ueditor/config?action=config&scene=article',
    ]);
CODE,
            'notes' => [
                '当你需要走统一网关、加业务参数或做上传代理时，自定义 serverUrl 很有价值。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorServerUrlSection.php',
                'label' => '自定义 serverUrl 能力块',
                'description' => '展示 ueditor 如何通过 options.serverUrl 改写配置接口地址。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_server_', false), '自定义 serverUrl')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Ueditor::make('content', '内容')
                    ->id('content_server_url')
                    ->options([
                        'serverUrl' => '/_form/ueditor/config?action=config&scene=article',
                    ])
            )
            ->fetch();
    }
}
