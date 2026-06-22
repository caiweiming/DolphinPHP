<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select_group;

use app\common\render\Form;
use app\common\render\form\items\select_group\SelectGroup;
use app\common\render\form\items\text\Text;

/**
 * select_group 能力块构建器
 */
final class SelectGroupSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'disabled' => $this->disabled($section),
            'rounded' => $this->rounded($section),
            'notice_scale' => $this->scale($section),
            'notice' => $this->notice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'options', 'value' => '轻量标签式多选候选项']],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'tags',
        'label' => '标签',
        'options' => [
            'php' => 'PHP',
            'tp8' => 'ThinkPHP',
            'mysql' => 'MySQL',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('tags', '标签')
    ->options([
        'php' => 'PHP',
        'tp8' => 'ThinkPHP',
        'mysql' => 'MySQL',
    ]);
CODE,
            ['select_group 适合承载简洁、紧凑的固定标签选择场景。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_basic_', false), '基础标签多选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('tags', '标签')
                            ->options(['php' => 'PHP', 'tp8' => 'ThinkPHP', 'mysql' => 'MySQL'])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '数组形式回显多个标签项']],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'tech_tags',
        'label' => '技术栈',
        'value' => ['php', 'mysql'],
        'options' => [
            'php' => 'PHP',
            'tp8' => 'ThinkPHP',
            'mysql' => 'MySQL',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('tech_tags', '技术栈')
    ->options([
        'php' => 'PHP',
        'tp8' => 'ThinkPHP',
        'mysql' => 'MySQL',
    ])
    ->value(['php', 'mysql']);
CODE,
            ['固定候选项的编辑态回显通常直接复用 value 数组即可。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('tech_tags', '技术栈')
                            ->options(['php' => 'PHP', 'tp8' => 'ThinkPHP', 'mysql' => 'MySQL'])
                            ->value(['php', 'mysql'])
                    )
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '支持禁用全部或指定标签项']],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'category_tags',
        'label' => '分类标签',
        'disabled' => ['closed'],
        'options' => [
            'open' => '开放',
            'vip' => '会员',
            'closed' => '下线',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('category_tags', '分类标签')
    ->options([
        'open' => '开放',
        'vip' => '会员',
        'closed' => '下线',
    ])
    ->disabled(['closed']);
CODE,
            ['禁用标签项适合展示不可用但仍需保留可见性的候选项。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_disabled_', false), '禁用状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('category_tags', '分类标签')
                            ->options(['open' => '开放', 'vip' => '会员', 'closed' => '下线'])
                            ->disabled(['closed'])
                    )
                    ->fetch();
            }
        );
    }

    private function rounded(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'rounded', 'value' => '切换为胶囊式圆角标签']],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'rounded_tags',
        'label' => '圆角标签',
        'rounded' => true,
        'options' => [
            'basic' => '基础版',
            'pro' => '专业版',
            'enterprise' => '企业版',
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('rounded_tags', '圆角标签')
    ->rounded()
    ->options([
        'basic' => '基础版',
        'pro' => '专业版',
        'enterprise' => '企业版',
    ]);
CODE,
            ['rounded 能让标签式选择更接近“状态 pill”风格。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_rounded_', false), '圆角样式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('rounded_tags', '圆角标签')
                            ->rounded()
                            ->options(['basic' => '基础版', 'pro' => '专业版', 'enterprise' => '企业版'])
                    )
                    ->fetch();
            }
        );
    }

    private function scale(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => '候选项数量', 'value' => '建议控制在 3~8 个短标签内，便于快速扫视和点选'],
                ['name' => '选型建议', 'value' => '候选项过多、文案过长或需要搜索时，建议改用 select2'],
            ],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'scene_tags',
        'label' => '场景标签',
        'options' => [
            'cms' => '内容管理',
            'member' => '会员中心',
            'order' => '订单管理',
            'report' => '数据报表',
        ],
        'value' => ['cms', 'report'],
        'tips' => '这是 select_group 更合适的规模。若需要几十个选项或搜索能力，请改用 select2。',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('scene_tags', '场景标签')
    ->options([
        'cms' => '内容管理',
        'member' => '会员中心',
        'order' => '订单管理',
        'report' => '数据报表',
    ])
    ->value(['cms', 'report'])
    ->tips('这是 select_group 更合适的规模。若需要几十个选项或搜索能力，请改用 select2。');
