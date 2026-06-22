<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\switch;

use app\common\render\Form;
use app\common\render\form\items\switch\Toggle;

/**
 * switch 禁用状态能力块
 */
final class SwitchDisabledSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'disabled'),
            'title' => (string) ($section['title'] ?? '禁用状态'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'disabled', 'value' => '禁用交互，保留当前状态展示'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'switch',
        'name' => 'locked_status',
        'label' => '锁定状态',
        'value' => 1,
        'disabled' => true,
        'tips' => '系统用户不可修改该状态',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::switch('locked_status', '锁定状态', '系统用户不可修改该状态')
    ->value(1)
    ->disabled(true);
CODE,
            'notes' => [
                '禁用态很适合“编辑自己账号时不能修改状态”这类高风险业务场景。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/switch/SwitchDisabledSection.php',
                    'label' => '禁用状态能力块',
                    'description' => '展示 disabled 对开关交互的影响。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_switch_section_disabled_', false), '禁用状态')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Toggle::make('locked_status', '锁定状态', '系统用户不可修改该状态')
                    ->value(1)
                    ->disabled(true)
            )
            ->fetch();
    }
}
