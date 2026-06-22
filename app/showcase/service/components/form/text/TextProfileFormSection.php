<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 业务综合示例能力块
 */
final class TextProfileFormSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务综合示例'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'required + placeholder', 'value' => '用户名、邮箱等基础输入场景'],
                ['name' => 'prefix + group_type', 'value' => '域名、账号前缀等带上下文输入'],
                ['name' => 'max_length', 'value' => '简介、别名等长度控制场景'],
            ],
            'array_code' => <<<'CODE'
[
    ['text:*', 'profile_username', '用户名', '登录账号，3 到 20 个字符'],
    ['text:*', 'profile_email', '联系邮箱', '用于接收通知与协作邀请'],
    ['text', 'profile_website', '个人站点', '用于公开展示的个人主页'],
    ['text', 'profile_slogan', '一句话介绍', '建议控制在 30 个字以内'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('profile_username', '用户名', '登录账号，3 到 20 个字符')
    ->required()
    ->minLength(3)
    ->maxLength(20)
    ->placeholder('例如 caiweiming');

Field::text('profile_email', '联系邮箱', '用于接收通知与协作邀请')
    ->required()
    ->prefix('<i class="ti ti-mail"></i>');
CODE,
            'notes' => [
                '综合示例更接近真实后台表单，适合直接照搬后再按业务字段改名。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextProfileFormSection.php',
                    'label' => '业务综合示例能力块',
                    'description' => '把常用 text 组合成真实业务表单片段。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_profile_', false), '业务综合示例')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('profile_username', '用户名', '登录账号，3 到 20 个字符')
                    ->required()
                    ->minLength(3)
                    ->maxLength(20)
                    ->placeholder('例如 caiweiming')
            )
            ->item(
                Text::make('profile_email', '联系邮箱', '用于接收通知与协作邀请')
                    ->required()
                    ->prefix('<i class="ti ti-mail"></i>')
                    ->placeholder('例如 team@example.com')
            )
            ->item(
                Text::make('profile_website', '个人站点', '用于公开展示的个人主页')
                    ->prefix('https://')
                    ->suffix('.com')
                    ->groupType('text')
                    ->placeholder('my-space')
            )
            ->item(
                Text::make('profile_slogan', '一句话介绍', '建议控制在 30 个字以内')
                    ->maxLength(30)
                    ->placeholder('例如 把复杂后台做得更轻一点')
            )
            ->fetch();
    }
}
