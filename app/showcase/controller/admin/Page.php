<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\components\page\PageBuilderPage;
use app\showcase\service\components\page\PageComponentPageFactory;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * Showcase 页面专题控制器
 */
#[Permission('页面渲染器', code: 'admin.page', icon: 'ti ti-layout-dashboard', sort: 23)]
final class Page extends Auth
{
    #[Permission('查看专题')]
    public function index(): string
    {
        $service = app(ShowcaseRegistryService::class);
        $groups = $service->groups('page');
        $componentsByGroup = $service->componentsByGroup('page');
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

        return $this->fetch('page/index');
    }

    #[Permission('查看详情')]
    public function show(string $key = ''): string
    {
        $componentKey = $key !== '' ? $key : (string) $this->request->param('key/s', '');
        $pageData = app(PageComponentPageFactory::class)->make($componentKey);
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

        return $this->fetch('page/show');
    }

    #[Permission('查看构建器')]
    public function builder(): string
    {
        $pageData = app(PageBuilderPage::class)->build();
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

        return $this->fetch('page/builder');
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
