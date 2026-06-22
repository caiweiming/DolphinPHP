<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\components\table\TableBuilderPage;
use app\showcase\service\components\table\TableLiveDemoBuilder;
use app\showcase\service\components\table\TableComponentPageFactory;
use app\showcase\service\demo\ShowcaseTableDemoDataService;
use app\showcase\service\registry\ShowcaseRegistryService;
/**
 * Showcase 表格专题控制器
 */
#[Permission('表格渲染器', code: 'admin.table', icon: 'ti ti-table', sort: 21)]
final class Table extends Auth
{
    /**
     * 允许 quickEdit 的演示字段
     * @var array<int, string>
     */
    protected array $quickEditFields = [
        'title',
        'status',
        'is_enabled',
        'is_system',
        'priority',
        'department_code',
        'expire_date',
        'publish_time',
        'sort',
        'color_tag',
    ];

    /**
     * 针对 showcase 演示表补充字符串日期字段的 quickEdit 兼容。
     */
    protected function beforeQuickEdit(int $id, string $field, mixed &$value, mixed $oldValue): bool
    {
        $token = (string) $this->request->param('_t', '');
        if ($token === '') {
            return true;
        }

        $tableConfig = dp_crud_token_decode($token);
        if (!is_array($tableConfig)) {
            return true;
        }

        $tableName = (string) ($tableConfig['name'] ?? '');
        if ($tableName !== 'showcase_table') {
            return true;
        }

        if ($field === 'expire_date') {
            if ($value === '') {
                $value = null;
                return true;
            }

            $value = is_numeric($value)
                ? date('Y-m-d', (int) $value)
                : (string) $value;
            return true;
        }

        if ($field === 'publish_time') {
            if ($value === '') {
                $value = null;
                return true;
            }

            $value = is_numeric($value)
                ? date('Y-m-d H:i:s', (int) $value)
                : (string) $value;
            return true;
        }

        return true;
    }

    /**
     * 表格专题页
     */
    #[Permission('查看专题')]
    public function index(): string
    {
        $service = app(ShowcaseRegistryService::class);
        $pageFactory = app(TableComponentPageFactory::class);
        $groups = $service->groups('table');
        $componentsByGroup = $service->componentsByGroup('table');
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
        $this->assign('tableActions', $pageFactory->actions());

        return $this->fetch('table/index');
    }

    /**
     * 重置表格演示数据
     */
    #[Permission('重置演示数据')]
    public function resetDemoData()
    {
        app(ShowcaseTableDemoDataService::class)->resetDemoData();
        $this->success('演示数据已重置');
    }

    /**
     * 表格列详情页
     */
    #[Permission('查看详情')]
    public function show(string $key = ''): string
    {
        if ($this->isLiveTableDemoRequest()) {
            return $this->respondLiveTableDemo();
        }

        $componentKey = $key !== '' ? $key : (string) $this->request->param('key/s', '');
        $pageData = app(TableComponentPageFactory::class)->make($componentKey);
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

        return $this->fetch('table/show');
    }

    /**
     * 表格构建器页面
     */
    #[Permission('查看构建器')]
    public function builder(): string
    {
        $pageData = app(TableBuilderPage::class)->build();
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

        return $this->fetch('table/builder');
    }

    /**
     * 响应详情页内嵌真实表格的 Ajax 数据请求
     */
    private function respondLiveTableDemo(): string
    {
        if (!$this->request->isAjax()) {
            // 表格组件的真实数据请求有时不会携带 X-Requested-With，
            // 这里仅对 showcase live demo 补充 Ajax 上下文，确保返回规范 JSON。
            $this->request->setRoute(['_ajax' => 1]);
        }

        $demoToken = (string) $this->request->param('_demo_token/s', '');
        $table = app(TableLiveDemoBuilder::class)->makeTableFromToken($demoToken);
        $response = $table->render();

        if ($response instanceof \think\response\Json) {
            return $response->getContent();
        }

        return '';
    }

    private function isLiveTableDemoRequest(): bool
    {
        return (int) $this->request->param('_table_demo/d', 0) === 1
            && (string) $this->request->param('_demo_token/s', '') !== '';
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
