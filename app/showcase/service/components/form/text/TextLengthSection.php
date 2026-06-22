<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 长度限制能力块
 */
final class TextLengthSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'length_limit'),
            'title' => (string) ($section['title'] ?? '必填与长度限制'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'required', 'value' => '标记业务必填字段'],
                ['name' => 'minLength', 'value' => '控制最小长度'],
                ['name' => 'maxLength', 'value' => '控制最大长度'],
            ],
            'array_code' => <<<'CODE'
[
    ['text:*', 'product_code', '产品编码', '至少 6 个字符，最多 12 个字符'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('product_code', '产品编码', '至少 6 个字符，最多 12 个字符')
    ->required()
    ->minLength(6)
    ->maxLength(12);
CODE,
            'notes' => [
                '长度限制适合编码、短链标识、别名等强约束输入场景。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextLengthSection.php',
                    'label' => '长度限制能力块',
                    'description' => '展示必填与长度限制的组合配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_length_', false), '必填与长度限制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('product_code', '产品编码', '至少 6 个字符，最多 12 个字符')
                    ->required()
                    ->minLength(6)
                    ->maxLength(12)
                    ->placeholder('例如 PRO2026')
            )
            ->fetch();
    }
}
