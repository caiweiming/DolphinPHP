<?php
declare(strict_types=1);

namespace app\cms\controller\admin;

use app\cms\model\Article as ArticleModel;
use app\cms\model\Category as CategoryModel;
use app\cms\service\CmsInputSecurityService;
use app\common\attribute\Permission;
use Exception;
use think\Paginator;
use think\response\Json;
use Throwable;

/**
 * CMS 分类后台管理
 */
#[Permission('CMS分类管理', icon: 'ti ti-category', sort: 10)]
class Category extends Auth
{
    /**
     * 允许快速编辑的字段
     * @var array<int, string>
     */
    protected array $quickEditFields = ['status'];

    /**
     * 分类模型
     * @var CategoryModel
     */
    protected CategoryModel $model;

    /**
     * 输入安全服务
     * @var CmsInputSecurityService
     */
    protected CmsInputSecurityService $inputSecurityService;

    /**
     * 初始化
     * @return void
     * @throws Throwable
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->model = new CategoryModel();
        $this->inputSecurityService = app(CmsInputSecurityService::class);
    }

    /**
     * 分类列表
     * @return string|Json
     */
    #[Permission('查看')]
    public function index(): string|Json
    {
        $this->table
            ->search('name,slug,status')
            ->columns([
                ['id', 'ID'],
                ['name', '分类名称'],
                ['slug', '别名'],
                ['status', '状态', 'switch', ['1' => '启用', '0' => '禁用']],
                ['sort', '排序'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'actions', ['edit', 'delete']],
            ])
            ->toolbar(['add', 'delete'])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 分类数据
     * @return Paginator
     */
    protected function data(): Paginator
    {
        return $this->model
            ->where($this->getSearchWhere())
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate((int)dp_app_setting('cms', 'content.list_rows', dp_get_list_rows()));
    }

    /**
     * 新增分类
     * @return string|Json
     * @throws Throwable
     */
    #[Permission('新增')]
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data = app(\app\cms\service\CmsInputSecurityService::class)->sanitizeCategoryPayload(
                $this->request->post('', null, 'trim')
            );
            $this->autoValidate(\app\cms\validate\AdminCategory::class, $data);

            try {
                $this->model->create($data);
            } catch (Exception) {
                $this->error('新增分类失败');
            }

            $this->success('新增分类成功', '', 'reload-table');
        }

        $this->form
            ->data([
                'status' => 1,
                'sort'   => 0,
            ])
            ->items($this->buildFormItems());

        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 编辑分类
     * @return string|Json
     * @throws Throwable
     */
    #[Permission('编辑')]
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);
        $category = $this->model->find($id);
        if (!$category) {
            $this->error('分类不存在');
        }

        if ($this->request->isPost()) {
            $data = app(\app\cms\service\CmsInputSecurityService::class)->sanitizeCategoryPayload(
                $this->request->post('', null, 'trim')
            );
            $this->autoValidate(\app\cms\validate\AdminCategory::class, $data);

            try {
                $category->save($data);
            } catch (Exception) {
                $this->error('编辑分类失败');
            }

            $this->success('编辑分类成功', '', 'reload-table');
        }

        $this->form
            ->data($category->toArray())
            ->items($this->buildFormItems());

        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 删除分类
     * @return Json
     */
    #[Permission('删除')]
    public function delete(): Json
    {
        $ids = $this->parseIdsParam($this->request->param('ids', []));
        if ($ids === []) {
            $id = $this->request->param('id/d', 0);
            if ($id > 0) {
                $ids = [$id];
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            $this->error('参数错误');
        }

        $categories = $this->model
            ->whereIn('id', $ids)
            ->select()
            ->all();

        if ($categories === []) {
            $this->error('分类不存在');
        }

        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryMap[(int)$category->id] = $category;
        }

        $foundIds = array_keys($categoryMap);
        $missing  = array_values(array_diff($ids, $foundIds));
        if ($missing !== []) {
            $this->error(count($missing) === 1 ? '分类不存在' : '存在不存在的分类');
        }

        foreach ($ids as $id) {
            $articleCount = (new ArticleModel())->where('category_id', $id)->count();
            if ($articleCount > 0) {
                $categoryName = (string)$categoryMap[$id]->getAttr('name');
                $this->error(
                    count($ids) > 1
                        ? sprintf('分类「%s」下仍存在 %d 篇文章，不能批量删除', $categoryName, $articleCount)
                        : sprintf('分类下仍存在 %d 篇文章，不能删除', $articleCount)
                );
            }
        }

        $deletedCount = 0;
        foreach ($ids as $id) {
            if ($categoryMap[$id]->delete() === false) {
                $this->error('删除分类失败');
            }
            $deletedCount++;
        }

        $this->success(
            $deletedCount > 1
                ? sprintf('成功删除 %d 个分类', $deletedCount)
                : '删除分类成功'
        );
    }

    /**
     * 构建分类表单
     * @return array<int, array<int|string, mixed>>
     */
    private function buildFormItems(): array
    {
        return [
            ['text:*', 'name', '分类名称', '请输入分类名称'],
            ['text:*', 'slug', '分类别名', '请输入分类别名，例如 company-news'],
            ['text', 'sort', '排序', '数字越小越靠前', '0'],
            ['switch', 'status', '状态', '是否启用', 1],
            ['textarea', 'remark', '备注', '用于说明当前分类的用途'],
        ];
    }
}
