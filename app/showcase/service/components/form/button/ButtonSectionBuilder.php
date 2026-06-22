<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\button;

use app\common\render\Form;
use app\common\render\form\items\button\Button;
use app\common\render\form\items\html\Html;
use app\common\render\form\items\text\Text;

/**
 * button 能力块构建器
 */
final class ButtonSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'color_style' => $this->colorStyle($section),
            'icon' => $this->icon($section),
            'link' => $this->link($section),
            'shape' => $this->shape($section),
            'disabled' => $this->disabled($section),
            'svg' => $this->svg($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'color', 'value' => '默认 primary，可不显式配置']],
            <<<'CODE'
[
    [
        'type' => 'button',
        'name' => 'preview',
        'label' => '预览',
        'color' => 'primary',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('preview', '预览')
    ->color('primary');
CODE,
            [
                'button 默认就是一个独立操作按钮，不会自动提交表单。',
                '如果需要真正提交表单，应使用 submit 类组件或显式接入前端点击逻辑。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_basic_', false), '基础按钮')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Button::make('preview', '预览')->color('primary'))
                    ->fetch();
            }
        );
    }

    private function colorStyle(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'color', 'value' => '支持 primary / ghost-secondary / outline-warning 等风格'],
            ],
            <<<'CODE'
[
    ['type' => 'button', 'name' => 'save', 'label' => '保存', 'color' => 'success'],
    ['type' => 'button', 'name' => 'ghost', 'label' => '次级操作', 'color' => 'ghost-secondary'],
    ['type' => 'button', 'name' => 'outline', 'label' => '校验', 'color' => 'outline-warning'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('save', '保存')->color('success');
Field::button('ghost', '次级操作')->color('ghost-secondary');
Field::button('outline', '校验')->color('outline-warning');
CODE,
            ['颜色示例直接覆盖主题色、幽灵色和描边色，最方便开发者选型。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_color_', false), '颜色与样式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Button::make('save', '保存')->color('success'))
                    ->item(Button::make('ghost', '次级操作')->color('ghost-secondary'))
                    ->item(Button::make('outline', '校验')->color('outline-warning'))
                    ->fetch();
            }
        );
    }

    private function icon(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'icon', 'value' => '支持图标+文字或纯图标按钮']],
            <<<'CODE'
[
    ['type' => 'button', 'name' => 'edit', 'label' => '编辑', 'icon' => 'ti ti-edit'],
    ['type' => 'button', 'name' => 'refresh', 'label' => '', 'icon' => 'ti ti-refresh'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('edit', '编辑')
    ->icon('ti ti-edit');

Field::button('refresh', '')
    ->icon('ti ti-refresh');
CODE,
            ['纯图标按钮适合工具栏，小心避免在关键动作上只放图标不放文案。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_icon_', false), '图标按钮')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Button::make('edit', '编辑')->icon('ti ti-edit'))
                    ->item(Button::make('refresh', '')->icon('ti ti-refresh'))
                    ->fetch();
            }
        );
    }

    private function link(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'href / target', 'value' => '设置后会自动渲染为 a 链接按钮']],
            <<<'CODE'
[
    [
        'type' => 'button',
        'name' => 'docs',
        'label' => '查看文档',
        'href' => '/showcase/form',
        'target' => '_blank',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('docs', '查看文档')
    ->href('/showcase/form')
    ->target('_blank');
CODE,
            [
                'button 存在 href 时底层会自动切到链接模式，不需要再手动设 a=true。',
                '链接模式本质上仍是 a 标签，适合跳转、预览、打开帮助文档这类轻动作入口。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_link_', false), '链接按钮')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Button::make('docs', '查看文档')
                            ->href('/showcase/form')
                            ->target('_blank')
                    )
                    ->fetch();
            }
        );
    }

    private function shape(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'square / pill', 'value' => '提供方形与药丸形变体']],
            <<<'CODE'
