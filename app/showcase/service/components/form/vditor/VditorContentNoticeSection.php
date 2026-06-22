<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\vditor;

use app\common\render\Form;
use app\common\render\form\items\vditor\Vditor;

/**
 * vditor 内容安全提示能力块
 */
final class VditorContentNoticeSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'content_notice'),
            'title' => (string) ($section['title'] ?? '内容安全提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'Markdown 入库', 'value' => '建议保留源文本，并在输出渲染前统一过滤'],
                ['name' => '上传资源', 'value' => '编辑器图片仍建议按业务目录治理，避免资源散乱'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'vditor',
        'name' => 'markdown_content',
        'label' => 'Markdown 内容',
        'value' => "## 安全建议\n\n- 建议保留 Markdown 原文\n- 渲染输出前建议做 HTML 白名单过滤\n- 图片上传目录建议按业务模块分组",
        'driver' => 'local',
        'dir' => 'showcase/vditor',
        'tips' => 'Markdown 看起来更安全，但渲染输出仍可能进入 HTML。',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::vditor('markdown_content', 'Markdown 内容')
    ->value("## 安全建议\n\n- 建议保留 Markdown 原文\n- 渲染输出前建议做 HTML 白名单过滤\n- 图片上传目录建议按业务模块分组")
    ->driver('local')
    ->dir('showcase/vditor')
    ->tips('Markdown 看起来更安全，但渲染输出仍可能进入 HTML。');
CODE,
            'notes' => [
                '这个板块把原文保留、渲染过滤和资源治理建议集中放在真实编辑器旁边，便于边看边抄。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/vditor/VditorContentNoticeSection.php',
                'label' => '内容安全提示能力块',
                'description' => '通过只读说明块补充 vditor 在实际落地中的安全与资源治理注意事项。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_vditor_section_notice_', false), '内容安全提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Vditor::make('markdown_content', 'Markdown 内容')
                    ->value("## 安全建议\n\n- 建议保留 Markdown 原文\n- 渲染输出前建议做 HTML 白名单过滤\n- 图片上传目录建议按业务模块分组")
                    ->driver('local')
                    ->dir('showcase/vditor')
                    ->tips('Markdown 看起来更安全，但渲染输出仍可能进入 HTML。')
            )
            ->fetch();
    }
}
