<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;

/**
 * select2 自定义 ajax URL 能力块
 */
final class Select2AjaxCustomSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'ajax_custom'),
            'title' => (string) ($section['title'] ?? '自定义 ajax URL'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'ajax', 'value' => '也可以直接传字符串 URL，适合接入自定义搜索接口'],
                ['name' => '远程搜索', 'value' => '常见于用户、商品、客户等需要跨业务接口取数的选择字段'],
                ['name' => '返回结构', 'value' => '自定义接口至少要返回 Select2 可识别的 `id / text` 结构'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'uid2',
        'label' => '用户 自定义ajax url',
        'tips' => '选择用户',
        'ajax' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('uid2', '用户 自定义ajax url', '选择用户')
    ->ajax((string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']))
    ->placeholder('请输入关键字搜索数据');
CODE,
            'notes' => [
                '字符串 URL 模式更适合完全自定义的数据源，但前后端都需要遵守 Select2 期望的数据结构。',
                '如果你已经有现成搜索接口，最省事的做法就是先按这个示例接入，再把返回字段统一成 `id / text`。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2AjaxCustomSection.php',
                    'label' => '自定义 ajax URL 能力块',
                    'description' => '展示 select2 如何直接对接自定义远程接口。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_ajax_custom_', false), '自定义 ajax URL')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select2',
                'name' => 'uid2',
                'label' => '用户 自定义ajax url',
                'tips' => '选择用户',
                'placeholder' => '请输入关键字搜索数据',
                'ajax' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
            ])
            ->fetch();
    }
}
