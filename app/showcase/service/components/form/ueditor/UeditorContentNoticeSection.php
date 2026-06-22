<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\ueditor;

use app\common\render\Form;
use app\common\render\form\items\ueditor\Ueditor;

/**
 * ueditor 内容安全提示能力块
 */
final class UeditorContentNoticeSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'content_notice'),
            'title' => (string) ($section['title'] ?? '内容安全提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'HTML 过滤', 'value' => '建议入库前做白名单清洗或安全过滤'],
                ['name' => '字段类型', 'value' => '正文字段建议使用 LONGTEXT 等长文本类型'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'ueditor',
        'name' => 'safe_content',
        'label' => '正文内容',
        'value' => '<p>请在提交前确认内容已完成安全过滤。</p><ul><li>入库前建议执行 dp_clean()</li><li>正文字段建议使用 LONGTEXT</li><li>上传目录建议按业务模块隔离</li></ul>',
        'driver' => 'local',
        'dir' => 'showcase/ueditor',
        'tips' => 'HTML 富文本入库前建议做白名单清洗或安全过滤。',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::ueditor('safe_content', '正文内容')
    ->value('<p>请在提交前确认内容已完成安全过滤。</p><ul><li>入库前建议执行 dp_clean()</li><li>正文字段建议使用 LONGTEXT</li><li>上传目录建议按业务模块隔离</li></ul>')
    ->driver('local')
    ->dir('showcase/ueditor')
    ->tips('HTML 富文本入库前建议做白名单清洗或安全过滤。');
CODE,
            'notes' => [
                '这个板块把过滤、字段类型和上传目录建议集中放在真实编辑器旁边，便于边看边抄。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/ueditor/UeditorContentNoticeSection.php',
                'label' => '内容安全提示能力块',
                'description' => '通过只读说明块补充 ueditor 在实际落地中的安全与存储注意事项。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_ueditor_section_notice_', false), '内容安全提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Ueditor::make('safe_content', '正文内容')
                    ->value('<p>请在提交前确认内容已完成安全过滤。</p><ul><li>入库前建议执行 dp_clean()</li><li>正文字段建议使用 LONGTEXT</li><li>上传目录建议按业务模块隔离</li></ul>')
                    ->driver('local')
                    ->dir('showcase/ueditor')
                    ->tips('HTML 富文本入库前建议做白名单清洗或安全过滤。')
            )
            ->fetch();
    }
}
