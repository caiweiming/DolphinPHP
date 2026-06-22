<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\cropper;

use app\common\render\Form;
use app\common\render\form\items\cropper\Cropper;
use app\common\render\form\items\text\Text;

/**
 * cropper 业务表单片段能力块
 */
final class CropperProfileFormSection
{
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'profile_form'),
            'title' => (string) ($section['title'] ?? '业务表单片段'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'campaign_title', 'value' => '活动标题字段'],
                ['name' => 'campaign_cover', 'value' => '活动封面裁剪字段'],
                ['name' => 'campaign_poster', 'value' => '活动海报裁剪字段'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'campaign_title', '活动标题', '请输入活动标题'],
    [
        'type' => 'cropper',
        'name' => 'campaign_cover',
        'label' => '活动封面',
        'tips' => '建议裁成 16:9',
        'options' => [
            'selection' => [
                'aspectRatio' => 16 / 9,
                'initialAspectRatio' => 16 / 9,
            ],
        ],
    ],
    [
        'type' => 'cropper',
        'name' => 'campaign_poster',
        'label' => '活动海报',
        'tips' => '建议裁成 3:4',
        'options' => [
            'selection' => [
                'aspectRatio' => 3 / 4,
                'initialAspectRatio' => 3 / 4,
            ],
        ],
    ],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('campaign_title', '活动标题', '请输入活动标题');

Field::cropper('campaign_cover', '活动封面', '建议裁成 16:9')
    ->options([
        'selection' => [
            'aspectRatio' => 16 / 9,
            'initialAspectRatio' => 16 / 9,
        ],
    ]);

Field::cropper('campaign_poster', '活动海报', '建议裁成 3:4')
    ->options([
        'selection' => [
            'aspectRatio' => 3 / 4,
            'initialAspectRatio' => 3 / 4,
        ],
    ]);
CODE,
            'notes' => [
                '真实业务里经常同时存在“封面图”和“海报图”，而且两者比例不同，这种组合示例最有参考价值。',
            ],
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/cropper/CropperProfileFormSection.php',
                'label' => '业务表单片段能力块',
                'description' => '展示 cropper 在真实活动类表单中的组合方式。',
            ]],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_cropper_section_profile_', false), '业务表单片段')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(Text::make('campaign_title', '活动标题', '请输入活动标题'))
            ->item([
                'type' => 'cropper',
                'name' => 'campaign_cover',
                'label' => '活动封面',
                'tips' => '建议裁成 16:9',
                'options' => ['selection' => ['aspectRatio' => 16 / 9, 'initialAspectRatio' => 16 / 9]],
            ])
            ->item([
                'type' => 'cropper',
                'name' => 'campaign_poster',
                'label' => '活动海报',
                'tips' => '建议裁成 3:4',
                'options' => ['selection' => ['aspectRatio' => 3 / 4, 'initialAspectRatio' => 3 / 4]],
            ])
            ->fetch();
    }
}