CODE,
            [
                '这个板块的重点不是“功能限制”，而是把适合的选项规模直接展示出来。',
                '开发者如果发现候选项会持续增长，优先考虑切换到 select2，而不是继续堆叠 select_group。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_scale_', false), '选项规模建议')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('scene_tags', '场景标签')
                            ->options([
                                'cms' => '内容管理',
                                'member' => '会员中心',
                                'order' => '订单管理',
                                'report' => '数据报表',
                            ])
                            ->value(['cms', 'report'])
                            ->tips('这是 select_group 更合适的规模。若需要几十个选项或搜索能力，请改用 select2。')
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
                ['name' => '数组提交', 'value' => 'select_group 提交结果是数组'],
                ['name' => '落库建议', 'value' => '后端通常需要统一转 JSON 或逗号串存储，并保持候选值映射稳定'],
            ],
            <<<'CODE'
[
    [
        'type' => 'select_group',
        'name' => 'channel_tags',
        'label' => '渠道标签',
        'options' => [
            'mall' => '商城',
            'wechat' => '微信',
            'douyin' => '抖音',
        ],
        'value' => ['mall', 'wechat'],
        'tips' => 'select_group 提交为数组；建议统一落库格式；固定候选项适合集中映射管理',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::selectGroup('channel_tags', '渠道标签')
    ->options([
        'mall' => '商城',
        'wechat' => '微信',
        'douyin' => '抖音',
    ])
    ->value(['mall', 'wechat'])
    ->tips('select_group 提交为数组；建议统一落库格式；固定候选项适合集中映射管理');
CODE,
            [
                '这类多选标签和 tags 的差异，关键就在于固定候选项治理，所以这里应该直接展示真实 select_group。',
                '如果候选值会被后台配置反复调整，建议数据库里保存稳定 value，不要直接存展示文案。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_notice_', false), '值处理提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        SelectGroup::make('channel_tags', '渠道标签')
                            ->options([
                                'mall' => '商城',
                                'wechat' => '微信',
                                'douyin' => '抖音',
                            ])
                            ->value(['mall', 'wechat'])
                            ->tips('select_group 提交为数组；建议统一落库格式；固定候选项适合集中映射管理')
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
                ['name' => 'article_title', 'value' => '文章标题字段'],
                ['name' => 'article_tags', 'value' => '固定标签多选字段'],
            ],
            <<<'CODE'
[
    ['text', 'article_title', '文章标题', '请输入文章标题'],
    [
        'type' => 'select_group',
        'name' => 'article_tags',
        'label' => '标签分类',
        'rounded' => true,
        'options' => [
            'backend' => '后台管理',
            'ai' => 'AI 能力',
            'framework' => '框架设计',
        ],
        'value' => ['backend', 'framework'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('article_title', '文章标题', '请输入文章标题');

Field::selectGroup('article_tags', '标签分类')
    ->rounded()
    ->options([
        'backend' => '后台管理',
        'ai' => 'AI 能力',
        'framework' => '框架设计',
    ])
    ->value(['backend', 'framework']);
CODE,
            ['内容后台里常常会同时存在固定标签分类和自由标签输入，这一块适合和 tags 对照看。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_select_group_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('article_title', '文章标题', '请输入文章标题'))
                    ->item(
                        SelectGroup::make('article_tags', '标签分类')
                            ->rounded()
                            ->options(['backend' => '后台管理', 'ai' => 'AI 能力', 'framework' => '框架设计'])
                            ->value(['backend', 'framework'])
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
                'path' => 'app/showcase/service/components/form/select_group/SelectGroupSectionBuilder.php',
                'label' => 'select_group 能力块',
                'description' => '按 section key 组装 select_group 的完整示例能力块。',
            ]],
        ];
    }
}
