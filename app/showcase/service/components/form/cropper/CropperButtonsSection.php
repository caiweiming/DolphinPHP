<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;

/**
 * cropper 按钮组配置能力块
 */
final class CropperButtonsSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'buttons'),
            'title' => (string) ($section['title'] ?? '按钮组配置'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'buttons', 'value' => '控制 upload / url / select / delete 入口'],
                ['name' => '字符串写法', 'value' => 'buttons 也支持用逗号分隔字符串传入，适合数组配置迁移场景'],
            ],
            'array_code' => <<<'CODE'
[
    [
        'type' => 'cropper',
        'name' => 'member_avatar',
        'label' => '会员头像',
        'buttons' => ['upload', 'delete'],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::cropper('member_avatar', '会员头像')
    ->buttons(['upload', 'delete']);
CODE,
            'notes' => [
                '按钮组裁剪后最适合做“只能上传本地图片”或“允许录入网络图片”的差异化场景。',
                '如果你是从旧配置迁移过来，也可以继续沿用字符串写法，例如 `upload,url,delete`。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperButtonsSection.php',
                'label' => '按钮组配置能力块',
                'description' => '展示 cropper 如何裁剪上传入口按钮集合。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_buttons_', false), '按钮组配置')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Cropper::make('member_avatar', '会员头像')->buttons(['upload', 'delete']))
            ->fetch();
    }
}
