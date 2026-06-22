<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 扩展参数能力块
 */
final class TextAdvancedPropsSection
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
                ['name' => 'help', 'value' => '在标签右侧展示额外帮助说明'],
                ['name' => 'extra_class', 'value' => '追加字段自定义样式类'],
                ['name' => 'size', 'value' => '控制输入框尺寸，如 sm / lg'],
                ['name' => 'id', 'value' => '自定义 DOM id，便于脚本挂载'],
                ['name' => 'props', 'value' => '透传原生 HTML 属性'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'text',
        'name' => 'api_token',
        'label' => '接口令牌',
        'tips' => '该字段只用于对接内部服务',
        'help' => '用于说明字段来源、格式或接入限制',
        'id' => 'showcase-api-token',
        'size' => 'sm',
        'extra_class' => 'showcase-text--highlight',
        'props' => 'data-scene="showcase" data-role="token"',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('api_token', '接口令牌', '该字段只用于对接内部服务')
    ->id('showcase-api-token')
    ->small()
    ->extraClass('showcase-text--highlight')
    ->props('data-scene="showcase" data-role="token"');
CODE,
            'notes' => [
                'help 更适合数组配置方式，链式 API 更适合 id、尺寸和属性透传这类控制项。',
                'props 可以直接挂 data-* 属性，方便前端脚本读取场景信息。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextAdvancedPropsSection.php',
                    'label' => '扩展参数能力块',
                    'description' => '展示 help、id、size、extra_class、props 等进阶参数。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_advanced_', false), '扩展参数与 DOM 控制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'text',
                'name' => 'api_token',
                'label' => '接口令牌',
                'tips' => '该字段只用于对接内部服务',
                'help' => '用于说明字段来源、格式或接入限制',
                'id' => 'showcase-api-token',
                'size' => 'sm',
                'extra_class' => 'showcase-text--highlight',
                'props' => 'data-scene="showcase" data-role="token"',
                'placeholder' => 'sk-demo-2026',
            ])
            ->fetch();
    }
}
