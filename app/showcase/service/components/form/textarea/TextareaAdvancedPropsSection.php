<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;

/**
 * textarea 扩展参数能力块
 */
final class TextareaAdvancedPropsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'advanced_props'),
            'title' => (string) ($section['title'] ?? '扩展参数与 DOM 控制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'help', 'value' => '在标签右侧补充帮助说明'],
                ['name' => 'id', 'value' => '自定义 DOM id，便于挂脚本'],
                ['name' => 'size', 'value' => '控制字段尺寸，如 sm / lg'],
                ['name' => 'class', 'value' => '追加 textarea 样式类'],
                ['name' => 'props', 'value' => '透传 data-* 等原生属性'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'api_remark',
        'label' => '接口备注',
        'tips' => '用于记录对接限制或回调约束',
        'help' => '通常由接口负责人补充，供后续排查时快速定位上下文',
        'id' => 'showcase-textarea-api-remark',
        'size' => 'sm',
        'class' => 'showcase-textarea--highlight',
        'props' => 'data-scene="textarea" data-role="remark"',
        'rows' => 5,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('api_remark', '接口备注', '用于记录对接限制或回调约束')
    ->id('showcase-textarea-api-remark')
    ->small()
    ->class('showcase-textarea--highlight')
    ->props('data-scene="textarea" data-role="remark"')
    ->rows(5);
CODE,
            'notes' => [
                'help 更适合数组配置，因为当前 textarea 链式 API 没有单独的 help() 方法。',
                'class() 是 textarea 当前可用的样式扩展方式，和 text 页里的 extraClass() 不同。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaAdvancedPropsSection.php',
                    'label' => '扩展参数能力块',
                    'description' => '展示 help、id、size、class、props 等进阶参数。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_advanced_', false), '扩展参数与 DOM 控制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'textarea',
                'name' => 'api_remark',
                'label' => '接口备注',
                'tips' => '用于记录对接限制或回调约束',
                'help' => '通常由接口负责人补充，供后续排查时快速定位上下文',
                'id' => 'showcase-textarea-api-remark',
                'size' => 'sm',
                'class' => 'showcase-textarea--highlight',
                'props' => 'data-scene="textarea" data-role="remark"',
                'rows' => 5,
                'placeholder' => '例如 仅支持内网回调，需提前配置白名单',
            ])
            ->fetch();
    }
}
