<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\form_builder;

/**
 * Showcase 表单构建器方法板块组装器
 */
final class FormBuilderMethodSectionBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $methodKey): array
    {
        return match ($methodKey) {
            'id' => $this->id(),
            'title' => $this->title(),
            'class' => $this->class(),
            'prop' => $this->prop(),
            'data' => $this->data(),
            'item' => $this->item(),
            'items' => $this->items(),
            'item_template' => $this->itemTemplate(),
            'header' => $this->header(),
            'header_action' => $this->headerAction(),
            'footer' => $this->footer(),
            'footer_action' => $this->footerAction(),
            'method' => $this->method(),
            'alert' => $this->alert(),
            'template' => $this->template(),
            'item_handler' => $this->itemHandler(),
            'btn_submit' => $this->btnSubmit(),
            'btn_back' => $this->btnBack(),
            'action' => $this->action(),
            'ajax' => $this->ajax(),
            'confirm' => $this->confirm(),
            'extra_html' => $this->extraHtml(),
            'extra_html_file' => $this->extraHtmlFile(),
            'extra_js' => $this->extraJs(),
            'extra_css' => $this->extraCss(),
            'js' => $this->js(),
            'css' => $this->css(),
            'sticky' => $this->sticky(),
            'assign' => $this->assign(),
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
            'variants' => [],
            'array_code' => '',
            'form_code' => '',
            'usage_variants' => [],
            'behavior_notes' => [],
            'tips' => [],
            'example_mode' => 'source',
            'preview_html' => '',
            'source_refs' => [
                [
                    'path' => 'app/common/render/Form.php',
                    'label' => 'Form 构建器源码',
                    'description' => '方法签名与行为均来自该类。',
                ],
            ],
        ], $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function id(): array
    {
        return $this->wrap([
            'key' => 'id',
            'group_key' => 'identity',
            'title' => 'id() 表单 DOM 标识',
            'signature' => "id(string \$value = '')",
            'summary' => '显式指定表单根节点 id，便于脚本挂载和页面定位。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '表单 DOM id。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'id' => 'site-settings-form',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->id('site-settings-form');
CODE,
            'behavior_notes' => [
                'DOM id 应保持页面内唯一，方便脚本选择器和自动化测试稳定定位。',
            ],
            'tips' => [
                '优先使用语义化命名，避免把随机字符串作为长期稳定的 DOM 标识。',
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function title(): array
    {
        return $this->wrap([
            'key' => 'title',
            'group_key' => 'identity',
            'title' => 'title() 表单标题',
            'signature' => "title(string \$value = '')",
            'summary' => '设置表单头部标题文本，用于页面主标题或卡片标题。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '显示在表单头部的标题文本。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'title' => '站点设置',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '基础设置')
    ->title('站点设置');
CODE,
            'behavior_notes' => [
                '标题会影响表单头部的可读性，适合与 header() 一起使用。',
            ],
            'tips' => [
                '后台长表单建议使用明确业务标题，而不是泛化的“编辑”或“设置”。',
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="card"><div class="card-header"><h3 class="card-title">站点设置</h3></div></div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function class(): array
    {
        return $this->wrap([
            'key' => 'class',
            'group_key' => 'identity',
            'title' => 'class() 根节点样式类',
            'signature' => "class(string \$value = '', bool \$replace = false)",
            'summary' => '给表单根节点追加或替换 class，适合业务化布局控制。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '附加的 class 字符串。'],
                ['name' => '$replace', 'summary' => '是否替换默认 dp-form class。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'class' => 'site-form compact',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->class('site-form compact');
CODE,
            'behavior_notes' => [
                '追加 class 比完全替换默认类更安全，通常能减少布局回归风险。',
            ],
            'tips' => [
                '只有在确定需要完全接管样式时再使用 $replace = true。',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prop(): array
    {
        return $this->wrap([
            'key' => 'prop',
            'group_key' => 'identity',
            'title' => 'prop() 原生属性透传',
            'signature' => "prop(string \$value = '')",
            'summary' => '透传表单根节点的原生 HTML 属性，适合挂载 data-* 标记。',
            'parameter_details' => [
                ['name' => '$value', 'summary' => '原生属性字符串。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'prop' => 'data-scene="site" novalidate',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->prop('data-scene="site" novalidate');
CODE,
            'behavior_notes' => [
                '适合挂载 data-* 元信息，不建议在这里堆叠复杂的内联行为。',
            ],
            'tips' => [
                '优先透传稳定属性，避免把动态脚本逻辑写进 HTML 属性字符串。',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return $this->wrap([
            'key' => 'data',
            'group_key' => 'data_items',
            'title' => 'data() 回填数据',
            'signature' => "data(array|object \$data = [])",
            'summary' => '批量注入回填数据，适合编辑页或设置页的初始值装载。',
            'parameter_details' => [
                ['name' => '$data', 'summary' => '数组或对象形式的表单回填数据。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'data' => [
        'site_name' => 'DolphinPHP AI',
        'seo_title' => '后台演示',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;
use app\common\render\form\Field;

Form::make('site_form', '站点设置')
    ->data([
        'site_name' => 'DolphinPHP AI',
        'seo_title' => '后台演示',
    ])
    ->item(Field::text('site_name', '站点名称'))
    ->item(Field::text('seo_title', 'SEO 标题'));
CODE,
            'behavior_notes' => [
                '字段名需要与 data 键名一致，否则不会自动回填。',
            ],
            'tips' => [
                '编辑态优先统一通过 data() 回填，避免每个 item 单独散落默认值。',
            ],
            'usage_variants' => [
                [
                    'title' => '数组回填',
                    'summary' => '最常见的编辑页写法，字段名和数组键名直接一一对应。',
                    'array_code' => <<<'CODE'
[
    'data' => [
        'site_name' => 'DolphinPHP AI',
        'seo_title' => '后台演示',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->data([
        'site_name' => 'DolphinPHP AI',
        'seo_title' => '后台演示',
    ]);
CODE,
                ],
                [
                    'title' => '对象回填',
                    'summary' => '当控制器里拿到的是模型或 DTO 对象时，可以直接传对象。',
                    'array_code' => <<<'CODE'
// data() 也支持对象，这里用示意写法表达：
$site = (object) [
    'site_name' => 'DolphinPHP AI',
    'seo_title' => '后台演示',
];
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

$site = (object) [
    'site_name' => 'DolphinPHP AI',
    'seo_title' => '后台演示',
];

Form::make('site_form', '站点设置')
    ->data($site);
CODE,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function item(): array
    {
        return $this->wrap([
            'key' => 'item',
            'group_key' => 'data_items',
            'title' => 'item() 单个表单项',
            'signature' => "item(mixed \$type, mixed \$name = '', string \$label = '', string \$tips = '', mixed \$value = '', mixed \$options = [])",
            'summary' => '按单项方式追加字段，适合链式构造精细控制。',
            'parameter_details' => [
                ['name' => '$type', 'summary' => '表单项类型、对象或闭包。'],
                ['name' => '$name', 'summary' => '字段名。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称'],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;
use app\common\render\form\Field;

Form::make('site_form', '站点设置')
    ->item(Field::text('site_name', '站点名称', '请输入站点名称'));
CODE,
            'behavior_notes' => [
                '单项追加适合与条件分支组合，便于按场景逐步组装表单。',
            ],
            'tips' => [
                '复杂业务场景里，优先用 item() 保持每个字段语义独立。',
            ],
            'usage_variants' => [
                [
                    'title' => 'Field 对象写法',
                    'summary' => '最推荐的现代写法，链式表达最清晰，也最贴近实际业务代码。',
                    'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称'],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;
use app\common\render\form\Field;

Form::make('site_form', '站点设置')
    ->item(Field::text('site_name', '站点名称', '请输入站点名称'));
CODE,
                ],
                [
                    'title' => '参数分散写法',
                    'summary' => '不依赖 Field 对象时，可以直接把 type、name、label 等参数分开传。',
                    'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称', 'DolphinPHP AI'],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->item('text', 'site_name', '站点名称', '请输入站点名称', 'DolphinPHP AI');
CODE,
                ],
                [
                    'title' => '数组定义写法',
                    'summary' => '字段定义已经在别处组装好时，可以直接传单个数组。',
                    'array_code' => <<<'CODE'
[
    [
        'type' => 'text',
        'name' => 'site_name',
        'label' => '站点名称',
        'tips' => '请输入站点名称',
        'value' => 'DolphinPHP AI',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->item([
        'type' => 'text',
        'name' => 'site_name',
        'label' => '站点名称',
        'tips' => '请输入站点名称',
        'value' => 'DolphinPHP AI',
    ]);
CODE,
                ],
                [
                    'title' => '闭包写法',
                    'summary' => '字段需要运行时根据上下文动态生成时，可以传闭包返回最终定义。',
                    'array_code' => <<<'CODE'
[
    function ($form) {
        return [
            'type' => 'text',
            'name' => 'site_name',
            'label' => '站点名称',
            'tips' => '请输入站点名称',
        ];
    },
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->item(function ($form) {
        return [
            'type' => 'text',
            'name' => 'site_name',
            'label' => '站点名称',
            'tips' => '请输入站点名称',
        ];
    });
CODE,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function items(): array
    {
        return $this->wrap([
            'key' => 'items',
            'group_key' => 'data_items',
            'title' => 'items() 批量表单项',
            'signature' => "items(array \$items = [])",
            'summary' => '批量追加字段列表，适合静态字段较多的设置页。',
            'parameter_details' => [
                ['name' => '$items', 'summary' => '列表形式的字段定义数组。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称'],
    ['text', 'seo_title', 'SEO 标题', '请输入 SEO 标题'],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->items([
        ['text', 'site_name', '站点名称', '请输入站点名称'],
        ['text', 'seo_title', 'SEO 标题', '请输入 SEO 标题'],
    ]);
CODE,
            'behavior_notes' => [
                '批量定义适合结构稳定的表单，动态场景仍建议回到 item()。',
            ],
            'tips' => [
                '字段过多时可以先用 items() 打底，再按条件追加 item()。',
            ],
            'usage_variants' => [
                [
                    'title' => '列表批量写法',
                    'summary' => '全部使用列表参数形式时最紧凑，适合静态设置页快速搭建。',
                    'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称'],
    ['text', 'seo_title', 'SEO 标题', '请输入 SEO 标题'],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->items([
        ['text', 'site_name', '站点名称', '请输入站点名称'],
        ['text', 'seo_title', 'SEO 标题', '请输入 SEO 标题'],
    ]);
CODE,
                ],
                [
                    'title' => '混合批量写法',
                    'summary' => 'items() 允许混合列表项和完整数组项，适合分阶段组装字段。',
                    'array_code' => <<<'CODE'
[
    ['text', 'site_name', '站点名称', '请输入站点名称'],
    [
        'type' => 'textarea',
        'name' => 'site_notice',
        'label' => '站点公告',
        'tips' => '请输入首页公告',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->items([
        ['text', 'site_name', '站点名称', '请输入站点名称'],
        [
            'type' => 'textarea',
            'name' => 'site_notice',
            'label' => '站点公告',
            'tips' => '请输入首页公告',
        ],
    ]);
CODE,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemTemplate(): array
    {
        return $this->wrap([
            'key' => 'item_template',
            'group_key' => 'data_items',
            'title' => 'itemTemplate() 字段模板映射',
            'signature' => "itemTemplate(string|array \$type = '', string \$template = '')",
            'summary' => '为指定表单项类型覆盖模板，适合统一扩展展示布局。',
            'parameter_details' => [
                ['name' => '$type', 'summary' => '字段类型或映射数组。'],
                ['name' => '$template', 'summary' => '模板路径。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'item_template' => [
        'text' => 'showcase/custom_text',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemTemplate('text', 'showcase/custom_text');
CODE,
            'behavior_notes' => [
                '模板映射会影响该类型字段的最终渲染结构，适合做全局一致化包装。',
            ],
            'tips' => [
                '覆盖前先确认模板变量契约一致，避免字段在运行时缺少必要上下文。',
            ],
            'usage_variants' => [
                [
                    'title' => '单类型写法',
                    'summary' => '给单个字段类型覆盖模板，适合小范围试点接管。',
                    'array_code' => <<<'CODE'
[
    'item_template' => [
        'text' => 'showcase/custom_text',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemTemplate('text', 'showcase/custom_text');
CODE,
                ],
                [
                    'title' => '数组映射写法',
                    'summary' => '一次性声明多个类型与模板映射，适合统一接管一组字段。',
                    'array_code' => <<<'CODE'
[
    'item_template' => [
        'text' => 'showcase/custom_text',
        'textarea' => 'showcase/custom_textarea',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemTemplate([
        'text' => 'showcase/custom_text',
        'textarea' => 'showcase/custom_textarea',
    ]);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function header(): array
    {
        return $this->wrap([
            'key' => 'header',
            'group_key' => 'layout',
            'title' => 'header() 头部区域',
            'signature' => "header(mixed \$content = true)",
            'summary' => '控制表单头部显示、隐藏或替换为自定义内容。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => 'true 显示默认头部，false 隐藏，字符串为自定义内容。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'header' => '站点设置向导',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->header('站点设置向导');
CODE,
            'behavior_notes' => [
                '当表单已位于独立页面主内容区时，可以通过 header(false) 精简重复标题。',
            ],
            'tips' => [
                '头部文案应服务于当前任务，不要把过多操作入口堆在 header 内容里。',
            ],
            'usage_variants' => [
                [
                    'title' => '默认头部',
                    'summary' => '传 true 时保留框架默认头部，适合常规后台编辑页。',
                    'array_code' => <<<'CODE'
[
    'header' => true,
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->header(true);
CODE,
                ],
                [
                    'title' => '自定义文案',
                    'summary' => '传字符串时可直接替换为更明确的业务标题。',
                    'array_code' => <<<'CODE'
[
    'header' => '站点设置向导',
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->header('站点设置向导');
CODE,
                ],
                [
                    'title' => '隐藏头部',
                    'summary' => '页面自身已经有主标题时，可直接隐藏表单头部避免重复。',
                    'array_code' => <<<'CODE'
[
    'header' => false,
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->header(false);
CODE,
                ],
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="card"><div class="card-header">站点设置向导</div></div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function headerAction(): array
    {
        return $this->wrap([
            'key' => 'header_action',
            'group_key' => 'layout',
            'title' => 'headerAction() 头部操作区',
            'signature' => "headerAction(mixed \$content = '', array \$params = [])",
            'summary' => '向表单头部右侧追加操作内容，可放按钮、图标、下拉菜单或自定义 HTML。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '操作类型、HTML 字符串，或由多个操作组成的数组。'],
                ['name' => '$params', 'summary' => '当 $content 为 btn、icon、close、dropdown 时使用的附加参数。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'header_action' => [
        [
            'type' => 'btn',
            'title' => '查看帮助',
            'icon' => 'ti ti-help',
        ],
        '<span class="text-secondary fs-12">仅管理员可见</span>',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->header('站点设置')
    ->headerAction('btn', [
        'title' => '查看帮助',
        'icon' => 'ti ti-help',
    ])
    ->headerAction('<span class="text-secondary fs-12">仅管理员可见</span>');
CODE,
            'behavior_notes' => [
                'headerAction() 支持按钮、图标、关闭按钮、下拉菜单和自定义 HTML，最终都会进入表单头部操作区。',
                '如果传数组，Form 会逐项递归处理，适合把一组头部操作集中声明。',
            ],
            'tips' => [
                '头部操作应服务于整张表单，不建议把字段级动作塞进这里。',
            ],
            'usage_variants' => [
                [
                    'title' => '字符串 HTML',
                    'summary' => '直接塞一段自定义 HTML，适合放文本说明或自定义徽标。',
                    'array_code' => <<<'CODE'
[
    'header_action' => [
        '<span class="text-secondary fs-12">仅管理员可见</span>',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->headerAction('<span class="text-secondary fs-12">仅管理员可见</span>');
CODE,
                ],
                [
                    'title' => '按钮类型 + 参数',
                    'summary' => '把按钮类型和附加参数拆开传，适合标准化头部动作。',
                    'array_code' => <<<'CODE'
[
    'header_action' => [
        [
            'type' => 'btn',
            'title' => '查看帮助',
            'icon' => 'ti ti-help',
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->headerAction('btn', [
        'title' => '查看帮助',
        'icon' => 'ti ti-help',
    ]);
CODE,
                ],
                [
                    'title' => '图标类型 + 参数',
                    'summary' => '只放一个头部图标时可直接用 icon 类型，通常适合放帮助或刷新入口。',
                    'array_code' => <<<'CODE'
[
    'header_action' => [
        [
            'type' => 'icon',
            'icon' => 'ti ti-refresh',
            'title' => '刷新配置缓存',
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->headerAction('icon', [
        'icon' => 'ti ti-refresh',
        'title' => '刷新配置缓存',
    ]);
CODE,
                ],
                [
                    'title' => '下拉菜单写法',
                    'summary' => '多个头部次级操作较多时，收进 dropdown 会比平铺按钮更克制。',
                    'array_code' => <<<'CODE'
[
    'header_action' => [
        [
            'type' => 'dropdown',
            'items' => [
                ['title' => '查看帮助', 'url' => '/admin/help/form'],
                ['title' => '查看接口', 'url' => '/admin/help/api'],
            ],
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->headerAction('dropdown', [
        ['title' => '查看帮助', 'url' => '/admin/help/form'],
        ['title' => '查看接口', 'url' => '/admin/help/api'],
    ]);
CODE,
                ],
                [
                    'title' => '数组批量定义',
                    'summary' => '一次性放入多个操作项，Form 会递归逐项处理。',
                    'array_code' => <<<'CODE'
[
    'header_action' => [
        'close',
        '<span class="text-secondary fs-12">仅管理员可见</span>',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->headerAction([
        'close',
        '<span class="text-secondary fs-12">仅管理员可见</span>',
    ]);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function footer(): array
    {
        return $this->wrap([
            'key' => 'footer',
            'group_key' => 'layout',
            'title' => 'footer() 底部区域开关',
            'signature' => "footer(mixed \$show = true)",
            'summary' => '控制表单底部操作区是否渲染，适合纯展示表单或自定义提交区场景。',
            'parameter_details' => [
                ['name' => '$show', 'summary' => 'true 显示底部，false 隐藏底部。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'footer' => false,
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footer(false);
CODE,
            'behavior_notes' => [
                'footer(false) 会直接隐藏默认底部操作区，常用于只读详情或页面已自带操作栏的场景。',
            ],
            'tips' => [
                '关闭底部前先确认提交入口是否还存在，避免开发者误以为表单无法保存。',
            ],
            'usage_variants' => [
                [
                    'title' => '显示底部',
                    'summary' => '默认写法，保留框架内置的底部按钮区。',
                    'array_code' => <<<'CODE'
[
    'footer' => true,
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footer(true);
CODE,
                ],
                [
                    'title' => '隐藏底部',
                    'summary' => '表单只作展示或页面自带外部操作栏时，可关闭默认底部区域。',
                    'array_code' => <<<'CODE'
[
    'footer' => false,
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footer(false);
CODE,
                ],
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="card"><div class="card-body text-secondary">底部操作区已关闭，本表单仅展示字段内容。</div></div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function footerAction(): array
    {
        return $this->wrap([
            'key' => 'footer_action',
            'group_key' => 'layout',
            'title' => 'footerAction() 底部附加操作',
            'signature' => "footerAction(mixed \$content = '', bool \$left = false)",
            'summary' => '在底部按钮区左侧或右侧追加补充内容，常用于说明、辅助链接或次级动作。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '附加内容，支持字符串或数组。'],
                ['name' => '$left', 'summary' => 'true 放左侧，false 放右侧。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'footer_action' => [
        'left' => '<span class="text-secondary fs-12">保存后 5 分钟内生效</span>',
        'right' => '<a class="btn btn-ghost-info" href="/admin/help/form">填写说明</a>',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footerAction('<span class="text-secondary fs-12">保存后 5 分钟内生效</span>', true)
    ->footerAction('<a class="btn btn-ghost-info" href="/admin/help/form">填写说明</a>');
CODE,
            'behavior_notes' => [
                'footerAction() 只是往底部左右操作槽位追加内容，不会替代默认提交按钮。',
                '当传数组时会逐项追加，适合补充多个说明或快捷入口。',
            ],
            'tips' => [
                '底部附加操作应保持次要地位，避免和主提交按钮抢视觉焦点。',
            ],
            'usage_variants' => [
                [
                    'title' => '左侧附加内容',
                    'summary' => '需要补充说明性文案时，通常放在左侧更像上下文提示。',
                    'array_code' => <<<'CODE'
[
    'footer_action' => [
        'left' => '<span class="text-secondary fs-12">保存后 5 分钟内生效</span>',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footerAction('<span class="text-secondary fs-12">保存后 5 分钟内生效</span>', true);
CODE,
                ],
                [
                    'title' => '数组批量追加',
                    'summary' => '多个次级操作都需要放同一侧时，可直接传数组批量追加。',
                    'array_code' => <<<'CODE'
[
    'footer_action' => [
        'right' => [
            '<a class="btn btn-ghost-info" href="/admin/help/form">填写说明</a>',
            '<a class="btn btn-ghost-secondary" href="/admin/help/api">接口文档</a>',
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->footerAction([
        '<a class="btn btn-ghost-info" href="/admin/help/form">填写说明</a>',
        '<a class="btn btn-ghost-secondary" href="/admin/help/api">接口文档</a>',
    ]);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function method(): array
    {
        return $this->wrap([
            'key' => 'method',
            'group_key' => 'request',
            'title' => 'method() 提交方式',
            'signature' => "method(string \$method = '', bool \$ajax = true)",
            'summary' => '设置表单的请求方法，并决定是否走 AJAX 提交。',
            'parameter_details' => [
                ['name' => '$method', 'summary' => 'HTTP 方法，常用 post 或 put。'],
                ['name' => '$ajax', 'summary' => '是否启用 AJAX 提交。'],
            ],
            'variants' => ['source', 'behavior'],
            'array_code' => <<<'CODE'
[
    'method' => 'post',
    'ajax' => true,
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->method('post', true);
CODE,
            'behavior_notes' => [
                'method() 会同时影响请求方法和 AJAX 提交标记，适合统一设置提交策略。',
            ],
            'tips' => [
                '和后端路由约定保持一致，避免前后端对提交方式理解不一致。',
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function alert(): array
    {
        return $this->wrap([
            'key' => 'alert',
            'group_key' => 'notice_assets',
            'title' => 'alert() 表单提示区',
            'signature' => "alert(string|array \$content = '', string \$title = '', string \$type = 'info', string \$pos = 'top')",
            'summary' => '在表单顶部或底部插入提示块，适合展示提交约束或业务说明。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '提示内容，支持字符串或数组。'],
                ['name' => '$title', 'summary' => '提示标题。'],
                ['name' => '$type', 'summary' => '提示类型，如 info、warning。'],
                ['name' => '$pos', 'summary' => '显示位置，top 或 bottom。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'alert' => [
        'content' => ['修改后立即生效', '请先保存备案信息'],
        'title' => '发布提醒',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->alert(['修改后立即生效', '请先保存备案信息'], '发布提醒', 'warning', 'top');
CODE,
            'behavior_notes' => [
                '适合放全局说明，不适合替代字段级校验提示。',
            ],
            'tips' => [
                '同一表单内提示块数量应克制，避免信息噪音盖过字段本身。',
            ],
            'usage_variants' => [
                [
                    'title' => '字符串内容写法',
                    'summary' => '单条提示内容最直接，适合放一句关键说明。',
                    'array_code' => <<<'CODE'
[
    'alert' => [
        'content' => '修改后立即生效',
        'title' => '发布提醒',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->alert('修改后立即生效', '发布提醒', 'warning', 'top');
CODE,
                ],
                [
                    'title' => '数组内容写法',
                    'summary' => '传内容数组时会逐条展开，适合把几条注意事项放在同一个提示块里。',
                    'array_code' => <<<'CODE'
[
    'alert' => [
        'content' => ['修改后立即生效', '请先保存备案信息'],
        'title' => '发布提醒',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->alert(['修改后立即生效', '请先保存备案信息'], '发布提醒', 'warning', 'top');
CODE,
                ],
                [
                    'title' => '带 icon / close 修饰',
                    'summary' => '类型字符串可附带 icon、close 修饰，直接控制提示块的图标和关闭按钮。',
                    'array_code' => <<<'CODE'
[
    'alert' => [
        'content' => '请先确认配置完整性',
        'title' => '发布提醒',
        'type' => 'warning:icon,close',
        'pos' => 'top',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->alert('请先确认配置完整性', '发布提醒', 'warning:icon,close', 'top');
CODE,
                ],
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="alert alert-warning"><strong>发布提醒</strong><br>修改后立即生效</div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function template(): array
    {
        return $this->wrap([
            'key' => 'template',
            'group_key' => 'advanced',
            'title' => 'template() 表单布局模板',
            'signature' => "template(string \$path = '')",
            'summary' => '切换表单整体布局模板，用于深度定制页面结构。',
            'parameter_details' => [
                ['name' => '$path', 'summary' => '模板名称或模板路径。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'template' => 'showcase/form/custom_layout',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->template('showcase/form/custom_layout');
CODE,
            'behavior_notes' => [
                '它会改变表单整体渲染布局，通常需要和现有模板变量契约保持一致。',
            ],
            'tips' => [
                '不建议为了小范围样式调整就覆盖整个模板，优先通过 class、extraHtml 或局部扩展解决。',
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemHandler(): array
    {
        return $this->wrap([
            'key' => 'item_handler',
            'group_key' => 'advanced',
            'title' => 'itemHandler() 表单项处理器映射',
            'signature' => "itemHandler(string|array \$type = '', string \$class = '', string \$template = '')",
            'summary' => '指定表单项类型对应的处理类与模板，实现扩展项接管。',
            'parameter_details' => [
                ['name' => '$type', 'summary' => '字段类型或映射数组。'],
                ['name' => '$class', 'summary' => '处理类名。'],
                ['name' => '$template', 'summary' => '可选模板路径。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'item_handler' => [
        'markdown' => [
            'class' => 'app\\showcase\\form\\MarkdownItem',
            'template' => 'showcase/form/markdown',
        ],
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemHandler('markdown', 'app\\showcase\\form\\MarkdownItem', 'showcase/form/markdown');
CODE,
            'behavior_notes' => [
                '适合把自定义类型接入 Form 渲染流程，是扩展表单项的重要入口。',
            ],
            'tips' => [
                '先保证处理类和模板契约完整，再接入 itemHandler()，否则运行时更难排查。',
            ],
            'usage_variants' => [
                [
                    'title' => '单类型写法',
                    'summary' => '先接入一个自定义类型，确认处理类和模板契约是否跑通。',
                    'array_code' => <<<'CODE'
[
    'item_handler' => [
        'markdown' => [
            'class' => 'app\\showcase\\form\\MarkdownItem',
            'template' => 'showcase/form/markdown',
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemHandler('markdown', 'app\\showcase\\form\\MarkdownItem', 'showcase/form/markdown');
CODE,
                ],
                [
                    'title' => '数组映射写法',
                    'summary' => '批量声明多个类型映射，适合扩展库或统一注册阶段。',
                    'array_code' => <<<'CODE'
[
    'item_handler' => [
        'markdown' => [
            'class' => 'app\\showcase\\form\\MarkdownItem',
            'template' => 'showcase/form/markdown',
        ],
        'json_editor' => [
            'class' => 'app\\showcase\\form\\JsonEditorItem',
            'template' => 'showcase/form/json_editor',
        ],
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->itemHandler([
        'markdown' => [
            'class' => 'app\\showcase\\form\\MarkdownItem',
            'template' => 'showcase/form/markdown',
        ],
        'json_editor' => [
            'class' => 'app\\showcase\\form\\JsonEditorItem',
            'template' => 'showcase/form/json_editor',
        ],
    ]);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function btnSubmit(): array
    {
        return $this->wrap([
            'key' => 'btn_submit',
            'group_key' => 'layout',
            'title' => 'btnSubmit() 提交按钮',
            'signature' => "btnSubmit(mixed \$title = '', string \$pos = 'right', string \$color = 'primary', string \$style = '')",
            'summary' => '配置默认提交按钮的文案、位置、颜色和样式。',
            'parameter_details' => [
                ['name' => '$title', 'summary' => '按钮文字。'],
                ['name' => '$pos', 'summary' => '按钮区域位置。'],
                ['name' => '$color', 'summary' => '按钮主题色。'],
                ['name' => '$style', 'summary' => '按钮样式，如 pill 或 square。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'btn_submit' => [
        'title' => '保存设置',
        'pos' => 'right',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->btnSubmit('保存设置', 'right', 'primary');
CODE,
            'behavior_notes' => [
                '提交按钮文案应明确动作结果，避免只写“确定”这种无业务语义的文字。',
            ],
            'tips' => [
                '主操作通常保持一个即可，过多主色按钮会削弱操作焦点。',
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="text-end"><button class="btn btn-primary">保存设置</button></div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function btnBack(): array
    {
        return $this->wrap([
            'key' => 'btn_back',
            'group_key' => 'layout',
            'title' => 'btnBack() 返回按钮',
            'signature' => "btnBack(mixed \$title = '', string \$pos = 'left', string \$color = 'ghost-info', string \$style = '')",
            'summary' => '配置默认返回按钮的文案、位置、配色和形态，常用于编辑页返回列表。',
            'parameter_details' => [
                ['name' => '$title', 'summary' => '按钮文字。'],
                ['name' => '$pos', 'summary' => '按钮位置，left 或 right。'],
                ['name' => '$color', 'summary' => '按钮主题色。'],
                ['name' => '$style', 'summary' => '按钮样式，如 pill 或 square。'],
            ],
            'variants' => ['preview', 'source'],
            'array_code' => <<<'CODE'
[
    'btn_back' => [
        'title' => '返回列表',
        'pos' => 'left',
        'color' => 'ghost-info',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->btnBack('返回列表', 'left', 'ghost-info');
CODE,
            'behavior_notes' => [
                '返回按钮通常位于左侧，与右侧的主提交按钮形成明确主次关系。',
            ],
            'tips' => [
                '返回动作建议保持稳定，不要复用成删除、重置等高风险操作。',
            ],
            'example_mode' => 'preview',
            'preview_html' => '<div class="d-flex justify-content-start"><button class="btn btn-ghost-info">返回列表</button></div>',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function action(): array
    {
        return $this->wrap([
            'key' => 'action',
            'group_key' => 'request',
            'title' => 'action() 提交地址',
            'signature' => "action(mixed \$url = '')",
            'summary' => '指定表单提交 URL，支持普通字符串，也支持框架 URL 构建对象。',
            'parameter_details' => [
                ['name' => '$url', 'summary' => '提交地址字符串，或可 build() 的 URL 对象。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'action' => '/admin/system.site/save',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;
use think\facade\Url;

Form::make('site_form', '站点设置')
    ->action(Url::build('/admin/system.site/save'));
CODE,
            'behavior_notes' => [
                'action() 决定提交目标地址，字符串会直接使用，URL 对象则会先 build() 再写入表单。',
            ],
            'tips' => [
                '提交地址应和后端接收动作保持一一对应，避免复用含糊的通用入口。',
            ],
            'usage_variants' => [
                [
                    'title' => '字符串 URL',
                    'summary' => '提交地址已明确时可以直接传字符串，最直观。',
                    'array_code' => <<<'CODE'
[
    'action' => '/admin/system.site/save',
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->action('/admin/system.site/save');
CODE,
                ],
                [
                    'title' => 'Url 对象',
                    'summary' => '需要依赖框架 URL 生成逻辑时，直接传 Url::build() 的结果更稳妥。',
                    'array_code' => <<<'CODE'
[
    'action' => 'think\\facade\\Url::build(...)',
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;
use think\facade\Url;

Form::make('site_form', '站点设置')
    ->action(Url::build('/admin/system.site/save'));
CODE,
                ],
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ajax(): array
    {
        return $this->wrap([
            'key' => 'ajax',
            'group_key' => 'request',
            'title' => 'ajax() 提交方式开关',
            'signature' => "ajax(bool \$ajax = true)",
            'summary' => '单独控制是否以 AJAX 方式提交表单，适合覆盖默认提交策略。',
            'parameter_details' => [
                ['name' => '$ajax', 'summary' => 'true 为 AJAX 提交，false 为普通表单提交。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'ajax' => false,
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->ajax(false);
CODE,
            'behavior_notes' => [
                'ajax() 只负责切换 AJAX 提交标记，不会自动修改请求地址或请求方法。',
                '当页面需要整页跳转、文件下载或依赖浏览器原生提交流程时，可以关闭 AJAX。',
            ],
            'tips' => [
                '如果 method() 已经显式传了 AJAX 标记，再单独调用 ajax() 时要注意最终以最后一次设置为准。',
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function confirm(): array
    {
        return $this->wrap([
            'key' => 'confirm',
            'group_key' => 'request',
            'title' => 'confirm() 提交确认弹窗',
            'signature' => "confirm(string|array \$title = '确认操作', string \$text = '确认要执行此操作吗？', string \$type = 'warning', string \$confirmText = '确认', string \$cancelText = '取消')",
            'summary' => '在提交前增加二次确认，适合高风险或不可逆操作。',
            'parameter_details' => [
                ['name' => '$title', 'summary' => '弹窗标题或完整配置数组。'],
                ['name' => '$text', 'summary' => '提示内容。'],
                ['name' => '$type', 'summary' => '弹窗类型。'],
                ['name' => '$confirmText', 'summary' => '确认按钮文案。'],
                ['name' => '$cancelText', 'summary' => '取消按钮文案。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'confirm' => [
        'title' => '确认发布',
        'text' => '发布后将立即对外生效',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->confirm('确认发布', '发布后将立即对外生效', 'warning', '立即发布', '稍后再说');
CODE,
            'behavior_notes' => [
                '确认弹窗适合高风险动作，不应滥用到所有普通保存操作。',
            ],
            'tips' => [
                '只有在误操作成本明显较高时再增加确认层，避免打断正常提交流程。',
            ],
            'usage_variants' => [
                [
                    'title' => '分散参数写法',
                    'summary' => '适合一次性声明标题、文案、类型和按钮文字。',
                    'array_code' => <<<'CODE'
[
    'confirm' => [
        'title' => '确认发布',
        'text' => '发布后将立即对外生效',
        'type' => 'warning',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->confirm('确认发布', '发布后将立即对外生效', 'warning', '立即发布', '稍后再说');
CODE,
                ],
                [
                    'title' => '完整数组写法',
                    'summary' => '当确认弹窗配置来自统一配置中心或变量时，直接传完整数组更方便。',
                    'array_code' => <<<'CODE'
[
    'confirm' => [
        'title' => '确认发布',
        'text' => '发布后将立即对外生效',
        'type' => 'warning',
        'confirmText' => '立即发布',
        'cancelText' => '稍后再说',
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->confirm([
        'title' => '确认发布',
        'text' => '发布后将立即对外生效',
        'type' => 'warning',
        'confirmText' => '立即发布',
        'cancelText' => '稍后再说',
    ]);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraHtml(): array
    {
        return $this->wrap([
            'key' => 'extra_html',
            'group_key' => 'notice_assets',
            'title' => 'extraHtml() 追加 HTML 片段',
            'signature' => "extraHtml(string \$content, string \$pos = 'top')",
            'summary' => '在表单顶部或底部插入额外 HTML 结构，适合放说明块、统计块或自定义辅助内容。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '要插入的 HTML 内容。'],
                ['name' => '$pos', 'summary' => '插入位置，通常为 top 或 bottom。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'extra_html' => [
        'top' => '<div class="alert alert-info mb-3">请先完成基础配置，再保存高级选项。</div>',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->extraHtml('<div class="alert alert-info mb-3">请先完成基础配置，再保存高级选项。</div>', 'top');
CODE,
            'behavior_notes' => [
                'extraHtml() 直接插入原始 HTML，适合展示结构化说明，但也意味着内容需要由开发者自己保证安全和样式一致性。',
            ],
            'tips' => [
                '只有在现有 alert() 或表单项无法表达时再用 extraHtml()，避免把整页结构逻辑散落进字符串。',
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraHtmlFile(): array
    {
        return $this->wrap([
            'key' => 'extra_html_file',
            'group_key' => 'notice_assets',
            'title' => 'extraHtmlFile() 引入 HTML 模板',
            'signature' => "extraHtmlFile(string \$template = '', string \$pos = 'top', array \$vars = [])",
            'summary' => '通过模板文件把额外内容插入表单顶部或底部，适合复用较长的说明块或复杂片段。',
            'parameter_details' => [
                ['name' => '$template', 'summary' => '模板名称或完整模板路径。'],
                ['name' => '$pos', 'summary' => '插入位置，通常为 top 或 bottom。'],
                ['name' => '$vars', 'summary' => '传给模板的变量。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'extra_html_file' => [
        'template' => 'form/site_tips',
        'pos' => 'top',
        'vars' => [
            'mode' => 'advanced',
        ],
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->extraHtmlFile('form/site_tips', 'top', [
        'mode' => 'advanced',
    ]);
CODE,
            'behavior_notes' => [
                '如果模板没有扩展名，Form 会按当前控制器目录自动拼装模板路径。',
                '模板不存在时，最终插入的是“模板文件不存在”提示文本，因此它更适合结合真实文件一起验证。',
            ],
            'tips' => [
                '片段较长或需要模板变量时优先用 extraHtmlFile()，不要把大段 HTML 字符串直接写进控制器。',
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraJs(): array
    {
        return $this->wrap([
            'key' => 'extra_js',
            'group_key' => 'notice_assets',
            'title' => 'extraJs() 追加内联脚本',
            'signature' => "extraJs(string \$content = '')",
            'summary' => '向当前表单页面追加内联 JavaScript，适合少量初始化或页面级交互补充。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '内联 JavaScript 内容。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'extra_js' => "document.addEventListener('DOMContentLoaded', () => console.log('form ready'));",
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->extraJs("document.addEventListener('DOMContentLoaded', () => console.log('form ready'));");
CODE,
            'behavior_notes' => [
                'extraJs() 会把脚本交给资源管理器作为内联 JS 输出，适合小范围页面增强。',
            ],
            'tips' => [
                '初始化逻辑较大时不建议堆在 extraJs()，应拆到独立 JS 文件并通过 js() 引入。',
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraCss(): array
    {
        return $this->wrap([
            'key' => 'extra_css',
            'group_key' => 'notice_assets',
            'title' => 'extraCss() 追加内联样式',
            'signature' => "extraCss(string \$content = '')",
            'summary' => '向当前表单页面追加内联 CSS，适合极小范围的临时样式修饰。',
            'parameter_details' => [
                ['name' => '$content', 'summary' => '内联 CSS 内容。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'extra_css' => '.site-form .form-label{letter-spacing:.08em;}',
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->extraCss('.site-form .form-label{letter-spacing:.08em;}');
CODE,
            'behavior_notes' => [
                'extraCss() 会作为内联样式输出，适合展示页或单页场景的局部修饰。',
            ],
            'tips' => [
                '可复用样式不建议长期放在 extraCss()，应沉淀到独立样式文件并通过 css() 引入。',
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function js(): array
    {
        return $this->wrap([
            'key' => 'js',
            'group_key' => 'notice_assets',
            'title' => 'js() 引入模块脚本',
            'signature' => "js(string|array \$files_name = '', string \$app = '')",
            'summary' => '按模块约定加载外部 JS 文件，适合页面级初始化逻辑或较长交互代码。',
            'parameter_details' => [
                ['name' => '$files_name', 'summary' => 'JS 文件名，支持字符串、逗号分隔字符串或数组。'],
                ['name' => '$app', 'summary' => '可选应用名，不传时使用当前应用上下文。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'js' => ['form-builder', 'common'],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->js(['form-builder', 'common'], 'showcase');
CODE,
            'behavior_notes' => [
                'js() 适合承载可复用的页面脚本资源，比 extraJs() 更容易维护和复用。',
            ],
            'tips' => [
                '一旦脚本会被多个页面复用，就优先改为 js() 资源文件，而不是继续扩写内联脚本。',
            ],
            'usage_variants' => [
                [
                    'title' => '单文件字符串',
                    'summary' => '只引一个模块脚本时最简洁。',
                    'array_code' => <<<'CODE'
[
    'js' => 'form-builder',
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->js('form-builder', 'showcase');
CODE,
                ],
                [
                    'title' => '数组批量引入',
                    'summary' => '多个脚本一起引入时可直接传数组，避免逗号字符串难维护。',
                    'array_code' => <<<'CODE'
[
    'js' => ['form-builder', 'common'],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->js(['form-builder', 'common'], 'showcase');
CODE,
                ],
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function css(): array
    {
        return $this->wrap([
            'key' => 'css',
            'group_key' => 'notice_assets',
            'title' => 'css() 引入模块样式',
            'signature' => "css(string|array \$files_name = '', string \$app = '')",
            'summary' => '按模块约定加载外部 CSS 文件，适合沉淀可复用的页面级或组件级样式。',
            'parameter_details' => [
                ['name' => '$files_name', 'summary' => 'CSS 文件名，支持字符串、逗号分隔字符串或数组。'],
                ['name' => '$app', 'summary' => '可选应用名，不传时使用当前应用上下文。'],
            ],
            'variants' => ['source'],
            'array_code' => <<<'CODE'
[
    'css' => ['form-builder', 'common'],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->css(['form-builder', 'common'], 'showcase');
CODE,
            'behavior_notes' => [
                'css() 适合沉淀页面样式资源，避免反复使用 extraCss() 写分散的内联样式。',
            ],
            'tips' => [
                '如果样式需要复用或需要配合缓存策略发布，就不建议继续留在 extraCss()。',
            ],
            'usage_variants' => [
                [
                    'title' => '单文件字符串',
                    'summary' => '只引一个页面样式时最直接。',
                    'array_code' => <<<'CODE'
[
    'css' => 'form-builder',
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->css('form-builder', 'showcase');
CODE,
                ],
                [
                    'title' => '数组批量引入',
                    'summary' => '多个样式模块一起引入时更适合用数组声明。',
                    'array_code' => <<<'CODE'
[
    'css' => ['form-builder', 'common'],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->css(['form-builder', 'common'], 'showcase');
CODE,
                ],
            ],
            'example_mode' => 'source',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sticky(): array
    {
        return $this->wrap([
            'key' => 'sticky',
            'group_key' => 'notice_assets',
            'title' => 'sticky() 吸附设置',
            'signature' => "sticky(mixed \$pos = '', bool|int \$num = 0)",
            'summary' => '控制表单顶部或底部的吸附行为，用于长表单中的固定操作区。',
            'parameter_details' => [
                ['name' => '$pos', 'summary' => '位置，可用 top、bottom，传 false 可整体关闭。'],
                ['name' => '$num', 'summary' => '距离偏移，布尔值或整数。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'sticky' => [
        'top' => 20,
        'bottom' => false,
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->sticky('top', 20);
CODE,
            'behavior_notes' => [
                'sticky() 通过位置键控制顶部或底部吸附，也支持传 false 一次性关闭全部吸附。',
            ],
            'tips' => [
                '吸附偏移值应和后台固定头部高度协调，否则会出现遮挡。',
            ],
            'usage_variants' => [
                [
                    'title' => '指定位置与偏移',
                    'summary' => '最常见写法是指定 top 或 bottom，再配一个偏移值。',
                    'array_code' => <<<'CODE'
[
    'sticky' => [
        'top' => 20,
    ],
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->sticky('top', 20);
CODE,
                ],
                [
                    'title' => '关闭全部吸附',
                    'summary' => '不希望顶部和底部都吸附时，直接传 false 最明确。',
                    'array_code' => <<<'CODE'
[
    'sticky' => false,
]
CODE,
                    'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->sticky(false);
CODE,
                ],
            ],
            'example_mode' => 'behavior',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assign(): array
    {
        return $this->wrap([
            'key' => 'assign',
            'group_key' => 'advanced',
            'title' => 'assign() 模板变量注入',
            'signature' => "assign(string \$name, mixed \$value = null)",
            'summary' => '向当前视图注入模板变量，供自定义模板、额外片段或布局层读取。',
            'parameter_details' => [
                ['name' => '$name', 'summary' => '模板变量名。'],
                ['name' => '$value', 'summary' => '模板变量值。'],
            ],
            'variants' => ['behavior', 'source'],
            'array_code' => <<<'CODE'
[
    'assign' => [
        'pageMode' => 'advanced',
        'helpLink' => '/admin/help/form-builder',
    ],
]
CODE,
            'form_code' => <<<'CODE'
use app\common\render\Form;

Form::make('site_form', '站点设置')
    ->assign('pageMode', 'advanced')
    ->assign('helpLink', '/admin/help/form-builder');
CODE,
            'behavior_notes' => [
                'assign() 本质上是给当前视图层注入模板变量，通常配合 template()、extraHtmlFile() 或自定义布局一起使用。',
                '它不会直接改变表单 UI，而是影响模板渲染阶段可读取到的上下文。',
            ],
            'tips' => [
                '不建议把主要业务数据传递链路建立在 assign() 上，表单回填数据仍应优先通过 data() 管理。',
            ],
            'example_mode' => 'behavior',
        ]);
    }
}
