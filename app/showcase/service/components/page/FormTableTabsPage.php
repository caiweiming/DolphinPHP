<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\common\render\form\Field;

/**
 * 表单表格组合 tabs 详情页
 */
final class FormTableTabsPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $settingsForm = $this->demoForm('showcase_page_tabs_settings', '系统设置', [
            Field::text('site_name', '站点名称', '请输入站点名称'),
            Field::switch('site_status', '站点状态')->text(['关闭', '开启']),
        ], [
            'site_name' => 'DolphinPHP AI',
            'site_status' => 1,
        ]);

        $configTable = $this->demoTable('showcase_page_tabs_table', [
            ['id' => 1, 'group' => '站点', 'title' => '站点名称'],
            ['id' => 2, 'group' => '上传', 'title' => '默认存储'],
        ], [
            ['id', 'ID'],
            ['group', '分组'],
            ['title', '配置项'],
        ]);

        $sections = [
            $this->makeSection(
                'form-table-tabs',
                'Form 与 Table 混合 tabs',
                'tabs() 本质上承载的是“可被渲染的内容块”，因此完全可以一个 tab 放 Form，另一个 tab 放 Table。',
                $this->renderPagePreview(function ($page) use ($settingsForm, $configTable): void {
                    $page->title('配置中心');
                    $page->tabs([
                        'settings' => [
                            'title' => '系统设置',
                            'form' => $settingsForm,
                        ],
                        'config' => [
                            'title' => '配置定义',
                            'content' => $configTable,
                        ],
                    ], [
                        'id' => 'showcase-page-form-table-tabs',
                        'active' => 'settings',
                        'remember' => true,
                    ]);
                }),
                [
                    ['name' => 'settings', 'value' => 'Form'],
                    ['name' => 'config', 'value' => 'Table'],
                ],
                [
                    '这是 Page 承载不同渲染器块的典型场景，和 admin 的 System/Config 页面高度一致。',
                ],
                <<<'CODE'
[
    'tabs' => [
        'settings' => ['title' => '系统设置', 'form' => '$settingsForm'],
        'config' => ['title' => '配置定义', 'content' => '$configTable'],
    ],
]
CODE,
                <<<'CODE'
$this->page->tabs([
    'settings' => [
        'title' => '系统设置',
        'form' => $settingsForm,
    ],
    'config' => [
        'title' => '配置定义',
        'content' => $configTable,
    ],
], [
    'id' => 'showcase-page-form-table-tabs',
    'active' => 'settings',
    'remember' => true,
]);
CODE,
                [
                    ['path' => 'app/admin/controller/System.php', 'label' => 'System tabs 参考', 'description' => 'Page tabs + Form 的真实业务页。'],
                    ['path' => 'app/admin/controller/Config.php', 'label' => 'Config tabs 参考', 'description' => 'Page tabs + Table 的真实业务页。'],
                ]
            ),
            $this->makeSection(
                'url-tabs',
                '纯 url 模式 tabs',
                '如果每个标签都是独立控制器页面，那么只传 url 即可，Page 会自动根据当前地址匹配激活态。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('URL tabs 示例');
                    $page->tabs([
                        'page' => [
                            'title' => '页面渲染器',
                            'url' => 'showcase/admin.page/index',
                        ],
                        'form' => [
                            'title' => '表单渲染器',
                            'url' => 'showcase/admin.form/index',
                        ],
                        'table' => [
                            'title' => '表格渲染器',
                            'url' => 'showcase/admin.table/index',
                        ],
                    ], [
                        'id' => 'showcase-page-url-tabs',
                        'active' => 'page',
                    ]);
                    $page->row([
                        ['<div class="card"><div class="card-body">这种模式适合“每个标签页本身就是一张完整页面”。</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'url', 'value' => '控制器路由'],
                    ['name' => 'active', 'value' => "'page'"],
                ],
                [
                    'url 模式的激活态优先按当前请求路径自动匹配，其次才看 options.active。',
                ],
                <<<'CODE'
[
    'tabs' => [
        'page' => ['title' => '页面渲染器', 'url' => 'showcase/admin.page/index'],
        'form' => ['title' => '表单渲染器', 'url' => 'showcase/admin.form/index'],
        'table' => ['title' => '表格渲染器', 'url' => 'showcase/admin.table/index'],
    ],
]
CODE,
                <<<'CODE'
$this->page->tabs([
    'page' => [
        'title' => '页面渲染器',
        'url' => 'showcase/admin.page/index',
    ],
    'form' => [
        'title' => '表单渲染器',
        'url' => 'showcase/admin.form/index',
    ],
    'table' => [
        'title' => '表格渲染器',
        'url' => 'showcase/admin.table/index',
    ],
], [
    'active' => 'page',
]);
CODE,
                [
                    ['path' => 'docs/页面/tabs.md', 'label' => 'tabs 文档', 'description' => 'url 模式和激活规则说明。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'Page tabs 的内容源并不限于 Form。只要是字符串、可渲染对象、闭包，都可以塞进 content 模式 tabs 里。',
                'scenarios' => [
                    '配置中心：左边设置、右边配置项定义',
                    '某一业务页中按 tab 切换“录入表单”和“历史记录表格”',
                    '一页内多种渲染器块之间的结构化切换',
                ],
                'capabilities' => ['Form tabs', 'Table tabs', 'content 混合模式'],
                'quick_start' => [
                    'array_code' => "[\n    'settings' => ['title' => '系统设置', 'form' => '\$settingsForm'],\n    'config' => ['title' => '配置定义', 'content' => '\$configTable'],\n]",
                    'page_code' => "\$this->page->tabs([\n    'settings' => ['title' => '系统设置', 'form' => \$settingsForm],\n    'config' => ['title' => '配置定义', 'content' => \$configTable],\n]);",
                ],
            ],
            [
                [
                    'title' => '内容来源',
                    'items' => [
                        ['name' => 'form', 'summary' => '推荐直接传 Form 渲染器。'],
                        ['name' => 'content', 'summary' => '可传 Table、HTML 字符串、闭包等。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
