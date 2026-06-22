<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 富文案选项能力块
 */
final class RadioGroupRichOptionSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'rich_option'),
            'title' => (string) ($section['title'] ?? '富文案选项'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => 'value 可直接写 HTML，从而承载图标、标识、标题、副标题和补充说明'],
                ['name' => 'tips', 'value' => '适合支付方式、国家、风格模板等需要视觉识别的选项'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'theme_style',
        'label' => '主题风格',
        'tips' => '不同风格会影响首页视觉和品牌调性，也可以像支付方式、国家一样写成视觉化卡片',
        'options' => [
            'minimal' => '<div class="d-flex align-items-center gap-2"><span class="flag flag-country-jp flag-xs"></span><div><div class="fw-semibold">极简风</div><div class="text-secondary small">适合后台、工具类产品</div></div></div>',
            'brand' => '<div class="d-flex align-items-center gap-2"><span class="payment payment-provider-visa payment-xs"></span><div><div class="fw-semibold">品牌风</div><div class="text-secondary small">强化品牌识别与营销氛围</div></div></div>',
            'editorial' => '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-xs">ED</span><div><div class="fw-semibold">内容风</div><div class="text-secondary small">突出文章与图文阅读体验</div></div></div>',
        ],
        'value' => 'brand',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('theme_style', '主题风格', '不同风格会影响首页视觉和品牌调性，也可以像支付方式、国家一样写成视觉化卡片')
    ->options([
        'minimal' => '<div class="d-flex align-items-center gap-2"><span class="flag flag-country-jp flag-xs"></span><div><div class="fw-semibold">极简风</div><div class="text-secondary small">适合后台、工具类产品</div></div></div>',
        'brand' => '<div class="d-flex align-items-center gap-2"><span class="payment payment-provider-visa payment-xs"></span><div><div class="fw-semibold">品牌风</div><div class="text-secondary small">强化品牌识别与营销氛围</div></div></div>',
        'editorial' => '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-xs">ED</span><div><div class="fw-semibold">内容风</div><div class="text-secondary small">突出文章与图文阅读体验</div></div></div>',
    ])
    ->value('brand');
CODE,
            'notes' => [
                'radio_group 最大的优势就是可以把选项从单行文案提升为视觉化卡片，既可表达差异点，也可强化识别度。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupRichOptionSection.php',
                    'label' => '富文案选项能力块',
                    'description' => '展示 radio_group 如何利用卡片布局承载更丰富的选项内容。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_rich_', false), '富文案选项')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('theme_style', '主题风格', '不同风格会影响首页视觉和品牌调性，也可以像支付方式、国家一样写成视觉化卡片')
                    ->options([
                        'minimal' => '<div class="d-flex align-items-center gap-2"><span class="flag flag-country-jp flag-xs"></span><div><div class="fw-semibold">极简风</div><div class="text-secondary small">适合后台、工具类产品</div></div></div>',
                        'brand' => '<div class="d-flex align-items-center gap-2"><span class="payment payment-provider-visa payment-xs"></span><div><div class="fw-semibold">品牌风</div><div class="text-secondary small">强化品牌识别与营销氛围</div></div></div>',
                        'editorial' => '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-xs">ED</span><div><div class="fw-semibold">内容风</div><div class="text-secondary small">突出文章与图文阅读体验</div></div></div>',
                    ])
                    ->value('brand')
            )
            ->fetch();
    }
}
