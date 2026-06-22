<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 页面构建器方法目录
 */
final class ShowcasePageBuilderMethodCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'header',
                'title' => '标题与操作区',
                'summary' => '控制页面标题、副标题与页面头部按钮。',
                'methods' => ['title', 'preTitle', 'action'],
            ],
            [
                'key' => 'layout',
                'title' => '内容布局',
                'summary' => '控制 row/rows/grid 与列 class 的基础组织方式。',
                'methods' => ['row', 'rows', 'grid', 'setColClass', 'clear'],
            ],
            [
                'key' => 'tabs',
                'title' => '标签页与内容切换',
                'summary' => '控制内容型和 URL 型 tabs 的组织方式。',
                'methods' => ['tabs'],
            ],
            [
                'key' => 'view',
                'title' => '视图与模板输出',
                'summary' => '控制 assign、display、fetch、filter 等模板层能力。',
                'methods' => ['assign', 'display', 'fetch', 'filter'],
            ],
        ];
    }
}
