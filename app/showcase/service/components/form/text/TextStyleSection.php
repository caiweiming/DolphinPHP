<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 样式变体能力块
 */
final class TextStyleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'style'),
            'title' => (string) ($section['title'] ?? '样式变体'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'rounded', 'value' => '更柔和的搜索框视觉'],
                ['name' => 'flush', 'value' => '贴线式输入布局'],
                ['name' => 'float', 'value' => '浮动标签样式'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'rounded_search', '圆角搜索', '用于首页搜索框'],
    ['text', 'flush_title', '扁平标题', '用于紧凑编辑场景'],
    ['text', 'float_username', '浮动用户名', '用于现代登录页'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('rounded_search', '圆角搜索', '用于首页搜索框')->rounded();
Field::text('flush_title', '扁平标题', '用于紧凑编辑场景')->flush();
Field::text('float_username', '浮动用户名', '用于现代登录页')->float();
CODE,
            'notes' => [
                '同一 text 组件可以在不同页面气质下切换不同视觉风格。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextStyleSection.php',
                    'label' => '样式变体能力块',
                    'description' => '展示 rounded、flush、float 三种常见变体。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_style_', false), '样式变体')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('rounded_search', '圆角搜索', '用于首页搜索框')
                    ->rounded()
                    ->placeholder('搜索组件、页面或文档')
            )
            ->item(
                Text::make('flush_title', '扁平标题', '用于紧凑编辑场景')
                    ->flush()
                    ->placeholder('请输入标题')
            )
            ->item(
                Text::make('float_username', '浮动用户名', '用于现代登录页')
                    ->float()
                    ->placeholder('请输入用户名')
            )
            ->fetch();
    }
}
