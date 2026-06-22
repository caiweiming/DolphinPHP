<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 默认值与回填能力块
 */
final class UeditorDefaultValueSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '支持直接回填 HTML 内容字符串'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'content',
        'label' => '文章内容',
        'value' => '<p><strong>默认导语：</strong>欢迎使用 DolphinPHP Showcase。</p>',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('content', '文章内容')
    ->value('<p><strong>默认导语：</strong>欢迎使用 DolphinPHP Showcase。</p>');
CODE,
            'notes' => [
                '编辑态回显时直接传 HTML 字符串即可，适合文章编辑、帮助中心修改等场景。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorDefaultValueSection.php',
                'label' => '默认值与回填能力块',
                'description' => '展示 ueditor 在编辑态内容回显场景下的写法。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Ueditor::make('content', '文章内容')
                    ->id('content_default_value')
                    ->value('<p><strong>默认导语：</strong>欢迎使用 DolphinPHP Showcase。</p>')
            )
            ->fetch();
    }
}
