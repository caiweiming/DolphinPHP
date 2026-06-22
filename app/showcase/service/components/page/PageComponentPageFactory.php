<?php
declare(strict_types=1);

namespace app\showcase\service\components\page;

use app\showcase\service\registry\ShowcaseRegistryService;
use InvalidArgumentException;

/**
 * Showcase 页面组件页工厂
 */
final class PageComponentPageFactory
{
    /**
     * @param string $key
     * @return array<string, mixed>
     */
    public function make(string $key): array
    {
        $registry = app(ShowcaseRegistryService::class);
        $component = $registry->findComponent('page', $key);

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

        return $this->pageBuilderFor($key)->build($component);
    }

    private function pageBuilderFor(string $key): object
    {
        return match ($key) {
            'basic.header' => app(HeaderPage::class),
            'basic.row' => app(RowPage::class),
            'layout.grid' => app(GridPage::class),
            'layout.mixed' => app(MixedLayoutPage::class),
            'tabs.content' => app(ContentTabsPage::class),
            'tabs.form_table' => app(FormTableTabsPage::class),
            'actions.interactive' => app(InteractiveActionsPage::class),
            default => throw new InvalidArgumentException(sprintf('Unknown showcase page component page: %s', $key)),
        };
    }
}
