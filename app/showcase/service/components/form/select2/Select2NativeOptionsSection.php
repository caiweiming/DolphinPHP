<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;

/**
 * select2 原生配置能力块
 */
final class Select2NativeOptionsSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'native_options'),
            'title' => (string) ($section['title'] ?? '原生 _options 配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => '_options', 'value' => '透传 Select2 原生参数，如 `minimumResultsForSearch`、`theme`、`width`'],
                ['name' => 'data-options', 'value' => '最终会渲染到 `data-options` 属性，供前端初始化时消费'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'scene_id',
        'label' => '业务场景',
        'tips' => '演示 Select2 原生配置透传',
        'options' => [
            'approval' => '审批流',
            'crm' => '客户管理',
            'ops' => '运营投放',
        ],
        '_options' => [
            'minimumResultsForSearch' => 10,
            'width' => '100%',
            'theme' => 'bootstrap-5',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('scene_id', '业务场景', '演示 Select2 原生配置透传')
    ->options([
        'approval' => '审批流',
        'crm' => '客户管理',
        'ops' => '运营投放',
    ])
    ->attr('_options', [
        'minimumResultsForSearch' => 10,
        'width' => '100%',
        'theme' => 'bootstrap-5',
    ]);
CODE,
            'notes' => [
                '当前 select2 链式 API 没有单独的 nativeOptions() 方法，但可以通过 `->attr(\'_options\', [...])` 直接写入底层配置。',
                '如果你更偏好声明式配置，数组写法仍然是最直观的选择。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2NativeOptionsSection.php',
                    'label' => '原生配置能力块',
                    'description' => '展示 select2 如何透传原生 `_options` 参数。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_native_', false), '原生 _options 配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select2',
                'name' => 'scene_id',
                'label' => '业务场景',
                'tips' => '演示 Select2 原生配置透传',
                'props' => 'data-scene="native-options"',
                'options' => [
                    'approval' => '审批流',
                    'crm' => '客户管理',
                    'ops' => '运营投放',
                ],
                '_options' => [
                    'minimumResultsForSearch' => 10,
                    'width' => '100%',
                    'theme' => 'bootstrap-5',
                ],
            ])
            ->fetch();
    }
}
