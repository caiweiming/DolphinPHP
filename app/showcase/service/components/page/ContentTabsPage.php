<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\common\render\form\Field;

/**
 * 内容型 tabs 详情页
 */
final class ContentTabsPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $baseForm = $this->demoForm('showcase_page_tabs_base', '基础资料', [
            Field::text('nickname', '昵称', '请输入昵称'),
            Field::text('email', '邮箱', '请输入邮箱地址'),
        ], [
            'nickname' => '演示管理员',
            'email' => 'demo@dolphinphp.com',
        ]);

        $securityForm = $this->demoForm('showcase_page_tabs_security', '安全设置', [
            Field::password('password', '新密码')->prop('autocomplete="new-password"'),
            Field::password('confirm_password', '确认密码')->prop('autocomplete="new-password"'),
        ]);

        $overviewHtml = '<div class="card"><div class="card-body"><h4 class="mb-2">概览信息</h4><p class="mb-0 text-muted">适合在当前页快速切换说明块、统计摘要或渲染器内容。</p></div></div>';

        $sections = [
            $this->makeSection(
                'content-tabs',
                'content 模式 tabs',
                '不配置 url，只提供 form 或 content，就会在当前页切换内容，而不是跳转。',
                $this->renderPagePreview(function ($page) use ($baseForm, $securityForm): void {
                    $page->title('账号管理');
                    $page->tabs([
                        'base' => [
                            'title' => '基础资料',
                            'form' => $baseForm,
                        ],
                        'security' => [
                            'title' => '安全设置',
                            'form' => $securityForm,
                        ],
                    ], [
                        'id' => 'showcase-page-content-tabs',
                        'active' => 'base',
                        'remember' => true,
                    ]);
                }),
                [
                    ['name' => 'form', 'value' => '$baseForm / $securityForm'],
                    ['name' => 'remember', 'value' => 'true'],
                ],
                [
                    'content 模式最适合“一个页面多个独立表单”的场景。',
                    'remember 依赖 localStorage，刷新后仍会回到上次激活的 tab。',
                ],
                <<<'CODE'
[
    'tabs' => [
        'base' => ['title' => '基础资料', 'form' => '$baseForm'],
        'security' => ['title' => '安全设置', 'form' => '$securityForm'],
    ],
    'options' => [
        'id' => 'showcase-page-content-tabs',
        'active' => 'base',
        'remember' => true,
    ],
]
CODE,
                <<<'CODE'
$this->page->tabs([
    'base' => [
        'title' => '基础资料',
        'form' => $baseForm,
    ],
    'security' => [
        'title' => '安全设置',
        'form' => $securityForm,
    ],
], [
    'id' => 'showcase-page-content-tabs',
    'active' => 'base',
    'remember' => true,
]);
CODE,
                [
                    ['path' => 'app/admin/controller/Profile.php', 'label' => 'Profile tabs 参考', 'description' => '页面级 content tabs 的真实案例。'],
                    ['path' => 'app/common/render/page/tabs.html', 'label' => 'tabs 模板', 'description' => 'content tabs 的前端结构。'],
                ]
            ),
            $this->makeSection(
                'mixed-tabs',
                'content + url 混合模式',
                '同一个 tabs 容器里既可以放当前页内容切换，也可以放跳转到其他页面的入口。',
                $this->renderPagePreview(function ($page) use ($baseForm, $overviewHtml): void {
                    $page->title('混合 tabs 示例');
                    $page->tabs([
                        'overview' => [
                            'title' => '概览说明',
                            'content' => $overviewHtml,
                        ],
                        'profile' => [
                            'title' => '基础资料',
                            'form' => $baseForm,
                        ],
                        'docs' => [
                            'title' => '文档页',
                            'url' => 'showcase/admin.page/builder',
                        ],
                    ], [
                        'id' => 'showcase-page-mixed-tabs',
                        'active' => 'overview',
                    ]);
                }),
                [
                    ['name' => 'overview', 'value' => 'content'],
                    ['name' => 'profile', 'value' => 'form'],
                    ['name' => 'docs', 'value' => 'url'],
                ],
                [
                    '配置了 url 的标签会直接按跳转模式处理，其余标签仍然保持当前页切换。',
                ],
                <<<'CODE'
[
    'tabs' => [
        'overview' => ['title' => '概览说明', 'content' => '$overviewHtml'],
        'profile' => ['title' => '基础资料', 'form' => '$baseForm'],
        'docs' => ['title' => '文档页', 'url' => 'showcase/admin.page/builder'],
    ],
]
CODE,
                <<<'CODE'
$this->page->tabs([
    'overview' => [
        'title' => '概览说明',
        'content' => $overviewHtml,
    ],
    'profile' => [
        'title' => '基础资料',
        'form' => $baseForm,
    ],
    'docs' => [
        'title' => '文档页',
        'url' => 'showcase/admin.page/builder',
    ],
]);
CODE,
                [
                    ['path' => 'docs/页面/tabs.md', 'label' => 'tabs 文档', 'description' => '混合模式与激活规则说明。'],
                ]
            ),
            $this->makeSection(
                'fill-right-disabled',
                'fill / right / disabled 变体',
                '除了基本切换，tabs 还支持等宽铺满、右侧对齐和禁用标签这些布局型参数。',
                $this->renderPagePreview(function ($page) use ($overviewHtml, $securityForm): void {
                    $page->title('tabs 布局变体');
                    $page->tabs([
                        'summary' => [
                            'title' => '概览',
                            'content' => $overviewHtml,
                        ],
                        'security' => [
                            'title' => '安全设置',
                            'form' => $securityForm,
                        ],
                        'logs' => [
                            'title' => '日志中心',
                            'right' => true,
                            'url' => 'showcase/admin.chart/index',
                        ],
                        'coming' => [
                            'title' => '高级设置',
                            'disabled' => true,
                            'content' => '<div class="alert alert-warning mb-0">该标签已禁用</div>',
                        ],
                    ], [
                        'id' => 'showcase-page-layout-tabs',
                        'fill' => true,
                        'active' => 'summary',
                    ]);
                }),
                [
                    ['name' => 'fill', 'value' => 'true'],
                    ['name' => 'right', 'value' => 'true'],
                    ['name' => 'disabled', 'value' => 'true'],
                ],
                [
                    'right 更适合把“日志”“帮助”“外链说明”这类附属标签放到最右侧。',
                    'disabled 只控制前端可点击状态，不代表后端自动做权限控制。',
                ],
                <<<'CODE'
[
    'tabs' => [
        'summary' => ['title' => '概览', 'content' => '$overviewHtml'],
        'security' => ['title' => '安全设置', 'form' => '$securityForm'],
        'logs' => ['title' => '日志中心', 'right' => true, 'url' => 'showcase/admin.chart/index'],
        'coming' => ['title' => '高级设置', 'disabled' => true],
    ],
    'options' => [
        'fill' => true,
        'active' => 'summary',
    ],
]
CODE,
                <<<'CODE'
$this->page->tabs([
    'summary' => [
        'title' => '概览',
        'content' => $overviewHtml,
    ],
    'security' => [
        'title' => '安全设置',
        'form' => $securityForm,
    ],
    'logs' => [
        'title' => '日志中心',
        'right' => true,
        'url' => 'showcase/admin.chart/index',
    ],
    'coming' => [
        'title' => '高级设置',
        'disabled' => true,
    ],
], [
    'fill' => true,
    'active' => 'summary',
]);
CODE,
                [
                    ['path' => 'app/common/render/page/tabs.html', 'label' => 'tabs 模板', 'description' => 'fill / right / disabled 的前端结构来源。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '页面级 tabs 解决的是“同一页面多个独立内容块之间怎么切换”。它和表单项 `tabs` 不是一回事，层级更高。',
                'scenarios' => [
                    '个人中心：基础资料 / 安全设置',
                    '系统设置：多个独立配置分组切换',
                    '一页多个互不影响的 Form 或 HTML 内容块',
                ],
                'capabilities' => ['content 模式', 'remember', 'active', 'Form 内容块'],
                'quick_start' => [
                    'array_code' => "[\n    'base' => ['title' => '基础资料', 'form' => '\$baseForm'],\n    'security' => ['title' => '安全设置', 'form' => '\$securityForm'],\n]",
                    'page_code' => "\$this->page->tabs([\n    'base' => ['title' => '基础资料', 'form' => \$baseForm],\n    'security' => ['title' => '安全设置', 'form' => \$securityForm],\n], ['remember' => true]);",
                ],
            ],
            [
                [
                    'title' => 'tabs 参数',
                    'items' => [
                        ['name' => 'title', 'summary' => '标签标题。'],
                        ['name' => 'form/content', 'summary' => 'content 模式内容来源。'],
                        ['name' => 'active', 'summary' => '默认激活标签。'],
                        ['name' => 'remember', 'summary' => '是否记住上次激活标签。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
