<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\textarea;

use app\common\render\Form;
use app\common\render\form\items\textarea\Textarea;

/**
 * textarea 长度限制能力块
 */
final class TextareaLengthSection
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
                ['name' => 'required', 'value' => '标记摘要、说明等必须填写的多行字段'],
                ['name' => 'max_length', 'value' => '控制最大输入字符数'],
                ['name' => 'max()', 'value' => 'maxLength() 的链式别名'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'textarea',
        'name' => 'summary',
        'label' => '摘要',
        'tips' => '最多输入 500 个字符',
        'required' => true,
        'max_length' => 500,
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::textarea('summary', '摘要', '最多输入 500 个字符')
    ->required()
    ->max(500)
    ->placeholder('请概括本次提交的核心内容');
CODE,
            'notes' => [
                '当前 textarea 链式 API 提供 max() 别名，但没有和 text 完全对称的 min() 别名。',
                '长度限制仍然只是前端约束，后端校验规则需要另行配置。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/textarea/TextareaLengthSection.php',
                    'label' => '长度限制能力块',
                    'description' => '展示 required 与 max_length / max() 的组合配置。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_textarea_section_length_', false), '必填与长度限制')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Textarea::make('summary', '摘要', '最多输入 500 个字符')
                    ->required()
                    ->max(500)
                    ->placeholder('请概括本次提交的核心内容')
                    ->rows(5)
            )
            ->fetch();
    }
}
