<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio;

use app\common\render\Form;
use app\common\render\form\items\radio\Radio;

/**
 * radio 默认值与回填能力块
 */
final class RadioDefaultValueSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'default_value'),
            'title' => (string) ($section['title'] ?? '默认值与回填'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'value', 'value' => 'active 表示编辑页默认回显“启用”'],
                ['name' => 'options', 'value' => '建议使用稳定的业务值而不是随机数字'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio',
        'name' => 'user_status',
        'label' => '账号状态',
        'tips' => '编辑用户时回显当前状态',
        'options' => [
            'draft' => '待激活',
            'active' => '启用',
            'locked' => '锁定',
        ],
        'value' => 'active',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radio('user_status', '账号状态', '编辑用户时回显当前状态')
    ->options([
        'draft' => '待激活',
        'active' => '启用',
        'locked' => '锁定',
    ])
    ->value('active');
CODE,
            'notes' => [
                'radio 的 value 通常直接对应数据库中的枚举值，最适合做编辑态回显。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio/RadioDefaultValueSection.php',
                    'label' => '默认值与回填能力块',
                    'description' => '展示 radio 在编辑态场景下如何回显当前选中值。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_section_default_', false), '默认值与回填')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Radio::make('user_status', '账号状态', '编辑用户时回显当前状态')
                    ->options([
                        'draft' => '待激活',
                        'active' => '启用',
                        'locked' => '锁定',
                    ])
                    ->value('active')
            )
            ->fetch();
    }
}
