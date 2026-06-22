<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\text;

use app\common\render\Form;
use app\common\render\form\items\text\Text;

/**
 * text datalist 能力块
 */
final class TextDatalistSection
{
    /**
     * @param array<string, mixed> $component
     * @param array<string, string> $section
     * @return array<string, mixed>
     */
    public function build(array $component, array $section): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'datalist'),
            'title' => (string) ($section['title'] ?? '自动补全 datalist'),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $this->buildPreview(),
            'params' => [
                ['name' => 'datalist', 'value' => '浏览器原生候选列表'],
                ['name' => 'placeholder', 'value' => '提示可输入或选择的值'],
            ],
            'array_code' => <<<'CODE'
[
    ['text', 'city', '常驻城市', '可输入，也可从候选项中快速选择'],
]
CODE,
            'field_code' => <<<'CODE'
use app\common\render\form\Field;

Field::text('city', '常驻城市', '可输入，也可从候选项中快速选择')
    ->placeholder('例如 上海')
    ->datalist(['上海', '北京', '杭州', '深圳']);
CODE,
            'notes' => [
                'datalist 适合中小规模候选项，零额外脚本即可获得自动补全体验。',
            ],
            'source_refs' => [
                [
                    'path' => 'app/showcase/service/components/form/text/TextDatalistSection.php',
                    'label' => 'datalist 能力块',
                    'description' => '展示浏览器原生自动补全候选。'],
            ],
        ];
    }

    private function buildPreview(): string
    {
        Form::clearInstances();

        return Form::make(uniqid('showcase_text_section_datalist_', false), '自动补全 datalist')
            ->header(false)
            ->template(root_path() . 'app/common/render/form/layout.html')
            ->item(
                Text::make('city', '常驻城市', '可输入，也可从候选项中快速选择')
                    ->placeholder('例如 上海')
                    ->datalist(['上海', '北京', '杭州', '深圳'])
            )
            ->fetch();
    }
}
