<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;

/**
 * select2 内置 ajax 数据源能力块
 */
final class Select2AjaxBuiltinSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'ajax_builtin'),
            'title' => (string) ($section['title'] ?? '内置 ajax 数据源'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'ajax.table/title/search/rows', 'value' => '使用数组配置时，框架会自动生成 `admin/api/getSelectAjax` 的 token 与请求参数'],
                ['name' => 'ajax.callback', 'value' => '可对返回数据做二次格式化，适合拼接部门名、用户昵称等展示文本'],
                ['name' => 'ajax.url', 'value' => '数组模式不传时会自动补成 `admin/api/getSelectAjax`，一般无需手动填写'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'uid',
        'label' => '用户',
        'tips' => '选择用户',
        'ajax' => [
            'url' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
            'table' => 'admin_user',
            'title' => 'username',
            'search' => 'username|nickname',
            'rows' => 15,
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('uid', '用户', '选择用户')
    ->ajax([
        'url' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']),
        'table' => 'admin_user',
        'title' => 'username',
        'search' => 'username|nickname',
        'rows' => 15,
    ])
    ->placeholder('请输入关键字搜索用户');
CODE,
            'notes' => [
                '数组形式的 ajax 是当前框架里最推荐的 select2 远程加载方式，能直接复用内置接口契约。',
                '在 showcase 示例里额外显式写入了 `url`，这样安装后就能直接命中演示数据源；真实项目里可删除该项，回退到默认 `admin/api/getSelectAjax`。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2AjaxBuiltinSection.php',
                    'label' => '内置 ajax 数据源能力块',
                    'description' => '展示 select2 如何接入框架内置 `admin/api/getSelectAjax` 契约。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();
        $showcaseAjaxUrl = (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'select2_departments']);

        return Form::make(uniqid('showcase_select2_section_ajax_builtin_', false), '内置 ajax 数据源')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item([
                'type' => 'select2',
                'name' => 'uid',
                'label' => '用户',
                'tips' => '选择用户',
                'placeholder' => '请输入关键字搜索用户',
                'ajax' => [
                    'url' => $showcaseAjaxUrl,
                    'table' => 'admin_user',
                    'title' => 'username',
                    'search' => 'username|nickname',
                    'rows' => 15,
                ],
            ])
            ->fetch();
    }
}