[
    ['type' => 'button', 'name' => 'square', 'label' => '方形', 'shape' => 'square'],
    ['type' => 'button', 'name' => 'pill', 'label' => '药丸', 'shape' => 'pill'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('square', '方形')
    ->square();

Field::button('pill', '药丸')
    ->pill();
CODE,
            ['形状能力更多是视觉风格选择，常与图标按钮一起使用。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_shape_', false), '形状变体')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Button::make('square', '方形')->square())
                    ->item(Button::make('pill', '药丸')->pill())
                    ->fetch();
            }
        );
    }

    private function disabled(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'disabled', 'value' => '适合权限不足或流程未满足时的按钮展示']],
            <<<'CODE'
[
    [
        'type' => 'button',
        'name' => 'publish',
        'label' => '发布',
        'color' => 'secondary',
        'disabled' => true,
        'tips' => '当前缺少发布权限',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('publish', '发布')
    ->color('secondary')
    ->disabled()
    ->tips('当前缺少发布权限');
CODE,
            [
                '禁用按钮最好总配一条说明，告诉用户为什么此时不可操作。',
                'disabled 只是视觉态，底层并没有补原生 disabled 属性。',
                '当前 disabled 主要是视觉态 class，不会像原生 disabled 属性那样自动阻止点击事件。',
            ],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_disabled_', false), '禁用状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Button::make('publish', '发布')
                            ->color('secondary')
                            ->disabled()
                            ->tips('当前缺少发布权限')
                    )
                    ->fetch();
            }
        );
    }

    private function svg(array $section): array
    {
        return $this->wrap(
            $section,
            [['name' => 'svg', 'value' => '内联 SVG 会覆盖 icon 的渲染结果']],
            <<<'CODE'
[
    [
        'type' => 'button',
        'name' => 'home',
        'label' => '首页',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l7 -7l7 7"></path><path d="M9 21v-6h6v6"></path></svg>',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::button('home', '首页')
    ->svg('<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l7 -7l7 7"></path><path d="M9 21v-6h6v6"></path></svg>');
CODE,
            ['当需要完全自定义图标时，svg 比 icon class 更灵活。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_svg_', false), 'SVG 图标')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Button::make('home', '首页')
                            ->svg('<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l7 -7l7 7"></path><path d="M9 21v-6h6v6"></path></svg>')
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
                ['name' => '说明 + 预览按钮', 'value' => '真实表单中常见的辅助动作区'],
            ],
            <<<'CODE'
[
    [
        'type' => 'html',
        'name' => 'preview_notice',
        'label' => '预览说明',
        'value' => '<div class="alert alert-secondary mb-0">保存前可先预览当前配置效果</div>',
    ],
    ['type' => 'button', 'name' => 'preview', 'label' => '打开预览', 'color' => 'primary', 'icon' => 'ti ti-eye'],
    ['text', 'title', '标题', '请输入展示标题'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::html('preview_notice', '预览说明')
    ->value('<div class="alert alert-secondary mb-0">保存前可先预览当前配置效果</div>');

Field::button('preview', '打开预览')
    ->color('primary')
    ->icon('ti ti-eye');

Field::text('title', '标题', '请输入展示标题');
CODE,
            ['这类按钮常常不是最终提交动作，而是“辅助查看/预检/跳转”的轻动作。'],
            function (): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_button_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Html::make('preview_notice', '预览说明')
                            ->value('<div class="alert alert-secondary mb-0">保存前可先预览当前配置效果</div>')
                    )
                    ->item(
                        Button::make('preview', '打开预览')
                            ->color('primary')
                            ->icon('ti ti-eye')
                    )
                    ->item(Text::make('title', '标题', '请输入展示标题'))
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
                'path' => 'app/showcase/service/components/form/button/ButtonSectionBuilder.php',
                'label' => 'button 能力块',
                'description' => '按 section key 组装 button 的完整示例能力块。',
            ]],
        ];
    }
}
