<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 前后缀能力块
 */
final class TextPrefixSuffixSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'prefix_suffix'),
            'title' => (string) ($section['title'] ?? '前缀与后缀'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'prefix', 'value' => '输入框前的文本、图标或按钮'],
                ['name' => 'suffix', 'value' => '输入框后的补充内容'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'website_url', '官网地址', '支持带协议的完整域名'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('website_url', '官网地址', '支持带协议的完整域名')
    ->prefix('https://')
    ->suffix('.com');
CODE,
            'notes' => [
                '前后缀最适合域名、金额、搜索框等带上下文约束的输入。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextPrefixSuffixSection.php',
                    'label' => '前后缀能力块',
                    'description' => '展示 prefix 与 suffix 的输入组合。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_prefix_suffix_', false), '前缀与后缀')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('website_url', '官网地址', '支持带协议的完整域名')
                    ->prefix('https://')
                    ->suffix('.com')
                    ->placeholder('your-brand')
            )
            ->item(
                Text::make('website_slug', '站点别名', '用于二级域名或短链标识')
                    ->prefix('site/')
                    ->suffix('preview')
                    ->placeholder('docs')
            )
            ->fetch();
    }
}
