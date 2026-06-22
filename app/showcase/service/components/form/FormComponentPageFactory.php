<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

use app\showcase\service\registry\ShowcaseRegistryService;
use InvalidArgumentException;

/**
 * Showcase 表单组件页工厂
 */
final class FormComponentPageFactory
{
    /**
     * 构建组件页数据
     * @param string $key
     * @return array<string, mixed>
     */
    public function make(string $key): array
    {
        $registry = app(ShowcaseRegistryService::class);
        $component = $registry->findComponent('form', $key);

        if (($component['status'] ?? 'planned') !== 'available') {
            return [
                'component' => $component,
                'overview' => [
                    'summary' => '当前组件已注册到示例目录；如详情数据缺失，请优先参考右侧源码与文档入口。',
                    'capabilities' => (array) ($component['tags'] ?? []),
                ],
                'param_groups' => [],
                'sections' => [],
                'related_components' => [],
                'doc_links' => (array) ($component['doc_links'] ?? []),
                'source_refs' => (array) ($component['source_refs'] ?? []),
            ];
        }

        $sections = $registry->sectionsForComponent('form', $key);
        $builder = $this->pageBuilderFor($key);

        return app(ComponentPreviewSubmitDecorator::class)->decorate($builder->build($component, $sections));
    }

    /**
     * 解析组件页构建器
     * @param string $key
     * @return object
     */
    private function pageBuilderFor(string $key): object
    {
        return match ($key) {
            'basic.text' => app(TextComponentPage::class),
            'basic.textarea' => app(TextareaComponentPage::class),
            'basic.number' => app(NumberComponentPage::class),
            'basic.password' => app(PasswordComponentPage::class),
            'basic.switch' => app(SwitchComponentPage::class),
            'basic.hidden' => app(HiddenComponentPage::class),
            'basic.html' => app(HtmlComponentPage::class),
            'basic.static' => app(StaticComponentPage::class),
            'basic.button' => app(ButtonComponentPage::class),
            'basic.button_group' => app(ButtonGroupComponentPage::class),
            'basic.mask' => app(MaskComponentPage::class),
            'choice.radio' => app(RadioComponentPage::class),
            'choice.radio_group' => app(RadioGroupComponentPage::class),
            'choice.select' => app(SelectComponentPage::class),
            'choice.select2' => app(Select2ComponentPage::class),
            'choice.linkage' => app(LinkageComponentPage::class),
            'choice.linkages' => app(LinkagesComponentPage::class),
            'choice.checkbox' => app(CheckboxComponentPage::class),
            'choice.checkbox_group' => app(CheckboxGroupComponentPage::class),
            'choice.select_group' => app(SelectGroupComponentPage::class),
            'choice.tags' => app(TagsComponentPage::class),
            'choice.color' => app(ColorComponentPage::class),
            'choice.color_select' => app(ColorSelectComponentPage::class),
            'choice.icon' => app(IconComponentPage::class),
            'datetime.date' => app(DateComponentPage::class),
            'datetime.date_range' => app(DateRangeComponentPage::class),
            'datetime.time' => app(TimeComponentPage::class),
            'datetime.datetime' => app(DatetimeComponentPage::class),
            'datetime.datetime_range' => app(DatetimeRangeComponentPage::class),
            'media.image' => app(ImageComponentPage::class),
            'media.file' => app(FileComponentPage::class),
            'media.cropper' => app(CropperComponentPage::class),
            'media.image_select' => app(ImageSelectComponentPage::class),
            'extensions.data_table' => app(ExtensionComponentPage::class),
            'extensions.fieldset' => app(ExtensionComponentPage::class),
            'extensions.select_table' => app(ExtensionComponentPage::class),
            'extensions.transfer' => app(TransferComponentPage::class),
            'rich.ueditor' => app(UeditorComponentPage::class),
            'rich.vditor' => app(VditorComponentPage::class),
            'rich.amap' => app(MapComponentPage::class),
            'rich.bmap' => app(MapComponentPage::class),
            'rich.qmap' => app(MapComponentPage::class),
            'rich.tabs' => app(TabsComponentPage::class),
            'rich.table' => app(TableComponentPage::class),
            default => throw new InvalidArgumentException(sprintf('Unknown showcase component page: %s', $key)),
        };
    }
}
