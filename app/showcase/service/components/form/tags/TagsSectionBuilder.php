<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\tags;

use app\common\render\Form;
use app\common\render\form\items\tags\Tags;
use app\common\render\form\items\text\Text;

/**
 * tags 能力块构建器
 */
final class TagsSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'placeholder_tips' => $this->placeholder($section),
            'readonly_default' => $this->readonlyDefault($section),
            'whitelist' => $this->whitelist($section),
            'style_size' => $this->style($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '支持数组形式默认标签']],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'tags',
        'label' => '标签',
        'value' => 'PHP,ThinkPHP',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('tags', '标签')
    ->value('PHP,ThinkPHP');
CODE,
            ['基础 tags 最适合让开发者直接看到“自由多标签输入”这件事本身。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_basic_', false), '基础标签输入')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('tags', '标签')
                            ->value('PHP,ThinkPHP')
                    )
                    ->fetch();
            }
        );
    }

    private function placeholder(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'placeholder', 'value' => '指导用户按回车录入标签'],
                ['name' => 'tips', 'value' => '补充数量或命名规则'],
            ],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'keywords',
        'label' => '关键词',
        'placeholder' => '输入后按回车添加关键词',
        'tips' => '建议控制在 5 个以内',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('keywords', '关键词')
    ->attr('placeholder', '输入后按回车添加关键词')
    ->tips('建议控制在 5 个以内');
CODE,
            ['tags 组件的学习成本主要在交互规则提示，所以 placeholder/tips 很关键。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_placeholder_', false), '提示与占位符')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('keywords', '关键词')
                            ->attr('placeholder', '输入后按回车添加关键词')
                            ->tips('建议控制在 5 个以内')
                    )
                    ->fetch();
            }
        );
    }

    private function readonlyDefault(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'readonly + value', 'value' => '展示已有标签且禁止编辑']],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'readonly_tags',
        'label' => '只读标签',
        'value' => 'DolphinPHP,Showcase',
        'readonly' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('readonly_tags', '只读标签')
    ->value('DolphinPHP,Showcase')
    ->readonly();
CODE,
            ['只读态常用于展示系统生成标签、同步标签或审核态标签。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_readonly_', false), '只读与默认值')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('readonly_tags', '只读标签')
                            ->value('DolphinPHP,Showcase')
                            ->readonly()
                    )
                    ->fetch();
            }
        );
    }

    private function whitelist(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'options.whitelist', 'value' => '限制标签候选范围，支持数组，也支持用逗号字符串传入'],
                ['name' => 'options.enforceWhitelist', 'value' => '开启后只能选择白名单中的标签'],
            ],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'skill_tags',
        'label' => '技能标签',
        'options' => [
            'whitelist' => 'PHP,ThinkPHP,MySQL,Redis',
            'enforceWhitelist' => true,
        ],
        'value' => 'PHP,MySQL',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('skill_tags', '技能标签')
    ->options([
        'whitelist' => 'PHP,ThinkPHP,MySQL,Redis',
        'enforceWhitelist' => true,
    ])
    ->value('PHP,MySQL');
CODE,
            [
                '白名单能力很适合内容治理、技能治理和运营标签治理场景。',
                '这里故意展示字符串写法，是为了让开发者知道底层会自动拆分成数组，不一定要手写 PHP 数组。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_whitelist_', false), '白名单选项')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('skill_tags', '技能标签')
                            ->options([
                                'whitelist' => 'PHP,ThinkPHP,MySQL,Redis',
                                'enforceWhitelist' => true,
                            ])
                            ->value('PHP,MySQL')
                    )
                    ->fetch();
            }
        );
    }

    private function style(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'rounded / flush / size', 'value' => '继承文本输入的视觉能力']],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'style_tags',
        'label' => '样式标签',
        'size' => 'sm',
        'class' => 'showcase-tags--compact',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('style_tags', '样式标签')
    ->small()
    ->class('showcase-tags--compact');
CODE,
            ['tags 在视觉上本质也是一个输入框，因此很多样式能力和 text 一样。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_style_', false), '样式与尺寸')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('style_tags', '样式标签')
                            ->small()
                            ->class('showcase-tags--compact')
                    )
                    ->fetch();
            }
        );
    }

    private function notice(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => '提交值结构', 'value' => '前端常以数组提交，编辑态也可用逗号字符串回填'],
                ['name' => '落库策略', 'value' => '建议统一转 JSON 或逗号串，避免同一字段出现多种存储格式'],
            ],
            <<<'CODE'
[
    [
        'type' => 'tags',
        'name' => 'topic_tags',
        'label' => '专题标签',
        'value' => '后台管理,组件示例,框架设计',
        'tips' => 'tags 通常以数组提交；白名单支持字符串写法；落库前建议统一转 JSON 或逗号串',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::tags('topic_tags', '专题标签')
    ->value('后台管理,组件示例,框架设计')
    ->tips('tags 通常以数组提交；白名单支持字符串写法；落库前建议统一转 JSON 或逗号串');
CODE,
            [
                '这里直接用 tags 本体承载“值结构提示”，开发者能同时看到输入形态和后端处理建议。',
                '如果编辑页回填用的是字符串，建议整个项目统一使用同一种分隔规则，避免历史数据混乱。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_notice_', false), '值处理提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Tags::make('topic_tags', '专题标签')
                            ->value('后台管理,组件示例,框架设计')
                            ->tips('tags 通常以数组提交；白名单支持字符串写法；落库前建议统一转 JSON 或逗号串')
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'title', 'value' => '文章标题字段'],
                ['name' => 'article_tags', 'value' => '自由标签输入字段'],
            ],
            <<<'CODE'
[
    ['text', 'title', '文章标题', '请输入文章标题'],
    [
        'type' => 'tags',
        'name' => 'article_tags',
        'label' => '文章标签',
        'tips' => '输入后按回车添加文章标签',
        'options' => [
            'whitelist' => ['后台管理', 'AI 能力', '框架设计', '组件示例'],
        ],
        'value' => '后台管理,组件示例',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('title', '文章标题', '请输入文章标题');

Field::tags('article_tags', '文章标签')
    ->tips('输入后按回车添加文章标签')
    ->options([
        'whitelist' => ['后台管理', 'AI 能力', '框架设计', '组件示例'],
    ])
    ->value('后台管理,组件示例');
CODE,
            ['这是内容管理后台中最常见的自由标签录入组合场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_tags_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('title', '文章标题', '请输入文章标题'))
                    ->item(
                        Tags::make('article_tags', '文章标签')
                            ->tips('输入后按回车添加文章标签')
                            ->options([
                                'whitelist' => ['后台管理', 'AI 能力', '框架设计', '组件示例'],
                            ])
                            ->value('后台管理,组件示例')
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
                'path' => 'app/showcase/service/components/form/tags/TagsSectionBuilder.php',
                'label' => 'tags 能力块',
                'description' => '按 section key 组装 tags 的完整示例能力块。',
            ]],
        ];
    }
}
