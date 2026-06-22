<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\select2;

use app\common\render\Form;
use app\common\render\form\items\select2\Select2;

/**
 * select2 多选能力块
 */
final class Select2MultipleSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'multiple'),
            'title' => (string) ($section['title'] ?? 'multiple 多选'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'multiple', 'value' => 'true 后启用多选模式并自动追加 name[]'],
                ['name' => '搜索 + 多选', 'value' => '比 select 更适合多值字段，因为用户可以边搜边选'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'select2',
        'name' => 'role_ids',
        'label' => '角色',
        'tips' => '可一次选择多个角色',
        'multiple' => true,
        'options' => [
            'admin' => '管理员',
            'editor' => '编辑',
            'author' => '作者',
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::select2('role_ids', '角色', '可一次选择多个角色')
    ->multiple()
    ->options([
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
    ]);
CODE,
            'notes' => [
                '多选是 select2 的高频场景，尤其适合用户、角色、标签这类数据量较多的字段。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/select2/Select2MultipleSection.php',
                    'label' => '多选能力块',
                    'description' => '展示 select2 开启 multiple 后的多选结构。',
                ],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_select2_section_multiple_', false), 'multiple 多选')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Select2::make('role_ids', '角色', '可一次选择多个角色')
                    ->multiple()
                    ->options([
                        'admin' => '管理员',
                        'editor' => '编辑',
                        'author' => '作者',
                    ])
            )
            ->fetch();
    }
}
