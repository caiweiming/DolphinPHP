<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select;

use app\common\render\Form;
use app\common\render\form\items\select\Select;

/**
 * select 占位符提示能力块
 */
final class SelectPlaceholderSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'placeholder'),
            'title' => (string) ($section['title'] ?? '占位符提示'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'placeholder', 'value' => '自定义空选项提示文案'],
                ['name' => 'tips', 'value' => '适合说明应该如何选择该字段'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'product',
        'label' => '商品',
        'tips' => '请先选择要绑定的商品',
        'placeholder' => '请选择商品',
        'options' => [
            'sku-1' => '旗舰版 SaaS 授权',
            'sku-2' => '企业私有化部署',
            'sku-3' => '年度增值服务',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select('product', '商品', '请先选择要绑定的商品')
    ->options([
        'sku-1' => '旗舰版 SaaS 授权',
        'sku-2' => '企业私有化部署',
        'sku-3' => '年度增值服务',
    ])
    ->placeholder('请选择商品');
CODE,
            'notes' => [
                '普通 select 的 placeholder 会渲染为首个空选项文案；显式设置 placeholder 更适合业务字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select/SelectPlaceholderSection.php',
                    'label' => '占位符能力块',
                    'description' => '展示 placeholder 在 select 场景中的典型写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select_section_placeholder_', false), '占位符提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select::make('product', '商品', '请先选择要绑定的商品')
                    ->options([
                        'sku-1' => '旗舰版 SaaS 授权',
                        'sku-2' => '企业私有化部署',
                        'sku-3' => '年度增值服务',
                    ])
                    ->placeholder('请选择商品')
            )
            ->fetch();
    }
}
