<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

use InvalidArgumentException;
use LogicException;

/**
 * Showcase 注册表聚合服务
 */
final class ShowcaseRegistryService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function renderers(): array
    {
        return ShowcaseRendererCatalog::all();
    }

    public function groups(string $renderer): array
    {
        return $this->componentCatalogFor($renderer)::groups();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function components(string $renderer): array
    {
        return $this->componentCatalogFor($renderer)::components();
    }

    /**
     * 兼容旧示例 API，供后续任务完成前的现有页面继续使用。
     * @return array<string, array<string, mixed>>
     */
    public function examples(string $renderer): array
    {
        return $this->legacyCatalogFor($renderer)::examples();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function componentsByGroup(string $renderer): array
    {
        $grouped = [];

        foreach ($this->groups($renderer) as $groupKey => $group) {
            $grouped[$groupKey] = [];
        }

        foreach ($this->components($renderer) as $component) {
            $groupKey = (string) ($component['group'] ?? '');

            if (!array_key_exists($groupKey, $grouped)) {
                throw new LogicException(sprintf('Unknown showcase component group: %s/%s', $renderer, $groupKey));
            }

            $grouped[$groupKey][] = $component;
        }

        return $grouped;
    }

    /**
     * 兼容旧示例 API，供后续任务完成前的现有页面继续使用。
     * @return array<string, array<string, mixed>>
     */
    public function examplesByGroup(string $renderer): array
    {
        $grouped = [];

        foreach ($this->groups($renderer) as $groupKey => $group) {
            $grouped[$groupKey] = [];
        }

        foreach ($this->examples($renderer) as $example) {
            $groupKey = (string) ($example['group'] ?? '');

            if (!array_key_exists($groupKey, $grouped)) {
                throw new LogicException(sprintf('Unknown showcase example group: %s/%s', $renderer, $groupKey));
            }

            $grouped[$groupKey][] = $example;
        }

        return $grouped;
    }

    /**
     * @return array<string, mixed>
     */
    public function findComponent(string $renderer, string $key): array
    {
        $components = $this->components($renderer);

        if (!isset($components[$key])) {
            throw new InvalidArgumentException(sprintf('Unknown showcase component: %s/%s', $renderer, $key));
        }

        return $components[$key];
    }

    /**
     * 兼容旧示例 API，供后续任务完成前的现有页面继续使用。
     * @return array<string, mixed>
     */
    public function findExample(string $renderer, string $key): array
    {
        $examples = $this->examples($renderer);

        if (!isset($examples[$key])) {
            throw new InvalidArgumentException(sprintf('Unknown showcase example: %s/%s', $renderer, $key));
        }

        return $examples[$key];
    }

    /**
     * @return list<array<string, string>>
     */
    public function sectionsForComponent(string $renderer, string $key): array
    {
        if ($renderer !== 'form') {
            throw new LogicException(sprintf('Unsupported showcase section catalog: %s', $renderer));
        }

        $this->findComponent($renderer, $key);
        $sectionsByComponent = ShowcaseFormComponentSectionCatalog::sections();

        return $sectionsByComponent[$key] ?? [];
    }

    /**
     * @return class-string
     */
    private function componentCatalogFor(string $renderer): string
    {
        $catalogs = [
            'form' => ShowcaseFormComponentCatalog::class,
            'table' => ShowcaseTableComponentCatalog::class,
            'chart' => ShowcaseChartComponentCatalog::class,
            'page' => ShowcasePageComponentCatalog::class,
        ];

        if (!isset($catalogs[$renderer])) {
            throw new LogicException(sprintf('Unsupported showcase renderer catalog: %s', $renderer));
        }

        return $catalogs[$renderer];
    }

    /**
     * @return class-string
     */
    private function legacyCatalogFor(string $renderer): string
    {
        $catalogs = [
            'form' => ShowcaseFormExampleCatalog::class,
        ];

        if (!isset($catalogs[$renderer])) {
            throw new LogicException(sprintf('Unsupported showcase renderer catalog: %s', $renderer));
        }

        return $catalogs[$renderer];
    }
}
