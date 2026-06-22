<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 原生 options 配置能力块
 */
final class VditorNativeOptionsSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options.height / counter', 'value' => '控制编辑高度和字数统计'],
                ['name' => 'options.upload.extraData', 'value' => '为上传请求附加业务参数'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'guide',
        'label' => '操作指南',
        'options' => [
            'height' => 480,
            'counter' => ['enable' => true],
            'upload' => [
                'extraData' => ['scene' => 'guide'],
            ],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('guide', '操作指南')
    ->options([
        'height' => 480,
        'counter' => ['enable' => true],
        'upload' => [
            'extraData' => ['scene' => 'guide'],
        ],
    ]);
CODE,
            'notes' => [
                'vditor 的高级调优也主要依赖 options 透传，上传额外参数尤其适合多租户或多业务场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorNativeOptionsSection.php',
                'label' => '原生 options 配置能力块',
                'description' => '展示 vditor 如何透传高度、计数器与上传扩展参数。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_options_', false), '原生 options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Vditor::make('guide', '操作指南')
                    ->options([
                        'height' => 480,
                        'counter' => ['enable' => true],
                        'upload' => [
                            'extraData' => ['scene' => 'guide'],
                        ],
                    ])
            )
            ->fetch();
    }
}
