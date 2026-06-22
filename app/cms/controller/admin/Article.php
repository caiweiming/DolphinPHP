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
 * CMS 文章后台管理
 */
#[Permission('CMS文章管理', icon: 'ti ti-article', sort: 20)]
class Article extends Auth
{
    /**
     * 文章模型
     * @var ArticleModel
     */
    protected ArticleModel $model;

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
        $this->model = new ArticleModel();
        $this->inputSecurityService = app(CmsInputSecurityService::class);
    }

    /**
     * 文章列表
     * @return string|Json
     */
    #[Permission('查看')]
    public function index(): string|Json
    {
        $this->table
            ->search([
                [
                    'name'        => 'keyword',
                    'placeholder' => '标题 / 别名',
                    'fields'      => ['title', 'slug'],
                ],
                [
                    'name'        => 'category_id',
                    'type'        => 'select',
                    'placeholder' => '分类',
                    'options'     => $this->getCategoryOptions(),
                ],
                [
                    'name'        => 'status',
                    'type'        => 'select',
                    'placeholder' => '状态',
                    'options'     => [
                        1 => '已发布',
                        0 => '草稿',
                    ],
                ],
            ])
            ->columns([
                ['id', 'ID'],
                ['category_name', '分类'],
                ['title', '标题'],
                ['slug', '别名'],
                ['status_text', '状态', 'status', [
                    '已发布' => 'success',
                    '草稿'   => 'warning',
                ]],
                ['sort', '排序'],
                ['published_time', '发布时间', 'datetime'],
                ['view_count', '浏览量'],
                ['right_button', '操作', 'actions', ['edit', 'delete']],
            ])
            ->toolbar(['add', 'delete'])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 文章数据
     * @return Paginator
     */
    protected function data(): Paginator
    {
        $categoryOptions = $this->getCategoryOptions();

        return $this->model
            ->where($this->getSearchWhere())
            ->order('sort', 'asc')
            ->order('published_time', 'desc')
            ->order('id', 'desc')
            ->paginate((int)dp_app_setting('cms', 'content.list_rows', dp_get_list_rows()))
            ->each(function (ArticleModel $article) use ($categoryOptions): ArticleModel {
                $article->setAttr(
                    'category_name',
                    $categoryOptions[(int)$article->getAttr('category_id')] ?? '-'
                );
                $article->setAttr(
                    'status_text',
                    (int)$article->getAttr('status') === 1 ? '已发布' : '草稿'
                );

                return $article;
            });
    }

    /**
     * 添加文章
     * @return string|Json
     * @throws Throwable
     */
    #[Permission('新增')]
    public function create(): string|Json
    {
        if ($this->request->isPost()) {
            $data = app(\app\cms\service\CmsInputSecurityService::class)->sanitizeArticlePayload(
                $this->request->post('', null, 'trim')
            );
            $this->autoValidate(\app\cms\validate\AdminArticle::class, $data);

            try {
                $data['published_time'] = $this->normalizePublishedTime($data['published_time'] ?? '');
                $this->model->create($data);
            } catch (Exception) {
                $this->error('新增文章失败');
            }

            $this->success('新增文章成功', '', 'reload-table');
        }

        $this->form
            ->data([
                'category_id'     => 0,
                'status'          => (int)dp_app_setting('cms', 'content.default_status', 1),
                'sort'            => 0,
                'published_time'  => date('Y-m-d H:i:s'),
            ])
            ->items($this->buildArticleFormItems());

        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 编辑文章
     * @return string|Json
     * @throws Throwable
     */
    #[Permission('编辑')]
    public function edit(): string|Json
    {
        $id = $this->request->param('id/d', 0);
        $article = $this->model->find($id);
        if (!$article) {
            $this->error('文章不存在');
        }

        if ($this->request->isPost()) {
            $data = app(\app\cms\service\CmsInputSecurityService::class)->sanitizeArticlePayload(
                $this->request->post('', null, 'trim')
            );
            $this->autoValidate(\app\cms\validate\AdminArticle::class, $data);

            try {
                $data['published_time'] = $this->normalizePublishedTime($data['published_time'] ?? '');
                $article->save($data);
            } catch (Exception) {
                $this->error('编辑文章失败');
            }

            $this->success('编辑文章成功', '', 'reload-table');
        }

        $formData = $article->toArray();
        $formData['published_time'] = !empty($formData['published_time'])
            ? date('Y-m-d H:i:s', (int)$formData['published_time'])
            : '';

        $this->form
            ->data($formData)
            ->items($this->buildArticleFormItems());

        $this->page->row($this->form);
        return $this->fetch();
    }

    /**
     * 删除文章
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

        $articles = $this->model
            ->whereIn('id', $ids)
            ->select()
            ->all();

        if ($articles === []) {
            $this->error('文章不存在');
        }

        $articleMap = [];
        foreach ($articles as $article) {
            $articleMap[(int)$article->id] = $article;
        }

        $foundIds = array_keys($articleMap);
        $missing  = array_values(array_diff($ids, $foundIds));
        if ($missing !== []) {
            $this->error(count($missing) === 1 ? '文章不存在' : '存在不存在的文章');
        }

        $deletedCount = 0;
        foreach ($ids as $id) {
            if ($articleMap[$id]->delete() === false) {
                $this->error('删除文章失败');
            }
            $deletedCount++;
        }

        $this->success(
            $deletedCount > 1
                ? sprintf('成功删除 %d 篇文章', $deletedCount)
                : '删除文章成功'
        );
    }

    /**
     * 获取分类选项
     * @return array<int, string>
     */
    private function getCategoryOptions(): array
    {
        return (new CategoryModel())
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->column('name', 'id');
    }

    /**
     * 构建文章表单
     * @return array<int, array<int|string, mixed>>
     */
    private function buildArticleFormItems(): array
    {
        return [
            ['select2:*', 'category_id', '所属分类', '请选择文章分类', '', $this->getCategoryOptions()],
            ['text:*', 'title', '文章标题', '请输入文章标题'],
            ['text:*', 'slug', '文章别名', '请输入文章别名，例如 hello-cms'],
            ['textarea', 'summary', '摘要', '用于列表页摘要展示'],
            ['textarea', 'content', '正文', '用于演示最小应用的正文输入'],
            ['text', 'cover', '封面地址', '可填写图片 URL 或附件路径'],
            ['text', 'source', '文章来源', '例如 官方公告、产品文档'],
            ['switch', 'status', '状态', '开启表示前台可见', 1],
            ['text', 'sort', '排序', '数字越小越靠前', '0'],
            ['datetime', 'published_time', '发布时间', '请选择发布时间'],
        ];
    }

    /**
     * 规范化发布时间
     * @param mixed $value
     * @return int
     */
    private function normalizePublishedTime(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '') {
            return time();
        }

        $timestamp = strtotime($stringValue);
        return $timestamp !== false ? $timestamp : time();
    }
}
