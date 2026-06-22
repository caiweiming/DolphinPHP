<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 默认值与回填能力块
 */
final class VditorDefaultValueSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => '支持直接回填 Markdown 字符串'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'content',
        'label' => '文档内容',
        'value' => "# DolphinPHP Showcase\n\n- 支持 Markdown 编辑\n- 支持图片上传",
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('content', '文档内容')
    ->value("# DolphinPHP Showcase\n\n- 支持 Markdown 编辑\n- 支持图片上传");
CODE,
            'notes' => [
                '编辑态回显时直接传 Markdown 原文即可，适合知识库条目、博客文章二次编辑。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorDefaultValueSection.php',
                'label' => '默认值与回填能力块',
                'description' => '展示 vditor 在编辑态内容回显场景下的写法。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_value_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Vditor::make('content', '文档内容')
                    ->id('content_default_value')
                    ->value("# DolphinPHP Showcase\n\n- 支持 Markdown 编辑\n- 支持图片上传")
            )
            ->fetch();
    }
}
