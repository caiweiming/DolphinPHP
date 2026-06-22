<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

use app\showcase\service\registry\ShowcaseRegistryService;
use InvalidArgumentException;

/**
 * Showcase 表格组件页工厂
 */
final class TableComponentPageFactory
{
    /**
     * @return array<int, array<string, string>>
     */
    public function actions(): array
    {
        return [
            [
                'title' => '重置演示数据',
                'summary' => '恢复表格示例初始数据，便于重复体验演示效果。',
                'route' => 'showcase/admin.table/resetDemoData',
                'url' => (string) url('showcase/admin.table/resetDemoData'),
            ],
        ];
    }

    /**
     * @param string $key
     * @return array<string, mixed>
     */
    public function make(string $key): array
    {
        $registry = app(ShowcaseRegistryService::class);
        $component = $registry->findComponent('table', $key);

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
                'actions' => $this->actions(),
            ];
        }

        $page = $this->pageBuilderFor($key)->build($component);
        $page['actions'] = $this->actions();

        return $page;
    }

    private function pageBuilderFor(string $key): object
    {
        return match ($key) {
            'state.status' => app(StatusColumnPage::class),
            'state.switch' => app(SwitchColumnPage::class),
            'state.yes_no' => app(YesNoColumnPage::class),
            'state.color' => app(ColorColumnPage::class),
            'interactive.actions' => app(ActionsColumnPage::class),
            'interactive.url' => app(UrlColumnPage::class),
            'interactive.select' => app(SelectColumnPage::class),
            'basic.image' => app(ImageColumnPage::class),
            'basic.icon' => app(IconColumnPage::class),
            'basic.datetime' => app(DatetimeColumnPage::class),
            'media.preview' => app(PreviewColumnPage::class),
            'advanced.callback' => app(CallbackColumnPage::class),
            default => throw new InvalidArgumentException(sprintf('Unknown showcase table component page: %s', $key)),
        };
    }
}
