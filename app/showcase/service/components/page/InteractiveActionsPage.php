<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

/**
 * 页面动作详情页
 */
final class InteractiveActionsPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'action-basic',
                '普通跳转与文档按钮',
                '最基础的页面动作是头部跳转按钮，适合新增、返回、查看文档等场景。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('页面动作示例');
                    $page->action('create', [
                        'title' => '新增用户',
                        'url' => 'admin/user/add',
                        'class' => 'btn btn-primary',
                        'icon' => 'ti ti-plus',
                    ]);
                    $page->action('docs', [
                        'title' => '查看文档',
                        'url' => 'https://www.dolphinphp.com',
                        'target' => '_blank',
                        'class' => 'btn btn-outline-secondary',
                    ]);
                    $page->row([
                        ['<div class="card"><div class="card-body">页面动作位于标题区右侧，不属于页面内容行。</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'url', 'value' => "'admin/user/add' / 'https://...'"],
                    ['name' => 'target', 'value' => "'_blank'"],
                ],
                [
                    '普通跳转是最轻量的 action 用法，不需要前端脚本协助。',
                ],
                <<<'CODE'
[
    'title' => '新增用户',
    'url' => 'admin/user/add',
    'class' => 'btn btn-primary',
    'icon' => 'ti ti-plus',
]
CODE,
                <<<'CODE'
$this->page->action('create', [
    'title' => '新增用户',
    'url' => 'admin/user/add',
    'class' => 'btn btn-primary',
    'icon' => 'ti ti-plus',
]);
CODE,
                [
                    ['path' => 'docs/页面/action.md', 'label' => 'action 文档', 'description' => '普通跳转、target、props 等说明。'],
                ]
            ),
            $this->makeSection(
                'action-ajax-confirm',
                'Ajax 与确认弹窗',
                '同步、清缓存、重建索引这类操作适合配合 ajax + confirm 声明式配置。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('Ajax 动作示例');
                    $page->action('sync', [
                        'title' => '立即同步',
                        'url' => 'showcase/admin.demoApi/submit',
                        'class' => 'btn btn-primary',
                        'ajax' => 'post',
                        'confirm' => '确认立即同步最新数据吗？',
                    ]);
                    $page->action('refresh', [
                        'title' => '刷新缓存',
                        'url' => 'showcase/admin.demoApi/submit',
                        'class' => 'btn btn-warning',
                        'ajax' => 'get',
                    ]);
                    $page->row([
                        ['<div class="alert alert-info mb-0">点击这些按钮时，后台布局会读取 data-config，按 Ajax / confirm 协议执行。</div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'ajax', 'value' => "'post' / 'get'"],
                    ['name' => 'confirm', 'value' => "'确认立即同步最新数据吗？'"],
                ],
                [
                    'action() 会把 ajax / confirm 规范化后塞进 data-config，由后台通用脚本执行。',
                ],
                <<<'CODE'
[
    'title' => '立即同步',
    'url' => 'admin/report/sync',
    'ajax' => 'post',
    'confirm' => '确认立即同步最新数据吗？',
]
CODE,
                <<<'CODE'
$this->page->action('sync', [
    'title' => '立即同步',
    'url' => 'admin/report/sync',
    'class' => 'btn btn-primary',
    'ajax' => 'post',
    'confirm' => '确认立即同步最新数据吗？',
]);
CODE,
                [
                    ['path' => 'app/admin/view/layout/default.html', 'label' => '页面动作前端执行器', 'description' => 'dp-page-action 如何解析 data-config。'],
                ]
            ),
            $this->makeSection(
                'action-pop-props',
                '弹窗与 props 透传',
                '需要弹层详情或额外 data-* 属性时，可以用 pop 和 props 做声明式配置。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('弹层动作示例');
                    $page->action('detail', [
                        'title' => '查看详情',
                        'url' => 'showcase/admin.page/show?key=basic.header',
                        'class' => 'btn btn-outline-primary',
                        'pop' => [
                            'title' => '详情',
                            'area' => ['960px', '720px'],
                        ],
                        'props' => [
                            'data-scene' => 'showcase',
                            'id' => 'showcase-page-action-detail',
                        ],
                    ]);
                    $page->row([
                        ['<div class="card"><div class="card-body">props 会被透传到最终按钮节点，方便脚本挂载和标识。</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'pop.title', 'value' => "'详情'"],
                    ['name' => 'props', 'value' => "['data-scene' => 'showcase']"],
                ],
                [
                    'pop 更适合详情、选择器、辅助页面，不要和 ajax 混成一个按钮。',
                ],
                <<<'CODE'
[
    'title' => '查看详情',
    'url' => 'admin/user/detail?id=1',
    'pop' => ['title' => '详情', 'area' => ['960px', '720px']],
    'props' => ['data-scene' => 'showcase', 'id' => 'detail-btn'],
]
CODE,
                <<<'CODE'
$this->page->action('detail', [
    'title' => '查看详情',
    'url' => 'admin/user/detail?id=1',
    'class' => 'btn btn-outline-primary',
    'pop' => [
        'title' => '详情',
        'area' => ['960px', '720px'],
    ],
    'props' => [
        'data-scene' => 'showcase',
        'id' => 'detail-btn',
    ],
]);
CODE,
                [
                    ['path' => 'docs/页面/action.md', 'label' => 'action 文档', 'description' => 'pop / props 的详细参数说明。'],
                ]
            ),
            $this->makeSection(
                'action-form-svg',
                '指定表单提交与 SVG 图标',
                '当头部按钮需要触发表单提交时，可以通过 ajax.form 指定目标表单；如果图标不想依赖 class，也可以直接传 svg。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('表单提交按钮示例');
                    $page->action('save', [
                        'title' => '保存配置',
                        'url' => 'showcase/admin.demoApi/submit',
                        'class' => 'btn btn-success',
                        'ajax' => [
                            'type' => 'post',
                            'form' => 'showcase_page_action_form',
                            'no_refresh' => true,
                        ],
                        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5l10 -10"/></svg>',
                    ]);
                    $page->row([
                        ['<form id="showcase_page_action_form" class="card"><div class="card-body"><div class="text-muted">这里演示的是 action 通过 ajax.form 指向表单，而不是按钮写在表单内部。</div></div></form>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'ajax.form', 'value' => "'showcase_page_action_form'"],
                    ['name' => 'ajax.no_refresh', 'value' => 'true'],
                    ['name' => 'svg', 'value' => '<svg>...</svg>'],
                ],
                [
                    '这是页面头部按钮和页面内容区表单协同的典型写法，适合配置页、工作台页。',
                ],
                <<<'CODE'
[
    'title' => '保存配置',
    'url' => 'admin/system/save',
    'ajax' => [
        'type' => 'post',
        'form' => 'system_config_form',
        'no_refresh' => true,
    ],
    'svg' => '<svg>...</svg>',
]
CODE,
                <<<'CODE'
$this->page->action('save', [
    'title' => '保存配置',
    'url' => 'admin/system/save',
    'class' => 'btn btn-success',
    'ajax' => [
        'type' => 'post',
        'form' => 'system_config_form',
        'no_refresh' => true,
    ],
    'svg' => '<svg>...</svg>',
]);
CODE,
                [
                    ['path' => 'docs/页面/action.md', 'label' => 'action 文档', 'description' => 'ajax.form / svg 等写法说明。'],
                ]
            ),
            $this->makeSection(
                'action-props-string',
                'props 字符串写法与 no_forward',
                '如果属性本身就是一段现成 HTML 属性字符串，可以直接原样透传；Ajax 行为也可以用 no_forward 阻止自动跳转。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('props 字符串示例');
                    $page->action('export', [
                        'title' => '导出并停留',
                        'url' => 'showcase/admin.demoApi/submit',
                        'class' => 'btn btn-outline-success',
                        'ajax' => [
                            'type' => 'post',
                            'no_forward' => true,
                        ],
                        'props' => 'data-scene="export" aria-label="导出并停留"',
                    ]);
                    $page->row([
                        ['<div class="card"><div class="card-body">当你已经有一段固定属性串时，props 直接传字符串会更省事。</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'ajax.no_forward', 'value' => 'true'],
                    ['name' => 'props', 'value' => "'data-scene=\"export\" ...'"],
                ],
                [
                    '数组 props 更适合结构化构建；字符串 props 更适合直接复用已有属性片段。',
                ],
                <<<'CODE'
[
    'title' => '导出并停留',
    'url' => 'admin/report/export',
    'ajax' => [
        'type' => 'post',
        'no_forward' => true,
    ],
    'props' => 'data-scene="export" aria-label="导出并停留"',
]
CODE,
                <<<'CODE'
$this->page->action('export', [
    'title' => '导出并停留',
    'url' => 'admin/report/export',
    'class' => 'btn btn-outline-success',
    'ajax' => [
        'type' => 'post',
        'no_forward' => true,
    ],
    'props' => 'data-scene="export" aria-label="导出并停留"',
]);
CODE,
                [
                    ['path' => 'docs/页面/action.md', 'label' => 'action 文档', 'description' => 'props 数组与字符串两种写法说明。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'action() 让页面头部按钮变成声明式配置，而不是散落在模板和 JS 里的手写事件。这是 Page 渲染器非常有辨识度的一项能力。',
                'scenarios' => [
                    '列表页顶部的新增、导出、同步、重置等全局动作',
                    '报表页顶部的一键同步、刷新缓存、查看说明',
                    '需要结合 confirm / ajax / pop 的管理页操作区',
                ],
                'capabilities' => ['普通跳转', 'ajax', 'confirm', 'pop', 'props'],
                'quick_start' => [
                    'array_code' => "[\n    'title' => '立即同步',\n    'url' => 'admin/report/sync',\n    'ajax' => 'post',\n    'confirm' => '确认立即同步吗？',\n]",
                    'page_code' => "\$this->page->action('sync', [\n    'title' => '立即同步',\n    'url' => 'admin/report/sync',\n    'class' => 'btn btn-primary',\n    'ajax' => 'post',\n    'confirm' => '确认立即同步吗？',\n]);",
                ],
            ],
            [
                [
                    'title' => '按钮参数',
                    'items' => [
                        ['name' => 'title/url/class', 'summary' => '最基础的按钮文案、地址和样式。'],
                        ['name' => 'ajax', 'summary' => '声明 Ajax 行为。'],
                        ['name' => 'confirm', 'summary' => '声明确认弹窗。'],
                        ['name' => 'pop', 'summary' => '声明弹层打开。'],
                        ['name' => 'props', 'summary' => '透传原生 HTML 属性。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
