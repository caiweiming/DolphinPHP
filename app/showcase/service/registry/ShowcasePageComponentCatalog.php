<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 页面组件目录
 */
final class ShowcasePageComponentCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'basic' => ['title' => '基础页面', 'summary' => '标题、副标题、单行内容和基础承载结构。'],
            'layout' => ['title' => '布局与组合', 'summary' => '多列网格、混合内容与页面级组合布局。'],
            'tabs' => ['title' => '标签页页面', 'summary' => '同页切换 tabs、URL tabs 与表单表格组合场景。'],
            'actions' => ['title' => '页面级动作', 'summary' => '头部操作按钮、确认弹窗、弹层与 Ajax 动作。'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function components(): array
    {
        return [
            'basic.header' => [
                'key' => 'basic.header',
                'renderer' => 'page',
                'group' => 'basic',
                'group_title' => '基础页面',
                'title' => 'header 页面标题区',
                'summary' => '演示 title()、preTitle() 与页面头部显示规则，适合先理解 Page 的基础骨架。',
                'status' => 'available',
                'tags' => ['title', 'preTitle', 'header', '基础骨架'],
                'doc_links' => ['docs/项目架构文档.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 构建器源码', 'description' => '页面标题、副标题和行布局的核心实现。'],
                    ['path' => 'app/admin/view/layout/default.html', 'label' => '后台主布局', 'description' => '页面头部、操作按钮与 row 渲染的最终模板。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\HeaderPage',
            ],
            'basic.row' => [
                'key' => 'basic.row',
                'renderer' => 'page',
                'group' => 'basic',
                'group_title' => '基础页面',
                'title' => 'row 单行与批量行',
                'summary' => '演示 row()、rows()、setColClass()、clear() 的真实组合能力。',
                'status' => 'available',
                'tags' => ['row', 'rows', 'setColClass', 'clear'],
                'doc_links' => ['docs/项目架构文档.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 构建器源码', 'description' => 'row() / rows() / clear() 的实现位置。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\RowPage',
            ],
            'layout.grid' => [
                'key' => 'layout.grid',
                'renderer' => 'page',
                'group' => 'layout',
                'group_title' => '布局与组合',
                'title' => 'grid 响应式网格',
                'summary' => '演示固定列数与响应式断点两种 grid() 写法，以及和卡片内容的组合方式。',
                'status' => 'available',
                'tags' => ['grid', 'row-cols', '响应式', '多列布局'],
                'doc_links' => ['docs/图表/chart.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 构建器源码', 'description' => 'grid() 如何拆分 chunk 并生成响应式 row class。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\GridPage',
            ],
            'layout.mixed' => [
                'key' => 'layout.mixed',
                'renderer' => 'page',
                'group' => 'layout',
                'group_title' => '布局与组合',
                'title' => 'mixed 混合内容页面',
                'summary' => '演示 Page 如何承载统计卡片、Form、Table、Chart 等混合内容块。',
                'status' => 'available',
                'tags' => ['row', 'Form', 'Table', 'Chart', '组合页面'],
                'doc_links' => ['docs/项目架构文档.md'],
                'source_refs' => [
                    ['path' => 'app/admin/controller/Profile.php', 'label' => 'Profile 控制器参考', 'description' => '页面承载多个 Form 内容块的真实用法。'],
                    ['path' => 'app/admin/controller/Config.php', 'label' => 'Config 控制器参考', 'description' => '页面 tabs 与 Table 组合的真实用法。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\MixedLayoutPage',
            ],
            'tabs.content' => [
                'key' => 'tabs.content',
                'renderer' => 'page',
                'group' => 'tabs',
                'group_title' => '标签页页面',
                'title' => 'tabs 内容切换',
                'summary' => '演示 content 模式 tabs，在一个页面中切换多个独立内容块。',
                'status' => 'available',
                'tags' => ['tabs', 'content', 'remember', 'active'],
                'doc_links' => ['docs/页面/tabs.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 构建器源码', 'description' => 'tabs() 的 content / url 模式判定逻辑。'],
                    ['path' => 'app/common/render/page/tabs.html', 'label' => 'tabs 模板', 'description' => '页面 tabs 的最终 HTML 模板。'],
                    ['path' => 'public/static/render/page/page.js', 'label' => 'tabs 前端逻辑', 'description' => 'remember 能力与 tabs 状态恢复逻辑。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\ContentTabsPage',
            ],
            'tabs.form_table' => [
                'key' => 'tabs.form_table',
                'renderer' => 'page',
                'group' => 'tabs',
                'group_title' => '标签页页面',
                'title' => 'tabs 表单与表格组合',
                'summary' => '演示 tabs() 同时承载 Form 和 Table 两种渲染器，适合系统设置、配置中心等后台页。',
                'status' => 'available',
                'tags' => ['tabs', 'Form', 'Table', '组合页'],
                'doc_links' => ['docs/页面/tabs.md'],
                'source_refs' => [
                    ['path' => 'app/admin/controller/System.php', 'label' => 'System 控制器参考', 'description' => 'Page tabs + Form 的真实业务写法。'],
                    ['path' => 'app/admin/controller/Config.php', 'label' => 'Config 控制器参考', 'description' => 'Page tabs + Table 的真实业务写法。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\FormTableTabsPage',
            ],
            'actions.interactive' => [
                'key' => 'actions.interactive',
                'renderer' => 'page',
                'group' => 'actions',
                'group_title' => '页面级动作',
                'title' => 'action 页面头部按钮',
                'summary' => '演示普通跳转、Ajax、confirm、pop 与 props 等 action() 常见组合。',
                'status' => 'available',
                'tags' => ['action', 'ajax', 'confirm', 'pop', 'props'],
                'doc_links' => ['docs/页面/action.md'],
                'source_refs' => [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 构建器源码', 'description' => 'action() 及其 ajax/pop/confirm 规范化逻辑。'],
                    ['path' => 'app/admin/view/layout/default.html', 'label' => '页面按钮前端逻辑', 'description' => 'dp-page-action 点击后的 Ajax / confirm / pop 执行流程。'],
                ],
                'builder' => 'app\\showcase\\service\\components\\page\\InteractiveActionsPage',
            ],
        ];
    }
}
