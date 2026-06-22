<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\components\form\FormComponentPageFactory;
use app\showcase\service\components\form\FormBuilderPage;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * Showcase 表单专题控制器
 */
#[Permission('表单渲染器', code: 'admin.form', icon: 'ti ti-forms', sort: 20)]
final class Form extends Auth
{
    /**
     * 表单专题页
     */
    #[Permission('查看专题')]
    public function index(): string
    {
        $service = app(ShowcaseRegistryService::class);
        $groups = $service->groups('form');
        $componentsByGroup = $service->componentsByGroup('form');
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

        return $this->fetch('form/index');
    }

    /**
     * 表单示例详情页
     */
    #[Permission('查看详情')]
    public function show(string $key = ''): string
    {
        $componentKey = $key !== '' ? $key : (string) $this->request->param('key/s', '');
        $pageData = app(FormComponentPageFactory::class)->make($componentKey);
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

        return $this->fetch('form/show');
    }

    /**
     * 表单构建器页面
     */
    #[Permission('查看构建器')]
    public function builder(): string
    {
        $pageData = app(FormBuilderPage::class)->build();
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

        return $this->fetch('form/builder');
    }

    /**
     * 构建源码预览数据
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
