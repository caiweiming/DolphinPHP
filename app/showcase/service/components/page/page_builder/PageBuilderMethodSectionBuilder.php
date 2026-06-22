<?php
declare(strict_types=1);

namespace app\showcase\service\components\page\page_builder;

/**
 * Showcase 页面构建器方法板块组装器
 */
final class PageBuilderMethodSectionBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $methodKey): array
    {
        return match ($methodKey) {
            'title' => $this->title(),
            'preTitle' => $this->preTitle(),
            'action' => $this->action(),
            'row' => $this->row(),
            'rows' => $this->rows(),
            'grid' => $this->grid(),
            'tabs' => $this->tabs(),
            'assign' => $this->assign(),
            'display' => $this->display(),
            'fetch' => $this->fetch(),
            'filter' => $this->filter(),
            'setColClass' => $this->setColClass(),
            'clear' => $this->clear(),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function wrap(array $payload): array
    {
        return array_merge([
            'key' => '',
            'group_key' => '',
            'title' => '',
            'signature' => '',
            'summary' => '',
            'parameter_details' => [],
            'array_code' => '',
            'page_code' => '',
            'usage_variants' => [],
            'behavior_notes' => [],
            'tips' => [],
            'example_mode' => 'source',
            'preview_html' => '',
            'source_refs' => [
                [
                    'path' => 'app/common/render/Page.php',
                    'label' => 'Page 构建器源码',
                    'description' => '方法签名与行为均来自该类。',
                ],
            ],
        ], $payload);
    }

    private function title(): array
    {
        return $this->wrap([
            'key' => 'title',
            'group_key' => 'header',
            'title' => 'title() 页面标题',
            'signature' => "title(string \$value = '', string \$default = '')",
            'summary' => '设置页面主标题。只要标题不为空，页面头部区域就会显示；当业务标题可能为空时，也可以通过默认值参数提供兜底标题。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '页面标题。'],
                ['name' => '$default', 'summary' => '当 $value 为空时使用的兜底标题。'],
            ],
            'array_code' => "[\n    'title' => '',\n    'default' => '数据看板',\n]",
            'page_code' => "use app\\common\\render\\Page;\n\nPage::make('dashboard')\n    ->title('', '数据看板');",
            'behavior_notes' => [
                'title() 会影响后台布局中的 page-title 区块，是页面级信息，不是卡片标题。',
                '当第一个参数为空时，会自动退回到第二个默认标题；只要最终标题不为空，就会触发页面头部显示。',
            ],
            'tips' => [
                '如果页面内容本身已经很多，标题建议保持业务化且短，避免头部过高。',
            ],
            'usage_variants' => [
                [
                    'title' => '直接设置业务标题',
                    'summary' => '最常见写法，适合标题明确且不会为空的后台页面。',
                    'array_code' => "[\n    'title' => '运营总览',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->title('运营总览');",
                ],
                [
                    'title' => '带默认值兜底',
                    'summary' => '适合标题来自查询条件、动态对象或外部配置时避免出现空标题。',
                    'array_code' => "[\n    'title' => '',\n    'default' => '数据看板',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->title('', '数据看板');",
                ],
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="showcase-page-preview-shell__header"><div class="showcase-page-preview-shell__headline"><h4 class="showcase-page-preview-shell__title">运营总览</h4></div></div>',
        ]);
    }

    private function preTitle(): array
    {
        return $this->wrap([
            'key' => 'preTitle',
            'group_key' => 'header',
            'title' => 'preTitle() 页面副标题',
            'signature' => "preTitle(string \$value = '')",
            'summary' => '设置页面副标题，通常用于模块名、上级分类、面包屑化描述或英文辅助标题。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '副标题文本。'],
            ],
            'array_code' => "[\n    'pre_title' => 'Dashboard / Orders',\n    'title' => '订单分析',\n]",
            'page_code' => "use app\\common\\render\\Page;\n\nPage::make('dashboard')\n    ->preTitle('Dashboard / Orders')\n    ->title('订单分析');",
            'behavior_notes' => [
                'preTitle() 通常配合 title() 使用，单独使用也会触发页面头部显示。',
                '它更适合承担“分类层级提示”的职责，不建议塞过长说明，否则页面头部会显得笨重。',
            ],
            'tips' => [
                '副标题更适合做分层提示，不建议堆砌太长说明文案。',
            ],
            'usage_variants' => [
                [
                    'title' => '英文辅助标题',
                    'summary' => '适合中英文混合后台，或用英文模块名辅助定位。',
                    'array_code' => "[\n    'pre_title' => 'Dashboard',\n    'title' => '运营总览',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->preTitle('Dashboard')\n    ->title('运营总览');",
                ],
                [
                    'title' => '层级式副标题',
                    'summary' => '适合将模块名和当前业务域串起来，形成近似面包屑的提示。',
                    'array_code' => "[\n    'pre_title' => 'Dashboard / Orders',\n    'title' => '订单分析',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->preTitle('Dashboard / Orders')\n    ->title('订单分析');",
                ],
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="showcase-page-preview-shell__header"><div class="showcase-page-preview-shell__headline"><div class="showcase-page-preview-shell__pretitle">Dashboard</div><h4 class="showcase-page-preview-shell__title">运营总览</h4></div></div>',
        ]);
    }

    private function action(): array
    {
        return $this->wrap([
            'key' => 'action',
            'group_key' => 'header',
            'title' => 'action() 页面头部按钮',
            'signature' => "action(string \$name = '', array \$attrs = [], string \$pos = 'top-right')",
            'summary' => '在页面头部右侧注册操作按钮，支持普通跳转、Ajax、confirm、pop 和 props。',
            'parameter_details' => [
                ['name' => '$name', 'summary' => '按钮内部标识。'],
                ['name' => '$attrs', 'summary' => '按钮配置数组。'],
                ['name' => '$pos', 'summary' => '当前主要使用 top-right。'],
            ],
            'array_code' => <<<'CODE'
[
    'actions' => [
        [
            'name' => 'sync',
            'title' => '立即同步',
            'url' => 'admin/report/sync',
            'ajax' => 'post',
            'confirm' => [
                'title' => '同步确认',
                'text' => '该操作会重新拉取远端统计结果。',
                'confirmText' => '立即同步',
                'cancelText' => '稍后处理',
            ],
        ],
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('report_page')
    ->title('报表中心')
    ->action('sync', [
        'title' => '立即同步',
        'url' => 'admin/report/sync',
        'class' => 'btn btn-primary',
        'ajax' => [
            'method' => 'post',
            'no_refresh' => true,
            'no_forward' => true,
        ],
        'confirm' => [
            'title' => '同步确认',
            'text' => '该操作会重新拉取远端统计结果。',
            'confirmText' => '立即同步',
            'cancelText' => '稍后处理',
        ],
    ]);
CODE,
            'behavior_notes' => [
                'action() 最终挂在后台布局的 page header 区域，不属于某一行内容块。',
                '配置了 ajax / pop / confirm 时，会自动补充 dp-page-action 类和 data-config。',
            ],
            'tips' => [
                '危险操作统一加 confirm；打开弹窗用 pop；提交请求用 ajax，职责不要混用。',
            ],
            'usage_variants' => [
                [
                    'title' => '普通跳转',
                    'summary' => '适合跳转到新增页、文档页、导出页。',
                    'array_code' => "[\n    'title' => '新增用户',\n    'url' => 'admin/user/add',\n]",
                    'page_code' => "Page::make('user_page')->action('create', [\n    'title' => '新增用户',\n    'url' => 'admin/user/add',\n    'class' => 'btn btn-primary',\n]);",
                ],
                [
                    'title' => '确认后 Ajax 提交',
                    'summary' => '适合同步、清理缓存、批量刷新等页面级操作。',
                    'array_code' => "[\n    'title' => '立即同步',\n    'url' => 'admin/report/sync',\n    'ajax' => ['method' => 'post', 'no_refresh' => true],\n    'confirm' => ['title' => '同步确认', 'confirmText' => '立即同步'],\n]",
                    'page_code' => "Page::make('report_page')->action('sync', [\n    'title' => '立即同步',\n    'url' => 'admin/report/sync',\n    'ajax' => ['method' => 'post', 'no_refresh' => true, 'no_forward' => true],\n    'confirm' => ['title' => '同步确认', 'text' => '该操作会重新拉取远端统计结果。', 'confirmText' => '立即同步'],\n]);",
                ],
                [
                    'title' => '弹窗打开',
                    'summary' => '适合详情预览、选择器、说明页。',
                    'array_code' => "[\n    'title' => '查看详情',\n    'url' => 'admin/user/detail?id=1',\n    'pop' => ['title' => '详情', 'area' => ['960px', '720px']],\n]",
                    'page_code' => "Page::make('user_page')->action('detail', [\n    'title' => '查看详情',\n    'url' => 'admin/user/detail?id=1',\n    'pop' => ['title' => '详情', 'area' => ['960px', '720px']],\n]);",
                ],
                [
                    'title' => 'props 与新窗口',
                    'summary' => '适合挂额外前端配置，或明确要求新窗口打开。',
                    'array_code' => "[\n    'title' => '查看文档',\n    'url' => 'https://docs.example.com/page',\n    'target' => '_blank',\n    'props' => ['track' => 'page-doc'],\n]",
                    'page_code' => "Page::make('docs_page')->action('manual', [\n    'title' => '查看文档',\n    'url' => 'https://docs.example.com/page',\n    'target' => '_blank',\n    'props' => '{\"track\":\"page-doc\"}',\n]);",
                ],
            ],
        ]);
    }

    private function row(): array
    {
        return $this->wrap([
            'key' => 'row',
            'group_key' => 'layout',
            'title' => 'row() 单行布局',
            'signature' => "row(mixed \$content = [], array \$attr = [])",
            'summary' => '向页面追加一行内容，可直接承载 HTML、Form、Table、Chart、闭包或数组列配置。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '行内容，支持字符串、数组、渲染器对象、闭包等。'],
                ['name' => '$attr', 'summary' => 'row 容器属性，如 class、id。'],
            ],
            'array_code' => <<<'CODE'
[
    'row' => [
        ['<div class="card"><div class="card-body">今日订单 128</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">支付转化 21.6%</div></div>', 'md-8'],
        ['newline' => true],
        ['<div class="card"><div class="card-body">复购率 32.4%</div></div>', 'md-6'],
        ['<div class="card"><div class="card-body">退款率 1.2%</div></div>', 'md-6'],
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')
    ->row([
        ['<div class="card"><div class="card-body">今日订单 128</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">支付转化 21.6%</div></div>', 'md-8'],
        ['newline' => true],
        [fn (Page $page) => '<div class="card"><div class="card-body">闭包内可直接复用当前页面实例</div></div>', 'md-6'],
        ['<div class="card"><div class="card-body">退款率 1.2%</div></div>', 'md-6'],
    ], [
        'class' => 'g-3 align-items-stretch',
    ]);
CODE,
            'behavior_notes' => [
                '数组列中的第二个参数会被解释为相对 colClass 的后缀，如 md-4 => col col-md-4。',
                '传入 newline 标记后，会主动结束当前行并开始新的列布局，适合局部断行。',
                '闭包内容适合在组装行时临时读取页面上下文或拼接复杂片段。',
            ],
            'tips' => [
                '如果只是一两个并列卡片，优先直接用 row()；大批量均匀网格再用 grid()。',
            ],
            'usage_variants' => [
                [
                    'title' => '基础数组列',
                    'summary' => '最常见写法，适合并列卡片、统计块、双栏说明区。',
                    'array_code' => "[\n    ['卡片 A', 'md-4'],\n    ['卡片 B', 'md-8'],\n]",
                    'page_code' => "Page::make('dashboard')->row([\n    ['<div class=\"card\"><div class=\"card-body\">卡片 A</div></div>', 'md-4'],\n    ['<div class=\"card\"><div class=\"card-body\">卡片 B</div></div>', 'md-8'],\n]);",
                ],
                [
                    'title' => '局部换行',
                    'summary' => '前一组列铺满后，使用 newline 在同一个 row 数据块里切到下一排。',
                    'array_code' => "[\n    ['概览', 'md-6'],\n    ['趋势', 'md-6'],\n    ['newline' => true],\n    ['来源分布', 'md-12'],\n]",
                    'page_code' => "Page::make('dashboard')->row([\n    ['<div class=\"card\"><div class=\"card-body\">概览</div></div>', 'md-6'],\n    ['<div class=\"card\"><div class=\"card-body\">趋势</div></div>', 'md-6'],\n    ['newline' => true],\n    ['<div class=\"card\"><div class=\"card-body\">来源分布</div></div>', 'md-12'],\n]);",
                ],
                [
                    'title' => '闭包生成内容',
                    'summary' => '适合在构建过程中再决定片段内容，或复用当前 Page 上下文。',
                    'array_code' => "[\n    [callable, 'md-12'],\n]",
                    'page_code' => "Page::make('dashboard')->row([\n    [fn (Page \$page) => '<div class=\"card\"><div class=\"card-body\">当前标题：运营总览</div></div>', 'md-12'],\n]);",
                ],
            ],
        ]);
    }

    private function rows(): array
    {
        return $this->wrap([
            'key' => 'rows',
            'group_key' => 'layout',
            'title' => 'rows() 批量行',
            'signature' => "rows(array \$rows, array \$attr = [])",
            'summary' => '批量追加多行，适合先把页面结构按数组批量组织好，再一次性输出到 Page。尤其适合报表首页、看板页这类“块很多但结构规则”的页面。',
            'parameter_details' => [
                ['name' => '$rows', 'summary' => '多行数组。'],
                ['name' => '$attr', 'summary' => '默认行属性，会传给每一行。'],
            ],
            'array_code' => <<<'CODE'
[
    'rows' => [
        [
            ['<div class="card"><div class="card-body">概览卡片</div></div>', 'md-4'],
            ['<div class="card"><div class="card-body">趋势图表</div></div>', 'md-8'],
        ],
        [
            ['<div class="card"><div class="card-body">渠道来源</div></div>', 'md-6'],
            ['<div class="card"><div class="card-body">支付排行</div></div>', 'md-6'],
        ],
    ],
    'attr' => ['class' => 'g-3'],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')
    ->rows([
        [
            ['<div class="card"><div class="card-body">概览卡片</div></div>', 'md-4'],
            ['<div class="card"><div class="card-body">趋势图表</div></div>', 'md-8'],
        ],
        [
            ['<div class="card"><div class="card-body">渠道来源</div></div>', 'md-6'],
            ['<div class="card"><div class="card-body">支付排行</div></div>', 'md-6'],
        ],
    ], [
        'class' => 'g-3',
    ]);
CODE,
            'behavior_notes' => [
                'rows() 只是 row() 的批量封装，适合把页面结构预先组织成数据数组再统一输出。',
                '第二个参数会作为默认 row 属性传给每一行，因此非常适合统一设置 g-3、align-items-stretch 这类公共 class。',
            ],
            'tips' => [
                '如果每一行差异较大，直接链式多次调用 row() 会更好读。',
            ],
            'usage_variants' => [
                [
                    'title' => '批量组织规则看板',
                    'summary' => '适合把多行卡片先整理成数组，再由服务层或配置统一下发。',
                    'array_code' => "[\n    'rows' => [\n        [['概览', 'md-4'], ['趋势', 'md-8']],\n        [['来源', 'md-6'], ['排行', 'md-6']],\n    ],\n]",
                    'page_code' => "Page::make('dashboard')->rows([\n    [['<div class=\"card\"><div class=\"card-body\">概览</div></div>', 'md-4'], ['<div class=\"card\"><div class=\"card-body\">趋势</div></div>', 'md-8']],\n    [['<div class=\"card\"><div class=\"card-body\">来源</div></div>', 'md-6'], ['<div class=\"card\"><div class=\"card-body\">排行</div></div>', 'md-6']],\n]);",
                ],
                [
                    'title' => '统一行属性',
                    'summary' => '给所有 row 一次性叠加栅格间距或纵向拉伸类名。',
                    'array_code' => "[\n    'attr' => ['class' => 'g-3 align-items-stretch'],\n]",
                    'page_code' => "Page::make('dashboard')->rows(\$rows, [\n    'class' => 'g-3 align-items-stretch',\n]);",
                ],
            ],
        ]);
    }

    private function grid(): array
    {
        return $this->wrap([
            'key' => 'grid',
            'group_key' => 'layout',
            'title' => 'grid() 网格布局',
            'signature' => "grid(array \$items, int|array \$cols = 3, array \$attr = [])",
            'summary' => '把一组项目按固定列数或响应式断点自动拆成多行，是 Page 上最省心的多卡片布局方式。',
            'parameter_details' => [
                ['name' => '$items', 'summary' => '要均匀铺开的项目数组。'],
                ['name' => '$cols', 'summary' => '固定列数，或断点列数数组。'],
                ['name' => '$attr', 'summary' => 'row 属性。'],
            ],
            'array_code' => <<<'CODE'
[
    'items' => ['卡片 A', '卡片 B', '卡片 C', '卡片 D'],
    'cols' => ['md' => 2, 'xl' => 4],
    'row_class' => 'g-3 row-cols-md-2 row-cols-xl-4',
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')->grid([
    '<div class="card"><div class="card-body">卡片 A</div></div>',
    '<div class="card"><div class="card-body">卡片 B</div></div>',
    '<div class="card"><div class="card-body">卡片 C</div></div>',
    '<div class="card"><div class="card-body">卡片 D</div></div>',
], ['md' => 2, 'xl' => 4], [
    'class' => 'g-3',
]);
CODE,
            'behavior_notes' => [
                '固定整数列数会生成 row-cols-3 这类 class，断点数组会生成 row-cols-md-2 / row-cols-xl-4 这类响应式 class。',
                'grid() 内部会按最大列数对 items 做 array_chunk，再逐行转交给 row()，因此它适合均匀卡片，不适合需要精细断行控制的复杂混排。',
            ],
            'tips' => [
                '内容高度差异很大时，grid() 适合统计卡片，不适合需要严格对齐的复杂表单组合。',
            ],
            'usage_variants' => [
                [
                    'title' => '固定列数',
                    'summary' => '适合桌面端固定三列、四列的统计卡片宫格。',
                    'array_code' => "[\n    'cols' => 3,\n    'class' => 'g-3 row-cols-3',\n]",
                    'page_code' => "Page::make('dashboard')->grid(\$items, 3, [\n    'class' => 'g-3',\n]);",
                ],
                [
                    'title' => '响应式断点列数',
                    'summary' => '适合移动端单列、平板两列、桌面四列这类自适应场景。',
                    'array_code' => "[\n    'cols' => ['sm' => 1, 'md' => 2, 'xl' => 4],\n]",
                    'page_code' => "Page::make('dashboard')->grid(\$items, [\n    'sm' => 1,\n    'md' => 2,\n    'xl' => 4,\n], [\n    'class' => 'g-3',\n]);",
                ],
            ],
        ]);
    }

    private function tabs(): array
    {
        return $this->wrap([
            'key' => 'tabs',
            'group_key' => 'tabs',
            'title' => 'tabs() 页面级标签容器',
            'signature' => "tabs(array \$tabs = [], array \$options = [])",
            'summary' => '渲染页面级 tabs，支持 content 同页切换、url 跳转模式，以及两者混合。',
            'parameter_details' => [
                ['name' => '$tabs', 'summary' => '标签定义，key 建议使用稳定英文标识。'],
                ['name' => '$options', 'summary' => 'tabs 容器配置，如 active、remember、fill、class。'],
            ],
            'array_code' => <<<'CODE'
[
    'tabs' => [
        'base' => ['title' => '基础资料', 'form' => '$profileForm'],
        'security' => ['title' => '安全设置', 'form' => '$passwordForm'],
        'logs' => ['title' => '登录日志', 'url' => 'admin/user/logs'],
        'help' => ['title' => '使用说明', 'url' => 'admin/user/help', 'right' => true],
    ],
    'options' => [
        'id' => 'admin-profile-tabs',
        'active' => 'base',
        'remember' => true,
        'fill' => true,
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

$profileForm = '<div class="card"><div class="card-body">基础资料表单</div></div>';
$passwordForm = '<div class="card"><div class="card-body">安全设置表单</div></div>';

Page::make('user_profile')->tabs([
    'base' => [
        'title' => '基础资料',
        'form' => $profileForm,
    ],
    'security' => [
        'title' => '安全设置',
        'form' => $passwordForm,
    ],
    'logs' => [
        'title' => '登录日志',
        'url' => 'admin/user/logs',
    ],
    'help' => [
        'title' => '使用说明',
        'url' => 'admin/user/help',
        'right' => true,
    ],
], [
    'id' => 'admin-profile-tabs',
    'active' => 'base',
    'remember' => true,
    'fill' => true,
]);
CODE,
            'behavior_notes' => [
                '只要某个 tab 配了 url，就按 url 模式跳转；否则按 content 模式切换内容。',
                'remember 只对 content 模式生效，依赖 public/static/render/page/page.js。',
                'right 基于标签项的 right 标记和整体 options.right 协同工作，适合把“帮助”“说明”“预览站点”等次级入口推到右侧。',
            ],
            'tips' => [
                '页面级 tabs 适合“多个独立内容块切换”；表单内部的分组切换仍应使用 form tabs 组件。',
            ],
            'usage_variants' => [
                [
                    'title' => '纯 content 模式',
                    'summary' => '适合同一页面内切换多个独立表单、图表或说明块。',
                    'array_code' => "[\n    'tabs' => [\n        'base' => ['title' => '基础资料', 'form' => \$profileForm],\n        'security' => ['title' => '安全设置', 'form' => \$passwordForm],\n    ],\n    'options' => ['active' => 'base', 'remember' => true],\n]",
                    'page_code' => "Page::make('user_profile')->tabs([\n    'base' => ['title' => '基础资料', 'form' => \$profileForm],\n    'security' => ['title' => '安全设置', 'form' => \$passwordForm],\n], [\n    'active' => 'base',\n    'remember' => true,\n]);",
                ],
                [
                    'title' => '纯 URL 模式',
                    'summary' => '适合一个标签对应一个控制器页面，由当前路径自动判断激活项。',
                    'array_code' => "[\n    'tabs' => [\n        'base' => ['title' => '基本信息', 'url' => 'admin/user/index'],\n        'logs' => ['title' => '登录日志', 'url' => 'admin/user/logs'],\n    ],\n]",
                    'page_code' => "Page::make('user_profile')->tabs([\n    'base' => ['title' => '基本信息', 'url' => 'admin/user/index'],\n    'logs' => ['title' => '登录日志', 'url' => 'admin/user/logs'],\n]);",
                ],
                [
                    'title' => '混合模式与右侧标签',
                    'summary' => '适合把常用配置留在当前页切换，把说明/日志等入口跳到独立页面。',
                    'array_code' => "[\n    'tabs' => [\n        'base' => ['title' => '基础资料', 'form' => \$profileForm],\n        'logs' => ['title' => '登录日志', 'url' => 'admin/user/logs'],\n        'help' => ['title' => '帮助', 'url' => 'admin/user/help', 'right' => true],\n    ],\n    'options' => ['fill' => true],\n]",
                    'page_code' => "Page::make('user_profile')->tabs([\n    'base' => ['title' => '基础资料', 'form' => \$profileForm],\n    'logs' => ['title' => '登录日志', 'url' => 'admin/user/logs'],\n    'help' => ['title' => '帮助', 'url' => 'admin/user/help', 'right' => true],\n], [\n    'fill' => true,\n]);",
                ],
            ],
        ]);
    }

    private function assign(): array
    {
        return $this->wrap([
            'key' => 'assign',
            'group_key' => 'view',
            'title' => 'assign() 视图变量赋值',
            'signature' => "assign(string|array \$name, mixed \$value = null)",
            'summary' => '给 Think View 注入模板变量，适合先在控制器或 Page 中准备好片段数据，再交给 display()/fetch() 统一拼装。',
            'parameter_details' => [
                ['name' => '$name', 'summary' => '变量名，或变量数组。'],
                ['name' => '$value', 'summary' => '变量值。'],
            ],
            'array_code' => <<<'CODE'
[
    'summary_card' => '<div class="card"><div class="card-body">今日订单 128</div></div>',
    'summary_stats' => [
        'orders' => 128,
        'users' => 64,
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')
    ->assign('summaryCard', '<div class="card"><div class="card-body">今日订单 128</div></div>')
    ->assign('summaryStats', [
        'orders' => 128,
        'users' => 64,
    ]);
CODE,
            'behavior_notes' => [
                'assign() 赋给的是底层 view，而不是 Page::$vars 自身的页面结构变量。',
                '最常见的用法是先 assign 若干模板变量，再在 display()/fetch() 中通过模板语法引用。',
            ],
            'tips' => [
                '如果只是页面标题、行列或 tabs，不必绕到 assign()；只有模板片段需要变量时再用。',
            ],
            'usage_variants' => [
                [
                    'title' => '单变量赋值',
                    'summary' => '适合把一段已经拼好的 HTML 卡片或摘要块注入模板。',
                    'array_code' => "[\n    'summary_card' => '<div class=\"card\"><div class=\"card-body\">今日订单 128</div></div>',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->assign('summaryCard', '<div class=\"card\"><div class=\"card-body\">今日订单 128</div></div>');",
                ],
                [
                    'title' => '数组批量赋值',
                    'summary' => '当模板片段依赖多组数据时，用数组一次性传递更紧凑。',
                    'array_code' => "[\n    'summary_stats' => [\n        'orders' => 128,\n        'users' => 64,\n    ],\n]",
                    'page_code' => "Page::make('dashboard')\n    ->assign([\n        'summaryStats' => ['orders' => 128, 'users' => 64],\n        'highlightText' => '今日新增 18 位付费用户',\n    ]);",
                ],
            ],
        ]);
    }

    private function display(): array
    {
        return $this->wrap([
            'key' => 'display',
            'group_key' => 'view',
            'title' => 'display() 直接渲染内容',
            'signature' => "display(string \$content, array \$vars = [])",
            'summary' => '直接渲染一段模板字符串，适合临时片段、组合壳模板，或需要把 assign 变量与内联模板快速协同的场景。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '模板内容。'],
                ['name' => '$vars', 'summary' => '模板变量。'],
            ],
            'array_code' => <<<'CODE'
[
    'content' => '<section class="page-demo-shell">{$summaryCard|raw}<div class="alert alert-info mt-3 mb-0">今日用户 {$summaryStats.users} 位</div></section>',
]
CODE,
            'page_code' => <<<'CODE'
Page::make('dashboard')
    ->assign('summaryCard', '<div class="card"><div class="card-body">今日订单 128</div></div>')
    ->assign('summaryStats', [
        'orders' => 128,
        'users' => 64,
    ])
    ->display('<section class="page-demo-shell">{$summaryCard|raw}<div class="alert alert-info mt-3 mb-0">今日用户 {$summaryStats.users} 位</div></section>');
CODE,
            'behavior_notes' => [
                'display() 更接近视图层 API，通常不如 row()/grid() 直观，但在局部片段模板里很实用。',
                '它非常适合做“外层壳模板”，把 assign 准备好的变量插回一个小模板片段中。',
            ],
            'tips' => [
                '大段业务视图不要长期塞在 display() 字符串里，优先抽模板文件再用 fetch()。',
            ],
            'usage_variants' => [
                [
                    'title' => '直接传 vars',
                    'summary' => '适合临时片段，不必提前 assign。',
                    'array_code' => "[\n    'content' => '<div class=\"card\"><div class=\"card-body\">{\$summary.orders}</div></div>',\n    'vars' => ['summary' => ['orders' => 128]],\n]",
                    'page_code' => "Page::make('dashboard')\n    ->display('<div class=\"card\"><div class=\"card-body\">{\$summary.orders}</div></div>', [\n        'summary' => ['orders' => 128],\n    ]);",
                ],
                [
                    'title' => '配合 assign 组装壳模板',
                    'summary' => '适合先准备多个变量，再在一段小模板里统一引用。',
                    'array_code' => "[\n    'content' => '<section class=\"page-demo-shell\">{\$summaryCard|raw}</section>',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->assign('summaryCard', '<div class=\"card\"><div class=\"card-body\">今日订单 128</div></div>')\n    ->display('<section class=\"page-demo-shell\">{\$summaryCard|raw}</section>');",
                ],
            ],
        ]);
    }

    private function fetch(): array
    {
        return $this->wrap([
            'key' => 'fetch',
            'group_key' => 'view',
            'title' => 'fetch() 输出页面内容',
            'signature' => "fetch(string \$template = '', array \$vars = [])",
            'summary' => '解析模板并输出页面，是 Page 渲染器最终落地到 HTML 的出口方法。它既可以直接走当前默认布局，也可以临时指定模板并补充额外变量。',
            'parameter_details' => [
                ['name' => '$template', 'summary' => '模板文件名或模板路径。'],
                ['name' => '$vars', 'summary' => '额外模板变量。'],
            ],
            'array_code' => <<<'CODE'
[
    'template' => 'admin/report/index',
    'vars' => [
        'summary' => [
            'orders' => 128,
            'amount' => '32,560.00',
        ],
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

$page = Page::make('report_dashboard')
    ->title('运营总览')
    ->assign('summaryCard', '<div class="card"><div class="card-body">今日订单 128</div></div>')
    ->row([
        ['<div class="card"><div class="card-body">待支付 18 单</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">已支付 110 单</div></div>', 'md-8'],
    ]);

return $page->fetch('admin/report/index', [
    'summary' => [
        'orders' => 128,
        'amount' => '32,560.00',
    ],
]);
CODE,
            'behavior_notes' => [
                '控制器里最常见的是先组好 page，再 return $this->fetch() 或直接 return $page->fetch()。',
                '当传入 $template 时，会临时覆盖当前 Page 的模板路径；$vars 则会与页面内置变量合并后再进入视图。',
            ],
            'tips' => [
                '如果已经走框架控制器布局，通常不需要手动指定 template；showcase 手册这里只是说明能力范围。',
            ],
            'usage_variants' => [
                [
                    'title' => '直接使用默认布局',
                    'summary' => '最常见场景，页面结构已经全部通过 row/grid/tabs 组织完成。',
                    'array_code' => "[\n    'template' => '',\n]",
                    'page_code' => "return Page::make('dashboard')\n    ->title('运营总览')\n    ->row(['<div class=\"card\"><div class=\"card-body\">概览</div></div>', 'md-12'])\n    ->fetch();",
                ],
                [
                    'title' => '指定模板并补变量',
                    'summary' => '适合页面整体走 Page 布局，但局部仍需落到业务模板文件内处理。',
                    'array_code' => "[\n    'template' => 'admin/report/index',\n    'vars' => ['summary' => ['orders' => 128]],\n]",
                    'page_code' => "return \$page->fetch('admin/report/index', [\n    'summary' => ['orders' => 128, 'amount' => '32,560.00'],\n]);",
                ],
            ],
        ]);
    }

    private function filter(): array
    {
        return $this->wrap([
            'key' => 'filter',
            'group_key' => 'view',
            'title' => 'filter() 视图过滤器',
            'signature' => "filter(callable \$filter = null)",
            'summary' => '给底层视图输出注册过滤器，适合做模板输出前的最后一道文本处理，也常用于输出收口阶段统一裁剪空白、替换标记、清理调试注释或微调局部结构。',
            'parameter_details' => [
                ['name' => '$filter', 'summary' => '过滤回调。'],
            ],
            'array_code' => "[\n    'filter' => 'callable',\n]",
            'page_code' => <<<'CODE'
Page::make('dashboard')
    ->filter(static function (string $content): string {
        $content = trim($content);
        $content = str_replace('待替换标记', '已替换内容', $content);
        return preg_replace('/<!--debug-->.*?<!--\\/debug-->/s', '', $content) ?? $content;
    });
CODE,
            'behavior_notes' => [
                '这是偏底层能力，更多用于特殊模板输出场景，不是页面搭建的日常主入口。',
                '常见用法是对 display()/fetch() 产出的 HTML 做最后一步统一收口，而不是替代正常模板逻辑。',
            ],
            'tips' => [
                '除非确实要统一改写输出，否则不要在业务页面里滥用 filter()，可读性会下降。',
            ],
            'usage_variants' => [
                [
                    'title' => '统一裁剪与替换',
                    'summary' => '适合对输出结果做简单 trim、标记替换或增加测试用钩子。',
                    'array_code' => "[\n    'filter' => 'trim + replace',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->filter(static function (string \$content): string {\n        \$content = trim(\$content);\n        return str_replace('待替换标记', '已替换内容', \$content);\n    });",
                ],
                [
                    'title' => '移除调试注释',
                    'summary' => '适合模板阶段临时埋了 debug 标记，最终输出前统一清理。',
                    'array_code' => "[\n    'filter' => 'preg_replace debug block',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->filter(static function (string \$content): string {\n        return preg_replace('/<!--debug-->.*?<!--\\\\/debug-->/s', '', \$content) ?? \$content;\n    });",
                ],
            ],
        ]);
    }

    private function setColClass(): array
    {
        return $this->wrap([
            'key' => 'setColClass',
            'group_key' => 'layout',
            'title' => 'setColClass() 列 class 前缀',
            'signature' => "setColClass(string \$class)",
            'summary' => '修改列布局前缀，默认是 col。适合你已有一套自定义栅格命名，或想让 row()/grid() 统一输出特定列前缀，而不是默认的 Bootstrap col / row-cols 体系。',
            'parameter_details' => [
                ['name' => '$class', 'summary' => '列前缀 class。'],
            ],
            'array_code' => <<<'CODE'
[
    'col_class' => 'metric-col',
    'row' => [
        ['卡片 A', 'md-4'],
        ['卡片 B', 'md-8'],
    ],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')
    ->setColClass('metric-col')
    ->row([
        ['<div class="card"><div class="card-body">卡片 A</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">卡片 B</div></div>', 'md-8'],
    ]);
CODE,
            'behavior_notes' => [
                'row()/grid() 中的列宽字符串都会基于该前缀继续拼接，如 md-4 => col-md-4。',
                '如果项目本身就依赖 Bootstrap 默认 col / row-cols，通常不需要改；只有在自定义栅格体系下才建议使用。',
            ],
            'tips' => [
                '绝大多数场景保持默认 col 即可，这个方法更多是说明 Page 布局前缀可定制。',
            ],
            'usage_variants' => [
                [
                    'title' => '自定义 row 列前缀',
                    'summary' => '适合你已经封装了自己的列命名体系，例如 metric-col-md-4。',
                    'array_code' => "[\n    'col_class' => 'metric-col',\n]",
                    'page_code' => "Page::make('dashboard')\n    ->setColClass('metric-col')\n    ->row([\n        ['内容 A', 'md-4'],\n        ['内容 B', 'md-8'],\n    ]);",
                ],
                [
                    'title' => '与 grid 配套',
                    'summary' => '当 grid 也要跟着统一列前缀时，可以先切前缀再统一构建页面。',
                    'array_code' => "[\n    'col_class' => 'metric-col',\n    'grid_cols' => ['md' => 2, 'xl' => 4],\n]",
                    'page_code' => "Page::make('dashboard')\n    ->setColClass('metric-col')\n    ->grid([\n        '<div class=\"card\"><div class=\"card-body\">A</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">B</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">C</div></div>',\n        '<div class=\"card\"><div class=\"card-body\">D</div></div>',\n    ], ['md' => 2, 'xl' => 4]);",
                ],
            ],
        ]);
    }

    private function clear(): array
    {
        return $this->wrap([
            'key' => 'clear',
            'group_key' => 'layout',
            'title' => 'clear() 清空已追加行',
            'signature' => "clear()",
            'summary' => '清空当前页面已累计的 row 数据，便于复用同一个 Page 实例重新组织内容。',
            'parameter_details' => [],
            'array_code' => <<<'CODE'
[
    'title' => '复用页面实例',
    'clear' => true,
    'rows_before' => ['旧内容'],
    'rows_after' => ['新内容'],
]
CODE,
            'page_code' => <<<'CODE'
use app\common\render\Page;

$page = Page::make('dashboard_reuse')
    ->title('复用页面实例')
    ->action('refresh', [
        'title' => '刷新数据',
        'url' => 'admin/report/refresh',
        'ajax' => 'get',
    ])
    ->row([
        ['<div class="card"><div class="card-body">旧内容</div></div>', 'md-12'],
    ]);

$page->clear()
    ->row([
        ['<div class="card"><div class="card-body">新内容</div></div>', 'md-8'],
        ['<div class="card"><div class="card-body">补充说明</div></div>', 'md-4'],
    ]);
CODE,
            'behavior_notes' => [
                'clear() 会重置 dp_page_rows 与内部 rowIndex，但不会清除已经设置的标题、副标题和 action。',
            ],
            'tips' => [
                '如果不是在复用 Page 实例，通常不需要主动 clear()；showcase 主要用于说明其行为边界。',
            ],
            'usage_variants' => [
                [
                    'title' => '切换同一实例的内容方案',
                    'summary' => '适合一段逻辑先临时拼了旧结构，后面根据条件改成另一套行布局。',
                    'array_code' => "[\n    'clear' => true,\n    'rows_after' => ['新布局'],\n]",
                    'page_code' => "\$page->row(['旧布局'])\n    ->clear()\n    ->row(['新布局']);",
                ],
                [
                    'title' => '保留标题和操作区',
                    'summary' => '只替换正文，不重建标题、副标题、action 等页面级信息。',
                    'array_code' => "[\n    'title' => '复用页面实例',\n    'action' => 'refresh',\n    'clear' => true,\n]",
                    'page_code' => "\$page = Page::make('dashboard_reuse')\n    ->title('复用页面实例')\n    ->action('refresh', ['title' => '刷新数据', 'url' => 'admin/report/refresh']);\n\n\$page->clear()->row(['新内容']);",
                ],
            ],
        ]);
    }
}
