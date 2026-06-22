<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\radio_group;

use app\common\render\Form;
use app\common\render\form\items\radio_group\RadioGroup;

/**
 * radio_group 套餐型展示能力块
 */
final class RadioGroupPricingPlanSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'pricing_plan'),
            'title' => (string) ($section['title'] ?? '套餐型展示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'options', 'value' => '适合写成“版本名 + 价格 + 权益描述”的组合卡片'],
                ['name' => 'value', 'value' => 'billing_plan 当前默认选中 team，模拟后台推荐版本'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'radio_group',
        'name' => 'billing_plan',
        'label' => '计费套餐',
        'tips' => '选择后将影响坐席数量、存储空间和自动化额度',
        'options' => [
            'starter' => '<div class="fw-semibold">起步版</div><div class="text-primary fs-3 mt-1">¥99/月</div><div class="text-secondary small mt-2">3 个成员，10GB 存储</div>',
            'team' => '<div class="fw-semibold">团队版</div><div class="text-primary fs-3 mt-1">¥299/月</div><div class="text-secondary small mt-2">20 个成员，100GB 存储</div>',
            'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-primary fs-3 mt-1">¥899/月</div><div class="text-secondary small mt-2">不限成员，专属支持</div>',
        ],
        'value' => 'team',
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::radioGroup('billing_plan', '计费套餐', '选择后将影响坐席数量、存储空间和自动化额度')
    ->options([
        'starter' => '<div class="fw-semibold">起步版</div><div class="text-primary fs-3 mt-1">¥99/月</div><div class="text-secondary small mt-2">3 个成员，10GB 存储</div>',
        'team' => '<div class="fw-semibold">团队版</div><div class="text-primary fs-3 mt-1">¥299/月</div><div class="text-secondary small mt-2">20 个成员，100GB 存储</div>',
        'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-primary fs-3 mt-1">¥899/月</div><div class="text-secondary small mt-2">不限成员，专属支持</div>',
    ])
    ->value('team');
CODE,
            'notes' => [
                '套餐型展示是 radio_group 最典型的使用场景，开发者能直接照着卡片结构复用到计费页。',
                '建议把价格、核心权益和推荐版本一起放进卡片内容里，开发者一眼就能判断该用标准版、团队版还是企业版的表达方式。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/radio_group/RadioGroupPricingPlanSection.php',
                    'label' => '套餐型展示能力块',
                    'description' => '展示 radio_group 在套餐、版本、价格档位场景中的典型写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_radio_group_section_plan_', false), '套餐型展示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                RadioGroup::make('billing_plan', '计费套餐', '选择后将影响坐席数量、存储空间和自动化额度')
                    ->options([
                        'starter' => '<div class="fw-semibold">起步版</div><div class="text-primary fs-3 mt-1">¥99/月</div><div class="text-secondary small mt-2">3 个成员，10GB 存储</div>',
                        'team' => '<div class="fw-semibold">团队版</div><div class="text-primary fs-3 mt-1">¥299/月</div><div class="text-secondary small mt-2">20 个成员，100GB 存储</div>',
                        'enterprise' => '<div class="fw-semibold">企业版</div><div class="text-primary fs-3 mt-1">¥899/月</div><div class="text-secondary small mt-2">不限成员，专属支持</div>',
                    ])
                    ->value('team')
            )
            ->fetch();
    }
}
