<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\showcase\service\registry\ShowcasePageBuilderMethodCatalog;

/**
 * Showcase 页面构建器页面组装器
 */
final class PageBuilderPage
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $groups = ShowcasePageBuilderMethodCatalog::groups();
        $sections = [];
        $sectionBuilder = app(page_builder\PageBuilderMethodSectionBuilder::class);

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
                'key' => 'page.builder',
                'title' => '页面构建器 / Page Builder',
                'summary' => '系统展示 Page.php 的页面级能力，帮助开发者快速组织标题、行列布局、标签页和头部动作。',
            ],
            'overview' => [
                'summary' => '本页聚焦 app/common/render/Page.php 的公开业务 API，说明页面标题、row/grid 布局、tabs 组织以及 action 头部按钮的实际写法。',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'title' => '运营总览',
    'pre_title' => 'Dashboard',
    'rows' => [
        [
            ['<div class="card"><div class="card-body">今日订单 128</div></div>', 'col-md-4'],
            ['<div class="card"><div class="card-body">支付转化 21.6%</div></div>', 'col-md-8'],
        ],
    ],
]
CODE,
                    'page_code' => <<<'CODE'
use app\common\render\Page;

$page = Page::make('dashboard_preview')
    ->preTitle('Dashboard')
    ->title('运营总览')
    ->row([
        ['<div class="card"><div class="card-body">今日订单 128</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">支付转化 21.6%</div></div>', 'md-8'],
    ]);
CODE,
                ],
            ],
            'groups' => $groups,
            'sections' => $sections,
            'source_refs' => [
                [
                    'path' => 'app/common/render/Page.php',
                    'label' => '页面构建器源码',
                    'description' => '当前页面讲解的所有方法都来自该类。',
                ],
                [
                    'path' => 'app/showcase/controller/admin/Page.php',
                    'label' => 'Showcase 控制器',
                    'description' => '承接首页入口与 builder 页面路由。',
                ],
            ],
        ];
    }
}

