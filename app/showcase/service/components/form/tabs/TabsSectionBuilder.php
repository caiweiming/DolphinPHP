<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\tabs;

use app\common\render\Form;
use app\common\render\form\Field;
use app\common\render\form\items\tabs\Tabs;

/**
 * tabs 能力块构建器
 */
final class TabsSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'icon' => $this->icon($section),
            'right' => $this->right($section),
            'fill' => $this->fill($section),
            'disabled' => $this->disabled($section),
            'form_items' => $this->formItems($section),
            'mixed_content' => $this->mixedContent($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options[].title / content', 'value' => '每个标签至少定义标题与内容']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'pay_tabs',
        'label' => '支付配置',
        'options' => [
            ['title' => '微信支付', 'content' => '<div>微信配置内容</div>'],
            ['title' => '支付宝支付', 'content' => '<div>支付宝配置内容</div>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('pay_tabs', '支付配置')
    ->options([
        ['title' => '微信支付', 'content' => '<div>微信配置内容</div>'],
        ['title' => '支付宝支付', 'content' => '<div>支付宝配置内容</div>'],
    ]);
CODE,
            ['tabs 组件的真实内容入口是 `options`，不是旧文档里的 `tabs()` 方法。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_basic_', false), '基础 HTML 标签页')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('pay_tabs', '支付配置')
                            ->options([
                                ['title' => '微信支付', 'content' => '<div>微信配置内容</div>'],
                                ['title' => '支付宝支付', 'content' => '<div>支付宝配置内容</div>'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function icon(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options[].icon', 'value' => '标签标题支持直接输出图标 HTML']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'channel_tabs',
        'label' => '渠道配置',
        'options' => [
            ['title' => '微信支付', 'icon' => '<i class="ti ti-brand-wechat"></i>', 'content' => '<div>微信配置</div>'],
            ['title' => '支付宝支付', 'icon' => '<i class="ti ti-brand-alipay"></i>', 'content' => '<div>支付宝配置</div>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('channel_tabs', '渠道配置')
    ->options([
        ['title' => '微信支付', 'icon' => '<i class="ti ti-brand-wechat"></i>', 'content' => '<div>微信配置</div>'],
        ['title' => '支付宝支付', 'icon' => '<i class="ti ti-brand-alipay"></i>', 'content' => '<div>支付宝配置</div>'],
    ]);
CODE,
            ['图标标签能明显提升多渠道、多平台配置页的识别效率。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_icon_', false), '图标标签页')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('channel_tabs', '渠道配置')
                            ->options([
                                ['title' => '微信支付', 'icon' => '<i class="ti ti-brand-wechat"></i>', 'content' => '<div>微信配置</div>'],
                                ['title' => '支付宝支付', 'icon' => '<i class="ti ti-brand-alipay"></i>', 'content' => '<div>支付宝配置</div>'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function right(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'right(true) / options[].right', 'value' => '支持整组右对齐或单个标签推右']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'ops_tabs',
        'label' => '操作配置',
        'options' => [
            ['title' => '基础设置', 'content' => '<div>基础设置</div>'],
            ['title' => '', 'icon' => '<i class="ti ti-settings"></i>', 'content' => '<div>高级设置</div>', 'right' => true],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('ops_tabs', '操作配置')
    ->options([
        ['title' => '基础设置', 'content' => '<div>基础设置</div>'],
        ['title' => '', 'icon' => '<i class="ti ti-settings"></i>', 'content' => '<div>高级设置</div>', 'right' => true],
    ]);
CODE,
            ['把设置类标签推到最右侧，是 tabs 里很实用的布局细节。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_right_', false), 'right 右侧标签')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('ops_tabs', '操作配置')
                            ->options([
                                ['title' => '基础设置', 'content' => '<div>基础设置</div>'],
                                ['title' => '', 'icon' => '<i class="ti ti-settings"></i>', 'content' => '<div>高级设置</div>', 'right' => true],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function fill(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'fill(true)', 'value' => '让标签头平均铺满容器宽度']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'full_tabs',
        'label' => '平均分布',
        'fill' => true,
        'options' => [
            ['title' => '站点', 'content' => '<div>站点设置</div>'],
            ['title' => '登录', 'content' => '<div>登录设置</div>'],
            ['title' => '安全', 'content' => '<div>安全设置</div>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('full_tabs', '平均分布')
    ->fill()
    ->options([
        ['title' => '站点', 'content' => '<div>站点设置</div>'],
        ['title' => '登录', 'content' => '<div>登录设置</div>'],
        ['title' => '安全', 'content' => '<div>安全设置</div>'],
    ]);
CODE,
            ['fill 更适合并列级别相同、数量固定的配置分组。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_fill_', false), 'fill 等宽标签')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('full_tabs', '平均分布')
                            ->fill()
                            ->options([
                                ['title' => '站点', 'content' => '<div>站点设置</div>'],
                                ['title' => '登录', 'content' => '<div>登录设置</div>'],
                                ['title' => '安全', 'content' => '<div>安全设置</div>'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '可禁用指定标签索引或全部标签']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'publish_tabs',
        'label' => '发布配置',
        'disabled' => [1],
        'options' => [
            ['title' => '基础信息', 'content' => '<div>基础内容</div>'],
            ['title' => '高级选项', 'content' => '<div>高级内容</div>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('publish_tabs', '发布配置')
    ->disabled([1])
    ->options([
        ['title' => '基础信息', 'content' => '<div>基础内容</div>'],
        ['title' => '高级选项', 'content' => '<div>高级内容</div>'],
    ]);
CODE,
            ['禁用标签适合某些能力尚未开放、但结构上先占位的页面。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_disabled_', false), 'disabled 禁用标签')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('publish_tabs', '发布配置')
                            ->disabled([1])
                            ->options([
                                ['title' => '基础信息', 'content' => '<div>基础内容</div>'],
                                ['title' => '高级选项', 'content' => '<div>高级内容</div>'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function formItems(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'content 表单项数组', 'value' => '可直接将表单项数组渲染进某个标签页']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'form_tabs',
        'label' => '表单分组',
        'options' => [
            [
                'title' => '账号信息',
                'content' => [
                    ['text', 'username', '用户名', '请输入用户名'],
                    ['password', 'password', '密码', '请输入密码'],
                ],
            ],
            ['title' => '说明', 'content' => '<div>这里只放静态说明</div>'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('form_tabs', '表单分组')
    ->options([
        [
            'title' => '账号信息',
            'content' => [
                ['text', 'username', '用户名', '请输入用户名'],
                ['password', 'password', '密码', '请输入密码'],
            ],
        ],
        ['title' => '说明', 'content' => '<div>这里只放静态说明</div>'],
    ]);
CODE,
            ['这是 tabs 和普通 HTML 容器最本质的区别之一：它能直接吃表单项数组。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_form_items_', false), '表单项数组内容')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('form_tabs', '表单分组')
                            ->options([
                                [
                                    'title' => '账号信息',
                                    'content' => [
                                        ['type' => 'text', 'name' => 'username', 'label' => '用户名', 'tips' => '请输入用户名'],
                                        ['type' => 'password', 'name' => 'password', 'label' => '密码', 'tips' => '请输入密码'],
                                    ],
                                ],
                                ['title' => '说明', 'content' => '<div>这里只放静态说明</div>'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function mixedContent(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'HTML + 表单混合', 'value' => '不同标签页可承载不同内容类型']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'mixed_tabs',
        'label' => '混合内容',
        'options' => [
            ['title' => '说明', 'content' => '<div class="alert alert-info mb-0">请先完成基础设置</div>'],
            [
                'title' => '基础设置',
                'content' => [
                    ['text', 'site_name', '站点名称', '请输入站点名称'],
                ],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('mixed_tabs', '混合内容')
    ->options([
        ['title' => '说明', 'content' => '<div class="alert alert-info mb-0">请先完成基础设置</div>'],
        [
            'title' => '基础设置',
            'content' => [
                ['text', 'site_name', '站点名称', '请输入站点名称'],
            ],
        ],
    ]);
CODE,
            ['这个能力块能帮助开发者理解 tabs 并不要求所有标签内容类型一致。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_mixed_', false), 'HTML 与表单混合内容')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('mixed_tabs', '混合内容')
                            ->options([
                                ['title' => '说明', 'content' => '<div class="alert alert-info mb-0">请先完成基础设置</div>'],
                                [
                                    'title' => '基础设置',
                                    'content' => [
                                        ['type' => 'text', 'name' => 'site_name', 'label' => '站点名称', 'tips' => '请输入站点名称'],
                                    ],
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '支付配置分组', 'value' => '多渠道、多分组配置的典型业务场景']],
            <<<'CODE'
[
    [
        'type' => 'tabs',
        'name' => 'payment_tabs',
        'label' => '支付配置',
        'options' => [
            [
                'title' => '微信支付',
                'icon' => '<i class="ti ti-brand-wechat"></i>',
                'content' => [
                    ['text', 'wechat_appid', 'AppID', '请输入微信 AppID'],
                    ['text', 'wechat_mchid', '商户号', '请输入微信商户号'],
                ],
            ],
            [
                'title' => '支付宝支付',
                'icon' => '<i class="ti ti-brand-alipay"></i>',
                'content' => [
                    ['text', 'alipay_appid', 'AppID', '请输入支付宝 AppID'],
                ],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tabs('payment_tabs', '支付配置')
    ->options([
        [
            'title' => '微信支付',
            'icon' => '<i class="ti ti-brand-wechat"></i>',
            'content' => [
                ['text', 'wechat_appid', 'AppID', '请输入微信 AppID'],
                ['text', 'wechat_mchid', '商户号', '请输入微信商户号'],
            ],
        ],
        [
            'title' => '支付宝支付',
            'icon' => '<i class="ti ti-brand-alipay"></i>',
            'content' => [
                ['text', 'alipay_appid', 'AppID', '请输入支付宝 AppID'],
            ],
        ],
    ]);
CODE,
            ['这是后台渠道配置场景里最有代表性的 tabs 用法之一。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tabs_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tabs::make('payment_tabs', '支付配置')
                            ->options([
                                [
                                    'title' => '微信支付',
                                    'icon' => '<i class="ti ti-brand-wechat"></i>',
                                    'content' => [
                                        ['type' => 'text', 'name' => 'wechat_appid', 'label' => 'AppID', 'tips' => '请输入微信 AppID'],
                                        ['type' => 'text', 'name' => 'wechat_mchid', 'label' => '商户号', 'tips' => '请输入微信商户号'],
                                    ],
                                ],
                                [
                                    'title' => '支付宝支付',
                                    'icon' => '<i class="ti ti-brand-alipay"></i>',
                                    'content' => [
                                        ['type' => 'text', 'name' => 'alipay_appid', 'label' => 'AppID', 'tips' => '请输入支付宝 AppID'],
                                    ],
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function wrap(array $section, array $params, string $arrayCode, string $fieldCode, array $notes, callable $previewBuilder): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? ''),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $previewBuilder(),
            'params' => $params,
            'array_code' => $arrayCode,
            'field_code' => $fieldCode,
            'notes' => $notes,
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/tabs/TabsSectionBuilder.php',
                'label' => 'tabs 能力块',
                'description' => '按 section key 组装 tabs 的完整示例能力块。',
            ]],
        ];
    }
}
