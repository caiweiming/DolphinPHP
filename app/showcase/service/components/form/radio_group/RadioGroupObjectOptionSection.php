<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\component\avatar\Item as Avatar;
use app\common\component\flag\Item as Flag;
use app\common\component\payment\Item as Payment;
use app\common\render\Form;

/**
 * radio_group 组件对象选项能力块
 */
final class RadioGroupObjectOptionSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'object_option'),
            'title' => (string) ($section['title'] ?? '组件对象选项'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => '可直接传入组件对象的 `->handle()` 结果，适合沉淀统一视觉片段'],
                ['name' => 'Payment / Flag / Avatar', 'value' => '把支付图标、国旗、头像这些通用 UI 片段复用到 radio_group 选项里'],
            ],
            'array_code' => <<<'CODE'
use app\common\component\avatar\Item as Avatar;
use app\common\component\flag\Item as Flag;
use app\common\component\payment\Item as Payment;

[
    [
        'type' => 'radio_group',
        'name' => 'member_profile',
        'label' => '联系人卡片',
        'tips' => 'options 也可以直接使用组件对象生成的渲染结果',
        'options' => [
            'visa' => (new Payment('visa', '卡号 <strong>7998</strong>'))->handle(),
            'cn' => (new Flag('cn', '中国'))->handle(),
            'owner' => (new Avatar('Paweł Kuna', 'UI Designer', '/static/avatars/000m.jpg', 'md'))->handle(),
        ],
        'value' => 'owner',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\component\avatar\Item as Avatar;
use app\common\component\flag\Item as Flag;
use app\common\component\payment\Item as Payment;
use app\common\render\form\Field;

Field::radioGroup('member_profile', '联系人卡片', 'options 也可以直接使用组件对象生成的渲染结果')
    ->options([
        'visa' => (new Payment('visa', '卡号 <strong>7998</strong>'))->handle(),
        'cn' => (new Flag('cn', '中国'))->handle(),
        'owner' => (new Avatar('Paweł Kuna', 'UI Designer', '/static/avatars/000m.jpg', 'md'))->handle(),
    ])
    ->value('owner');
CODE,
            'notes' => [
                '如果项目里已经有支付图标、国旗、头像这类小组件，直接复用对象输出比手写 HTML 更稳定，也更方便统一维护。',
                '这类写法特别适合“支付方式 / 国家地区 / 联系人卡片”之类需要统一视觉片段复用的业务配置页。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupObjectOptionSection.php',
                    'label' => '组件对象选项能力块',
                    'description' => '展示 radio_group 如何复用 Payment、Flag、Avatar 等组件对象作为选项内容。',
                ],
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_object_', false), '组件对象选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'radio_group',
                'name' => 'member_profile',
                'label' => '联系人卡片',
                'tips' => 'options 也可以直接使用组件对象生成的渲染结果',
                'options' => [
                    'visa' => (new Payment('visa', '卡号 <strong>7998</strong>'))->handle(),
                    'cn' => (new Flag('cn', '中国'))->handle(),
                    'owner' => (new Avatar('Paweł Kuna', 'UI Designer', '/static/avatars/000m.jpg', 'md'))->handle(),
                ],
                'value' => 'owner',
            ])
            ->fetch();
    }
}
