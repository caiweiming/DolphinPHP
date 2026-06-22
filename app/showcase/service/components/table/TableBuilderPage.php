<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

use app\showcase\service\registry\ShowcaseTableBuilderMethodCatalog;

/**
 * Showcase 表格构建器页面组装器
 */
final class TableBuilderPage
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $groups = ShowcaseTableBuilderMethodCatalog::groups();
        $sections = [];
        $sectionBuilder = app(table_builder\TableBuilderMethodSectionBuilder::class);

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
                'key' => 'table.builder',
                'title' => '表格构建器 / Table Builder',
                'summary' => '系统展示 Table.php 的表格级能力，帮助开发者直接抄用表格构建器写法。',
            ],
            'overview' => [
                'summary' => '本页聚焦 app/common/render/Table.php 的公开业务 API，说明如何组织数据源、列配置、搜索区、工具栏与 quickEdit 相关能力。',
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    'id' => 'user_list',
    'data' => [
        ['id' => 1, 'username' => 'admin', 'status' => 1],
        ['id' => 2, 'username' => 'editor', 'status' => 0],
    ],
    'columns' => [
        ['id', 'ID'],
        ['username', '用户名'],
        ['status', '状态', 'status', ['禁用', '启用:green']],
    ],
]
CODE,
                    'table_code' => <<<'CODE'
use app\common\render\Table;

Table::make('user_list')
    ->data([
        ['id' => 1, 'username' => 'admin', 'status' => 1],
        ['id' => 2, 'username' => 'editor', 'status' => 0],
    ])
    ->columns([
        ['id', 'ID'],
        ['username', '用户名'],
        ['status', '状态', 'status', ['禁用', '启用:green']],
    ]);
CODE,
                ],
            ],
            'groups' => $groups,
            'sections' => $sections,
            'source_refs' => [
                [
                    'path' => 'app/common/render/Table.php',
                    'label' => '表格构建器源码',
                    'description' => '当前页面讲解的所有方法都来自该类。',
                ],
                [
                    'path' => 'app/showcase/controller/admin/Table.php',
                    'label' => 'Showcase 控制器',
                    'description' => '承接首页入口与 builder 页面路由。',
                ],
            ],
        ];
    }
}
