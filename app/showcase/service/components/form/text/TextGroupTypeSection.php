<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text 前后缀分组类型能力块
 */
final class TextGroupTypeSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'group_type'),
            'title' => (string) ($section['title'] ?? '前后缀分组类型'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'group_type', 'value' => 'text / icon / button 三种输入组模式'],
                ['name' => 'prefix/suffix', 'value' => '决定前后缀展示内容'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'text',
        'name' => 'text_website',
        'label' => '站点域名',
        'tips' => '适合文本型前后缀',
        'prefix' => 'https://',
        'suffix' => '.com',
        'group_type' => 'text',
    ],
    [
        'type' => 'text',
        'name' => 'icon_email',
        'label' => '联系邮箱',
        'tips' => '适合图标型前缀',
        'prefix' => '<i class="ti ti-mail"></i>',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('text_website', '站点域名', '适合文本型前后缀')
    ->prefix('https://')
    ->suffix('.com')
    ->groupType('text');

Field::text('icon_email', '联系邮箱', '适合图标型前缀')
    ->prefix('<i class="ti ti-mail"></i>');

Field::text('button_code', '短信验证码', '适合按钮型后缀')
    ->suffix('<button type="button" class="btn btn-primary">发送验证码</button>')
    ->groupType('button');
CODE,
            'notes' => [
                'text 模式更适合协议、单位、域名后缀这类纯文本补充。',
                'icon 和 button 模式分别适合强调语义图标与交互型后缀。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextGroupTypeSection.php',
                    'label' => '分组类型能力块',
                    'description' => '对比 text、icon、button 三种输入组模式。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_group_type_', false), '前后缀分组类型')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('text_website', '站点域名', '适合文本型前后缀')
                    ->prefix('https://')
                    ->suffix('.com')
                    ->groupType('text')
                    ->placeholder('your-brand')
            )
            ->item(
                Text::make('icon_email', '联系邮箱', '适合图标型前缀')
                    ->prefix('<i class="fas fa-envelope"></i>')
                    ->placeholder('team@example.com')
            )
            ->item(
                Text::make('button_code', '短信验证码', '适合按钮型后缀')
                    ->suffix('<button type="button" class="btn btn-primary">发送验证码</button>')
                    ->groupType('button')
                    ->placeholder('输入 6 位验证码')
            )
            ->fetch();
    }
}
