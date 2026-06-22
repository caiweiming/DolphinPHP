<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 占位符提示能力块
 */
final class Select2PlaceholderSection
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
                ['name' => 'placeholder', 'value' => '自定义搜索型下拉的空态提示文案'],
                ['name' => 'tips', 'value' => '适合告诉开发者这是搜索型还是静态型选择字段'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'product',
        'label' => '商品',
        'tips' => '支持搜索的增强下拉选择',
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

Field::select2('product', '商品', '支持搜索的增强下拉选择')
    ->options([
        'sku-1' => '旗舰版 SaaS 授权',
        'sku-2' => '企业私有化部署',
        'sku-3' => '年度增值服务',
    ])
    ->placeholder('请选择商品');
CODE,
            'notes' => [
                '对 select2 来说，placeholder 比普通 select 更重要，因为它直接影响搜索框的空态提示。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2PlaceholderSection.php',
                    'label' => '占位符能力块',
                    'description' => '展示 placeholder 在 select2 场景中的典型写法。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_placeholder_', false), '占位符提示')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('product', '商品', '支持搜索的增强下拉选择')
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
