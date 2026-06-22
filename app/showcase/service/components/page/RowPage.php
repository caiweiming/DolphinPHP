<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

/**
 * row 页面详情页
 */
final class RowPage extends AbstractPageComponent
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sections = [
            $this->makeSection(
                'single-row',
                '一行多列',
                'row() 最直接的用法：一行里承载多个卡片或渲染器块，并通过列宽控制比例。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('单行布局示例');
                    $page->row([
                        ['<div class="card"><div class="card-body">概览卡片</div></div>', 'md-3'],
                        ['<div class="card"><div class="card-body">趋势卡片</div></div>', 'md-6'],
                        ['<div class="card"><div class="card-body">提醒卡片</div></div>', 'md-3'],
                    ]);
                }),
                [
                    ['name' => 'content', 'value' => '数组列配置'],
                    ['name' => 'class', 'value' => "'md-3' / 'md-6'"],
                ],
                [
                    '列宽字符串最终会基于默认 col 前缀转换成 col-md-3、col-md-6 等样式。',
                ],
                <<<'CODE'
[
    [
        ['<div class="card"><div class="card-body">概览卡片</div></div>', 'md-3'],
        ['<div class="card"><div class="card-body">趋势卡片</div></div>', 'md-6'],
        ['<div class="card"><div class="card-body">提醒卡片</div></div>', 'md-3'],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('row_demo')
    ->row([
        ['<div class="card"><div class="card-body">概览卡片</div></div>', 'md-3'],
        ['<div class="card"><div class="card-body">趋势卡片</div></div>', 'md-6'],
        ['<div class="card"><div class="card-body">提醒卡片</div></div>', 'md-3'],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'row() 实现', 'description' => 'Page::row 的列解析和行属性组装。'],
                ]
            ),
            $this->makeSection(
                'rows-batch',
                'rows() 批量追加',
                '当页面结构更像一组数据清单时，可以先组织 rows 数组，再一次性输出多行。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('批量行示例');
                    $page->rows([
                        [
                            ['<div class="card"><div class="card-body">第 1 行：数据概览</div></div>', 'md-6'],
                            ['<div class="card"><div class="card-body">第 1 行：趋势摘要</div></div>', 'md-6'],
                        ],
                        [
                            ['<div class="card"><div class="card-body">第 2 行：渠道拆分</div></div>', 'md-4'],
                            ['<div class="card"><div class="card-body">第 2 行：订单热力</div></div>', 'md-8'],
                        ],
                    ]);
                }),
                [
                    ['name' => 'rows', 'value' => '二维数组'],
                ],
                [
                    'rows() 内部仍然逐行调用 row()，只是更适合从配置或循环中批量组织结构。',
                ],
                <<<'CODE'
[
    [
        ['<div class="card"><div class="card-body">第 1 行：数据概览</div></div>', 'md-6'],
        ['<div class="card"><div class="card-body">第 1 行：趋势摘要</div></div>', 'md-6'],
    ],
    [
        ['<div class="card"><div class="card-body">第 2 行：渠道拆分</div></div>', 'md-4'],
        ['<div class="card"><div class="card-body">第 2 行：订单热力</div></div>', 'md-8'],
    ],
]
CODE,
                <<<'CODE'
use app\common\render\Page;

Page::make('rows_demo')
    ->rows([
        [
            ['<div class="card"><div class="card-body">第 1 行：数据概览</div></div>', 'md-6'],
            ['<div class="card"><div class="card-body">第 1 行：趋势摘要</div></div>', 'md-6'],
        ],
        [
            ['<div class="card"><div class="card-body">第 2 行：渠道拆分</div></div>', 'md-4'],
            ['<div class="card"><div class="card-body">第 2 行：订单热力</div></div>', 'md-8'],
        ],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'rows() 实现', 'description' => '批量行的封装。'],
                ]
            ),
            $this->makeSection(
                'clear-rebuild',
                'clear() 与 setColClass()',
                'clear() 可以清空已追加行，setColClass() 则决定后续列宽字符串的前缀解释方式。',
                $this->renderPagePreview(function ($page): void {
                    $page->title('clear 与 setColClass');
                    $page->row([
                        ['<div class="card"><div class="card-body">这行会先被清空</div></div>', 'md-12'],
                    ]);
                    $page->clear();
                    $page->setColClass('col');
                    $page->row([
                        ['<div class="card border-primary"><div class="card-body">clear() 后重新组织页面</div></div>', 'md-12'],
                    ]);
                }),
                [
                    ['name' => 'clear', 'value' => 'true'],
                    ['name' => 'setColClass', 'value' => "'col'"],
                ],
                [
                    'clear() 不会清掉标题和 actions，只会清空累计的 dp_page_rows。',
                ],
                <<<'CODE'
[
    'clear' => true,
    'set_col_class' => 'col',
]
CODE,
                <<<'CODE'
use app\common\render\Page;

$page = Page::make('clear_demo')
    ->row([
        ['<div class="card"><div class="card-body">这行会先被清空</div></div>', 'md-12'],
    ]);

$page->clear()
    ->setColClass('col')
    ->row([
        ['<div class="card border-primary"><div class="card-body">clear() 后重新组织页面</div></div>', 'md-12'],
    ]);
CODE,
                [
                    ['path' => 'app/common/render/Page.php', 'label' => 'clear / setColClass', 'description' => '行缓存与列前缀设置。'],
                ]
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'row()/rows() 是 Page 渲染器的基本功。无论后面承载的是表单、表格、图表还是自定义 HTML，最终都要落在页面行列布局上。',
                'scenarios' => [
                    '承载多个统计卡片的概览页',
                    '在一个页面中组合 Form、Table、Chart 等多个渲染器',
                    '需要手动微调列宽比例的后台页面',
                ],
                'capabilities' => ['row', 'rows', 'setColClass', 'clear'],
                'quick_start' => [
                    'array_code' => "[\n    ['<div class=\"card\"><div class=\"card-body\">卡片 A</div></div>', 'md-4'],\n    ['<div class=\"card\"><div class=\"card-body\">卡片 B</div></div>', 'md-8'],\n]",
                    'page_code' => "use app\\common\\render\\Page;\n\nPage::make('row_demo')\n    ->row([\n        ['<div class=\"card\"><div class=\"card-body\">卡片 A</div></div>', 'md-4'],\n        ['<div class=\"card\"><div class=\"card-body\">卡片 B</div></div>', 'md-8'],\n    ]);",
                ],
            ],
            [
                [
                    'title' => '布局参数',
                    'items' => [
                        ['name' => 'row($content, $attr)', 'summary' => '追加单行内容。'],
                        ['name' => 'rows($rows, $attr)', 'summary' => '批量追加多行。'],
                        ['name' => 'setColClass($class)', 'summary' => '设置列 class 前缀。'],
                        ['name' => 'clear()', 'summary' => '清空已追加行。'],
                    ],
                ],
            ],
            $sections,
            $this->relatedComponents((string) ($component['key'] ?? ''))
        );
    }
}
