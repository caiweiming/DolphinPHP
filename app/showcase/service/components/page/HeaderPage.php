<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

/**
 * header 页面标题区详情页
 */
final class HeaderPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'basic-title',
                '基础标题与副标题',
                '最常见的页面头部写法，用 preTitle() 补充模块归属，用 title() 表达当前页面主题。',
                $this->renderPagePreview(function ($page): void {
                    $page->preTitle('Dashboard');
                    $page->title('运营总览');
                    $page->row([
                        ['<div class="card"><div class="card-body">今日订单 <strong>128</strong></div></div>', 'md-4'],
                        ['<div class="card"><div class="card-body">支付转化率 <strong>21.6%</strong></div></div>', 'md-8'],
                    ]);
                }),
                [
                    ['name' => 'preTitle', 'value' => "'Dashboard'"],
                    ['name' => 'title', 'value' => "'运营总览'"],
                ],
                [
                    '页面头部由 Page 统一渲染，下面的卡片内容仍然通过 row() 追加。',
                ],
                <<<'CODE'
[
    'pre_title' => 'Dashboard',
    'title' => '运营总览',
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('dashboard')
    ->preTitle('Dashboard')
    ->title('运营总览');
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'Page 标题逻辑', 'description' => 'title() / preTitle() 的实现。'],
                ]
            ),
            $this->makeSection(
                'title-default',
                'default 兜底标题',
                '当标题值可能为空时，可以用 title($value, $default) 保底展示页面名称。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('', '默认报表页');
                    $page->row([
                        ['<div class="card"><div class="card-body">当业务标题为空时，仍显示默认报表页。</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'title', 'value' => "('', '默认报表页')"],
                ],
                [
                    '适合页面标题来自配置或参数，但你仍希望头部保持稳定可见。',
                ],
                <<<'CODE'
[
    'title' => '',
    'title_default' => '默认报表页',
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('report_page')
    ->title('', '默认报表页');
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'title default 逻辑', 'description' => 'value 为空时如何使用 default。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => '页面标题区决定了后台页最上层的业务语义。它不负责承载内容块，但决定用户进入页面后第一眼看到的上下文。',
                'scenarios' => [
                    '需要清晰标识业务模块与当前页面主题的后台管理页',
                    '需要在页面头部挂载全局动作按钮的报表页、列表页、设置页',
                    '需要把同一类页面用统一标题规范组织起来的系统级页面',
                ],
                'capabilities' => ['title', 'preTitle', 'default 标题', '头部动作协同'],
                'quick_start' => [
                    'array_code' => "[\n    'pre_title' => 'Dashboard',\n    'title' => '运营总览',\n]",
                    'page_code' => "use app\\common\\render\\Page;\n\nPage::make('dashboard')\n    ->preTitle('Dashboard')\n    ->title('运营总览');",
                ],
            ],
            [
                [
                    'title' => '标题参数',
                    'items' => [
                        ['name' => 'title($value, $default)', 'summary' => '设置标题与兜底标题。'],
                        ['name' => 'preTitle($value)', 'summary' => '设置副标题。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}

