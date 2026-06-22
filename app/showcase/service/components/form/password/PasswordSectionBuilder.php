<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\password;

use app\common\render\Form;
use app\common\render\form\items\password\Password;
use app\common\render\form\items\text\Text;

/**
 * password 能力块构建器
 */
final class PasswordSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'placeholder_tips' => $this->placeholder($section),
            'length_limit' => $this->length($section),
            'switch_strength' => $this->switchStrength($section),
            'prefix_style' => $this->style($section),
            'state' => $this->state($section),
            'validation' => $this->validation($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'name', 'value' => 'password / api_secret'],
                ['name' => 'type', 'value' => '自动渲染为 password 输入框'],
            ],
            <<<'CODE'
[
    ['password', 'password', '密码', '请输入密码'],
    [
        'type' => 'password',
        'name' => 'api_secret',
        'label' => '访问密钥',
        'tips' => '用于开放接口鉴权',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('password', '密码', '请输入密码');
Field::password('api_secret', '访问密钥', '用于开放接口鉴权');
CODE,
            ['基础 password 适合登录、注册、密钥配置等最常见的敏感单行输入场景。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '基础密码输入能力块',
            '组装 password 的基础输入、扩展能力与业务示例。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_basic_', false), '基础密码输入')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Password::make('password', '密码', '请输入密码')->id('password_basic'))
                    ->item(Password::make('api_secret', '访问密钥', '用于开放接口鉴权'))
                    ->fetch();
            }
        );
    }

    private function placeholder(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'placeholder', 'value' => '提示用户输入规则'],
                ['name' => 'tips', 'value' => '在输入框下方补充安全要求'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'new_password',
        'label' => '新密码',
        'placeholder' => '请输入 8-20 位新密码',
        'tips' => '建议包含大小写字母、数字和特殊字符',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('new_password', '新密码')
    ->placeholder('请输入 8-20 位新密码')
    ->tips('建议包含大小写字母、数字和特殊字符');
CODE,
            ['密码字段的引导文案通常比普通文本更重要，能直接降低弱口令输入概率。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '占位符与提示能力块',
            '展示 password 的 placeholder 与 tips 组合写法。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_placeholder_', false), '占位符与提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('new_password', '新密码')
                            ->placeholder('请输入 8-20 位新密码')
                            ->tips('建议包含大小写字母、数字和特殊字符')
                    )
                    ->fetch();
            }
        );
    }

    private function length(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'min_length', 'value' => '最小长度 8'],
                ['name' => 'max_length', 'value' => '最大长度 20'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'secure_password',
        'label' => '安全密码',
        'min_length' => 8,
        'max_length' => 20,
        'tips' => '密码长度限制为 8-20 位',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('secure_password', '安全密码')
    ->minLength(8)
    ->maxLength(20)
    ->tips('密码长度限制为 8-20 位');
CODE,
            ['长度限制适合和服务端校验规则保持一致，避免前后端要求不统一。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '长度限制能力块',
            '展示 password 的长度约束配置。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_length_', false), '长度限制')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('secure_password', '安全密码')
                            ->minLength(8)
                            ->maxLength(20)
                            ->tips('密码长度限制为 8-20 位')
                    )
                    ->fetch();
            }
        );
    }

    private function switchStrength(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'switch', 'value' => '显示/隐藏密码切换按钮'],
                ['name' => 'strength', 'value' => '密码强度提示条'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'register_password',
        'label' => '注册密码',
        'switch' => true,
        'strength' => true,
        'min_length' => 8,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('register_password', '注册密码')
    ->switch()
    ->strength()
    ->minLength(8);
CODE,
            [
                '注册、重置密码场景通常建议同时启用显示切换和强度提示。',
                '这一组能力最适合直接照抄到用户注册、管理员重置密码、API 口令生成页里。',
            ],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '显示切换与强度提示能力块',
            '展示 password 的 switch 与 strength 组合能力。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_switch_', false), '显示切换与强度提示')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('register_password', '注册密码')
                            ->switch()
                            ->strength()
                            ->minLength(8)
                    )
                    ->fetch();
            }
        );
    }

    private function style(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'prefix / rounded', 'value' => '支持图标前缀与圆角样式'],
                ['name' => 'flush / float', 'value' => '支持扁平输入和浮动标签'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'admin_password',
        'label' => '管理密码',
        'prefix' => '<i class="ti ti-lock"></i>',
        'rounded' => true,
    ],
    [
        'type' => 'password',
        'name' => 'float_password',
        'label' => '浮动密码',
        'float' => true,
        'flush' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('admin_password', '管理密码')
    ->prefix('<i class="ti ti-lock"></i>')
    ->rounded();

Field::password('float_password', '浮动密码')
    ->float()
    ->flush();
CODE,
            ['password 继承了 text 的大部分视觉能力，示例里直接展示这些共性能力最有参考价值。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '前后缀与样式能力块',
            '展示 password 的图标前缀、圆角、扁平和浮动标签能力。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_style_', false), '前后缀与样式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('admin_password', '管理密码')
                            ->prefix('<i class="ti ti-lock"></i>')
                            ->rounded()
                    )
                    ->item(
                        Password::make('float_password', '浮动密码')
                            ->float()
                            ->flush()
                    )
                    ->fetch();
            }
        );
    }

    private function state(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'readonly', 'value' => '只读展示已有值'],
                ['name' => 'disabled', 'value' => '禁用输入交互'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'readonly_token',
        'label' => '只读口令',
        'value' => 'readonly-demo',
        'readonly' => true,
    ],
    [
        'type' => 'password',
        'name' => 'disabled_secret',
        'label' => '禁用密钥',
        'disabled' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('readonly_token', '只读口令')
    ->value('readonly-demo')
    ->readonly();

Field::password('disabled_secret', '禁用密钥')
    ->disabled();
CODE,
            [
                '只读/禁用更多用于展示已有配置状态，不应用于真实密码回显场景。',
                '真实密码入库前应做哈希处理，编辑页也不应把数据库中的哈希值反向回填到 password 组件。',
            ],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '只读与禁用能力块',
            '展示 password 的只读与禁用状态。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_state_', false), '只读与禁用')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('readonly_token', '只读口令')
                            ->value('readonly-demo')
                            ->readonly()
                    )
                    ->item(
                        Password::make('disabled_secret', '禁用密钥')
                            ->disabled()
                    )
                    ->fetch();
            }
        );
    }

    private function validation(array $section): array
    {
        return $this->wrap(
            $section,
            [
                ['name' => 'valid(true)', 'value' => '展示通过校验状态'],
                ['name' => 'valid(false)', 'value' => '展示失败校验状态'],
            ],
            <<<'CODE'
[
    [
        'type' => 'password',
        'name' => 'valid_password',
        'label' => '校验通过',
        'valid' => true,
        'tips' => '密码强度符合要求',
    ],
    [
        'type' => 'password',
        'name' => 'invalid_password',
        'label' => '校验失败',
        'valid' => false,
        'tips' => '密码强度不足',
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::password('valid_password', '校验通过')
    ->valid(true)
    ->tips('密码强度符合要求');

Field::password('invalid_password', '校验失败')
    ->valid(false)
    ->tips('密码强度不足');
CODE,
            ['这个能力块方便开发者快速理解 password 同样支持标准校验反馈样式。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '校验与反馈状态能力块',
            '展示 password 的 valid 成功/失败样式。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_validation_', false), '校验与反馈状态')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Password::make('valid_password', '校验通过')
                            ->valid(true)
                            ->tips('密码强度符合要求')
                    )
                    ->item(
                        Password::make('invalid_password', '校验失败')
                            ->valid(false)
                            ->tips('密码强度不足')
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
                ['name' => 'username', 'value' => '用户名字段'],
                ['name' => 'password / password_confirm', 'value' => '密码与确认密码字段'],
            ],
            <<<'CODE'
[
    ['text', 'username', '用户名', '请输入用户名'],
    [
        'type' => 'password',
        'name' => 'password',
        'label' => '密码',
        'switch' => true,
        'strength' => true,
        'min_length' => 8,
    ],
    [
        'type' => 'password',
        'name' => 'password_confirm',
        'label' => '确认密码',
        'switch' => true,
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('username', '用户名', '请输入用户名');

Field::password('password', '密码')
    ->switch()
    ->strength()
    ->minLength(8);

Field::password('password_confirm', '确认密码')
    ->switch();
CODE,
            ['这是最接近真实注册/用户创建后台的组合示例，开发者可以直接照抄改造。'],
            'app/showcase/service/components/form/password/PasswordSectionBuilder.php',
            '业务表单片段能力块',
            '展示 password 在注册类表单中的组合方式。',
            function (): string {
                Form::clearInstances();

                return Form::make(uniqid('showcase_password_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('username', '用户名', '请输入用户名'))
                    ->item(
                        Password::make('password', '密码')
                            ->id('password_profile_form')
                            ->switch()
                            ->strength()
                            ->minLength(8)
                    )
                    ->item(
                        Password::make('password_confirm', '确认密码')
                            ->switch()
                    )
                    ->fetch();
            }
        );
    }

    /**
     * @param array<string, string> $section
     * @param list<array<string, string>> $params
     * @param list<string> $notes
     */
    private function wrap(
        array $section,
        array $params,
        string $arrayCode,
        string $fieldCode,
        array $notes,
        string $sourcePath,
        string $sourceLabel,
        string $sourceDescription,
        callable $previewBuilder
    ): array {
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
                'path' => $sourcePath,
                'label' => $sourceLabel,
                'description' => $sourceDescription,
            ]],
        ];
    }
}
