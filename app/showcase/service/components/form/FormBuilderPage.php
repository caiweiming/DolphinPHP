<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

use app\showcase\service\registry\ShowcaseFormBuilderMethodCatalog;

/**
 * Showcase 表单构建器页面组装器
 */
final class FormBuilderPage
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $groups = ShowcaseFormBuilderMethodCatalog::groups();
        $sections = [];
        $sectionBuilder = app(form_builder\FormBuilderMethodSectionBuilder::class);

        foreach ($groups as $group) {
            foreach ((array) ($group['methods'] ?? []) as $methodKey) {
                $section = $sectionBuilder->build((string) $methodKey);

                if ($section !== []) {
                    $sections[] = $section;
                }
            }
        }

        return [
            'page' => [
                'key' => 'form.builder',
                'title' => '表单构建器 / Form Builder',
                'summary' => '系统展示 Form.php 的表单级能力，帮助开发者直接抄用表单构建器写法。',
            ],
            'overview' => [
                'summary' => '本页聚焦 app/common/render/Form.php 的公开业务 API，说明如何控制表单标题、结构、提交行为、提示区和进阶扩展。',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'title' => '站点设置',
    'data' => [
        'site_name' => 'DolphinPHP AI',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;
use app\common\render\form\Field;

Form::make('site_form', '站点设置')
    ->title('站点设置')
    ->data([
        'site_name' => 'DolphinPHP AI',
    ])
    ->item(Field::text('site_name', '站点名称'));
CODE,
                ],
            ],
            'groups' => $groups,
            'sections' => $sections,
            'source_refs' => [
                [
                    'path' => 'app/common/render/Form.php',
                    'label' => '表单构建器源码',
                    'description' => '当前页面讲解的所有方法都来自该类。',
                ],
                [
                    'path' => 'app/showcase/controller/admin/Form.php',
                    'label' => 'Showcase 控制器',
                    'description' => '承接首页入口与 builder 页面路由。',
                ],
            ],
        ];
    }
}
