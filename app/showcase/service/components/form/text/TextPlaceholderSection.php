<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 占位符与提示能力块
 */
final class TextPlaceholderSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'placeholder_tips'),
            'title' => (string) ($section['title'] ?? '占位符与提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'placeholder', 'value' => '提示用户预期输入格式'],
                ['name' => 'tips', 'value' => '在字段下方补充说明'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'email_alias', '邮箱别名', '下方 tips 用于说明命名规范'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('email_alias', '邮箱别名', '下方 tips 用于说明命名规范')
    ->placeholder('例如 openai-cn');
CODE,
            'notes' => [
                'placeholder 负责输入前提示，tips 负责输入后的补充说明。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextPlaceholderSection.php',
                    'label' => '占位提示能力块',
                    'description' => '展示 placeholder 与 tips 的组合方式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_placeholder_', false), '占位符与提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('email_alias', '邮箱别名', '下方 tips 用于说明命名规范')
                    ->placeholder('例如 openai-cn')
            )
            ->item(
                Text::make('slogan', '一句话介绍', '建议控制在 20 个字以内')
                    ->placeholder('例如 把复杂管理后台做轻一点')
            )
            ->fetch();
    }
}
