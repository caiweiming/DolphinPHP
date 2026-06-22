<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\hidden;

use app\common\render\Form;
use app\common\render\form\items\hidden\Hidden;
use app\common\render\form\items\static\StaticText;
use app\common\render\form\items\text\Text;

/**
 * hidden 能力块构建器
 */
final class HiddenSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'multiple_fields' => $this->multipleFields($section),
            'default_value' => $this->defaultValue($section),
            'edit_primary_key' => $this->editPrimaryKey($section),
            'security_notice' => $this->securityNotice($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'value', 'value' => '通过 value 指定需要提交的隐藏值']],
            <<<'CODE'
[
    [
        'type' => 'hidden',
        'name' => 'id',
        'value' => 10001,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('id')
    ->value(10001);
CODE,
            ['hidden 不显示在页面上，但会在提交时随表单一起带到后端。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_basic_', false), '基础隐藏值')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('id')->id('id_basic')->value(10001))
                    ->item(Text::make('title', '标题', '请输入标题')->id('title_basic'))
                    ->fetch();
            }
        );
    }

    private function multipleFields(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => '多个 hidden', 'value' => '同时传递类型、来源和租户上下文'],
            ],
            <<<'CODE'
[
    ['type' => 'hidden', 'name' => 'tenant_id', 'value' => 7],
    ['type' => 'hidden', 'name' => 'source', 'value' => 'showcase'],
    ['type' => 'hidden', 'name' => 'mode', 'value' => 'create'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('tenant_id')->value(7);
Field::hidden('source')->value('showcase');
Field::hidden('mode')->value('create');
CODE,
            ['多 hidden 组合是后台表单里最常见的上下文携带方式。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_multi_', false), '多字段传递')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('tenant_id')->id('tenant_id_multiple_fields')->value(7))
                    ->item(Hidden::make('source')->value('showcase'))
                    ->item(Hidden::make('mode')->value('create'))
                    ->item(Text::make('name', '名称', '请输入名称'))
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '编辑回填', 'value' => '通过 value 携带已存在的记录值']],
            <<<'CODE'
[
    [
        'type' => 'hidden',
        'name' => 'draft_token',
        'value' => 'draft_20260602_x1',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('draft_token')
    ->value('draft_20260602_x1');
CODE,
            ['hidden 的默认值与回填本质上是同一能力，都是在渲染时直接写入 value。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_default_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('draft_token')->value('draft_20260602_x1'))
                    ->item(Text::make('subject', '主题', '请输入主题'))
                    ->fetch();
            }
        );
    }

    private function editPrimaryKey(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'id + version', 'value' => '编辑态常一起携带主键和版本号'],
            ],
            <<<'CODE'
[
    ['type' => 'hidden', 'name' => 'id', 'value' => 501],
    ['type' => 'hidden', 'name' => 'version', 'value' => 3],
    ['text', 'title', '标题', '请输入文章标题'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('id')->value(501);
Field::hidden('version')->value(3);
Field::text('title', '标题', '请输入文章标题');
CODE,
            ['编辑页除主键外，常常还会携带 version、scene、source 等附加上下文。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_edit_', false), '编辑态主键传递')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('id')->id('id_edit_primary_key')->value(501))
                    ->item(Hidden::make('version')->value(3))
                    ->item(Text::make('title', '标题', '请输入文章标题')->id('title_edit_primary_key')->value('Showcase 组件说明'))
                    ->fetch();
            }
        );
    }

    private function securityNotice(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => '实现建议', 'value' => 'hidden 值不可直接信任，后端必须校验']],
            <<<'CODE'
[
    ['type' => 'hidden', 'name' => 'tenant_id', 'value' => 7],
    [
        'type' => 'static',
        'name' => 'hidden_notice',
        'label' => '安全提示',
        'value' => '当前表单同时携带 tenant_id=7；hidden 值可被篡改，后端应重新验证 id、tenant_id、status 等关键字段。',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('tenant_id')->value(7);

Field::static('hidden_notice', '安全提示')
    ->value('当前表单同时携带 tenant_id=7；hidden 值可被篡改，后端应重新验证 id、tenant_id、status 等关键字段。');
CODE,
            ['hidden 本身不可见，这里额外放一个 static 说明块，只是为了演示 hidden 已随表单提交，且其值不能被后端直接信任。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_notice_', false), '安全提示组合示例')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('tenant_id')->id('tenant_id_security_notice')->value(7))
                    ->item(
                        StaticText::make('hidden_notice', '安全提示')
                            ->value('当前表单同时携带 tenant_id=7；hidden 值可被篡改，后端应重新验证 id、tenant_id、status 等关键字段。')
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
                ['name' => 'id / tenant_id', 'value' => '业务上下文隐藏字段'],
                ['name' => 'title / slug', 'value' => '真实可编辑字段'],
            ],
            <<<'CODE'
[
    ['type' => 'hidden', 'name' => 'id', 'value' => 501],
    ['type' => 'hidden', 'name' => 'tenant_id', 'value' => 7],
    ['text', 'title', '文章标题', '请输入文章标题'],
    ['text', 'slug', '访问别名', '请输入访问别名'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::hidden('id')->value(501);
Field::hidden('tenant_id')->value(7);
Field::text('title', '文章标题', '请输入文章标题');
Field::text('slug', '访问别名', '请输入访问别名');
CODE,
            ['这是最接近真实编辑表单的 hidden 用法，开发者可以直接按业务字段替换。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_hidden_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Hidden::make('id')->id('id_profile_form')->value(501))
                    ->item(Hidden::make('tenant_id')->id('tenant_id_profile_form')->value(7))
                    ->item(Text::make('title', '文章标题', '请输入文章标题')->id('title_profile_form')->value('Showcase 示例应用'))
                    ->item(Text::make('slug', '访问别名', '请输入访问别名')->value('showcase-app'))
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
                'path' => 'app/showcase/service/components/form/hidden/HiddenSectionBuilder.php',
                'label' => 'hidden 能力块',
                'description' => '按 section key 组装 hidden 的完整示例能力块。',
            ]],
        ];
    }
}
