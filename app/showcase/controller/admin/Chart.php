<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\components\chart\ChartBuilderPage;
use app\showcase\service\components\chart\ChartComponentPageFactory;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * Showcase 图表专题控制器
 */
#[Permission('图表渲染器', code: 'admin.chart', icon: 'ti ti-chart-line', sort: 22)]
final class Chart extends Auth
{
    #[Permission('查看专题')]
    public function index(): string
    {
        $service = app(ShowcaseRegistryService::class);
        $groups = $service->groups('chart');
        $componentsByGroup = $service->componentsByGroup('chart');
        $sections = [];

        foreach ($groups as $groupKey => $group) {
            $sections[] = [
                'key' => $groupKey,
                'title' => $group['title'] ?? '',
                'summary' => $group['summary'] ?? '',
                'components' => $componentsByGroup[$groupKey] ?? [],
            ];
        }

        $this->assign('sections', $sections);

        return $this->fetch('chart/index');
    }

    #[Permission('查看详情')]
    public function show(string $key = ''): string
    {
        $componentKey = $key !== '' ? $key : (string) $this->request->param('key/s', '');
        $pageData = app(ChartComponentPageFactory::class)->make($componentKey);
        $sectionSourceCodes = [];

        foreach ((array) ($pageData['sections'] ?? []) as $section) {
            $sectionKey = (string) ($section['key'] ?? '');
            if ($sectionKey === '') {
                continue;
            }

            $sectionSourceCodes[$sectionKey] = $this->buildSourceCodes((array) ($section['source_refs'] ?? []));
        }

        $this->assign('pageData', $pageData);
        $this->assign('sectionSourceCodes', $sectionSourceCodes);

        return $this->fetch('chart/show');
    }

    #[Permission('查看构建器')]
    public function builder(): string
    {
        $pageData = app(ChartBuilderPage::class)->build();
        $sectionSourceCodes = [];

        foreach ((array) ($pageData['sections'] ?? []) as $section) {
            $sectionKey = (string) ($section['key'] ?? '');
            if ($sectionKey === '') {
                continue;
            }

            $sectionSourceCodes[$sectionKey] = $this->buildSourceCodes((array) ($section['source_refs'] ?? []));
        }

        $this->assign('pageData', $pageData);
        $this->assign('sectionSourceCodes', $sectionSourceCodes);

        return $this->fetch('chart/builder');
    }

    /**
     * @param array<int, array<string, mixed>> $sourceRefs
     * @return array<int, array<string, string>>
     */
    private function buildSourceCodes(array $sourceRefs): array
    {
        $result = [];

        foreach ($sourceRefs as $sourceRef) {
            $path = trim((string) ($sourceRef['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $absolutePath = root_path() . $path;
            if (!is_file($absolutePath)) {
                continue;
            }

            $result[] = [
                'label' => trim((string) ($sourceRef['label'] ?? '源码文件')),
                'path' => $path,
                'content' => (string) file_get_contents($absolutePath),
            ];
        }

        return $result;
    }
}
