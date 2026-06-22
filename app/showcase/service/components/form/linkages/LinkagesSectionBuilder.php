<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\linkages;

use app\common\render\Form;
use app\common\render\form\items\linkages\Linkages;
use app\common\render\form\items\text\Text;

/**
 * linkages 能力块构建器
 */
final class LinkagesSectionBuilder
{
    private const SHOWCASE_LINKAGES_URL = 'showcase/admin.demo_api/options';

    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'levels' => $this->levels($section),
            'fields_root' => $this->fieldsRoot($section),
            'prefix_connection' => $this->prefixConnection($section),
            'filters' => $this->filters($section),
            'submit_all' => $this->submitAll($section),
            'default_value' => $this->defaultValue($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'table / levels', 'value' => '快速联动的最小核心配置'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'area_id',
        'label' => '快速联动',
        'table' => 'region',
        'levels' => ['省份', '城市', '区县'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('area_id', '快速联动')
    ->table('region')
    ->levels(['省份', '城市', '区县']);
CODE,
            ['没有配置 `table` 时，组件会直接提示 `未配置 table 参数`。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_basic_', false), '基础快速联动')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('area_id', '快速联动')
                            ->table('region')
                            ->url($showcaseUrl)
                            ->levels(['省份', '城市', '区县'])
                    )
                    ->fetch();
            }
        );
    }

    private function levels(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'levels(int)', 'value' => '可直接写联动级数'],
                ['name' => 'levels(array)', 'value' => '也可自定义每一级的 key、label、placeholder'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'category_path',
        'label' => '分类路径',
        'table' => 'goods_category',
        'levels' => [
            ['key' => 'group', 'label' => '一级分类', 'placeholder' => '请选择一级分类'],
            ['key' => 'category', 'label' => '二级分类', 'placeholder' => '请选择二级分类'],
            ['key' => 'sub_category', 'label' => '三级分类', 'placeholder' => '请选择三级分类'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('category_path', '分类路径')
    ->table('goods_category')
    ->levels([
        ['key' => 'group', 'label' => '一级分类', 'placeholder' => '请选择一级分类'],
        ['key' => 'category', 'label' => '二级分类', 'placeholder' => '请选择二级分类'],
        ['key' => 'sub_category', 'label' => '三级分类', 'placeholder' => '请选择三级分类'],
    ]);
CODE,
            ['如果只是固定 2 级或 3 级，也可以直接用 `->levels(3)` 的简写。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_levels_', false), 'levels 数量与命名')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('category_path', '分类路径')
                            ->table('goods_category')
                            ->url($showcaseUrl)
                            ->levels([
                                ['key' => 'group', 'label' => '一级分类', 'placeholder' => '请选择一级分类'],
                                ['key' => 'category', 'label' => '二级分类', 'placeholder' => '请选择二级分类'],
                                ['key' => 'sub_category', 'label' => '三级分类', 'placeholder' => '请选择三级分类'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function fieldsRoot(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'fields(id/name/pid)', 'value' => '适配非标准字段名的树形表'],
                ['name' => 'root_pid', 'value' => '指定根级 pid 值'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'dept_id',
        'label' => '组织架构',
        'table' => 'department_tree',
        'fields' => ['id' => 'dept_id', 'name' => 'dept_name', 'pid' => 'parent_id'],
        'root_pid' => 'root',
        'levels' => ['事业群', '部门', '小组'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('dept_id', '组织架构')
    ->table('department_tree')
    ->fields(['id' => 'dept_id', 'name' => 'dept_name', 'pid' => 'parent_id'])
    ->rootPid('root')
    ->levels(['事业群', '部门', '小组']);
CODE,
            ['这类参数决定了框架如何去理解你的树表结构，是 linkages 的关键适配点。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_fields_', false), 'fields 与 root_pid')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('dept_id', '组织架构')
                            ->table('department_tree')
                            ->url($showcaseUrl)
                            ->fields(['id' => 'dept_id', 'name' => 'dept_name', 'pid' => 'parent_id'])
                            ->rootPid('root')
                            ->levels(['事业群', '部门', '小组'])
                    )
                    ->fetch();
            }
        );
    }

    private function prefixConnection(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'prefix(true)', 'value' => '使用带前缀表名查询'],
                ['name' => 'connection(string)', 'value' => '读取指定数据库连接'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'tenant_area_id',
        'label' => '租户行政区',
        'table' => 'tenant_region',
        'prefix' => true,
        'connection' => 'tenant',
        'levels' => ['省份', '城市', '区县'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('tenant_area_id', '租户行政区')
    ->table('tenant_region')
    ->prefix()
    ->connection('tenant')
    ->levels(['省份', '城市', '区县']);
CODE,
            ['多库或租户系统里，这一组参数通常决定数据是否能正确读出来。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_conn_', false), 'prefix 与 connection')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('tenant_area_id', '租户行政区')
                            ->table('tenant_region')
                            ->url($showcaseUrl)
                            ->prefix()
                            ->connection('tenant')
                            ->levels(['省份', '城市', '区县'])
                    )
                    ->fetch();
            }
        );
    }

    private function filters(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'filters(array)', 'value' => '为联动查询追加固定过滤条件'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'visible_category',
        'label' => '展示分类',
        'table' => 'goods_category',
        'filters' => ['status' => 1, 'scene' => 'showcase'],
        'levels' => ['一级分类', '二级分类', '三级分类'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('visible_category', '展示分类')
    ->table('goods_category')
    ->filters(['status' => 1, 'scene' => 'showcase'])
    ->levels(['一级分类', '二级分类', '三级分类']);
CODE,
            ['如果整棵树里只有一部分节点可选，用 `filters` 比手写新接口更省事。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_filter_', false), 'filters 分级筛选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('visible_category', '展示分类')
                            ->table('goods_category')
                            ->url($showcaseUrl)
                            ->filters(['status' => 1, 'scene' => 'showcase'])
                            ->levels(['一级分类', '二级分类', '三级分类'])
                    )
                    ->fetch();
            }
        );
    }

    private function submitAll(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'submit_all(false)', 'value' => '只提交末级值'],
                ['name' => 'multiple()', 'value' => '配合末级多选提交数组结果'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'leaf_category_ids',
        'label' => '叶子分类',
        'table' => 'goods_category',
        'submit_all' => false,
        'multiple' => true,
        'levels' => ['一级分类', '二级分类', '叶子分类'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('leaf_category_ids', '叶子分类')
    ->table('goods_category')
    ->submitAll(false)
    ->multiple()
    ->levels(['一级分类', '二级分类', '叶子分类']);
CODE,
            ['当最终业务只关心叶子节点时，这种提交结构会更干净。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_submit_', false), 'submit_all 与末级提交')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('leaf_category_ids', '叶子分类')
                            ->table('goods_category')
                            ->url($showcaseUrl)
                            ->submitAll(false)
                            ->multiple()
                            ->levels(['一级分类', '二级分类', '叶子分类'])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'value', 'value' => '支持按层级数组回填已有路径'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkages',
        'name' => 'category_path',
        'label' => '默认分类路径',
        'table' => 'goods_category',
        'value' => ['10', '108', '1086'],
        'levels' => ['一级分类', '二级分类', '三级分类'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkages('category_path', '默认分类路径')
    ->table('goods_category')
    ->value(['10', '108', '1086'])
    ->levels(['一级分类', '二级分类', '三级分类']);
CODE,
            ['列表数组会按层级顺序自动映射到每一级的 key。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkages::make('category_path', '默认分类路径')
                            ->table('goods_category')
                            ->url($showcaseUrl)
                            ->value(['10', '108', '1086'])
                            ->levels(['一级分类', '二级分类', '三级分类'])
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGES_URL, ['dataset' => 'linkages']);

        return $this->wrap(
            $section,
            [
                ['name' => 'category_name / category_path', 'value' => '商品分类配置页中的典型组合'],
            ],
            <<<'CODE'
[
    ['type' => 'text', 'name' => 'category_name', 'label' => '分类名称', 'tips' => '请输入对外展示的分类名称'],
    [
        'type' => 'linkages',
        'name' => 'parent_path',
        'label' => '上级路径',
        'table' => 'goods_category',
        'levels' => ['一级分类', '二级分类', '三级分类'],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::text('category_name', '分类名称', '请输入对外展示的分类名称');

Field::linkages('parent_path', '上级路径')
    ->table('goods_category')
    ->levels(['一级分类', '二级分类', '三级分类']);
CODE,
            ['linkages 适合放在“选路径、选层级”的后台维护页面里。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkages_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('category_name', '分类名称', '请输入对外展示的分类名称'))
                    ->item(
                        Linkages::make('parent_path', '上级路径')
                            ->table('goods_category')
                            ->url($showcaseUrl)
                            ->levels(['一级分类', '二级分类', '三级分类'])
                    )
                    ->fetch();
            }
        );
    }

    private function wrap(array $section, array $params, string $arrayCode, string $fieldCode, array $notes, callable $previewBuilder): array
    {
        return [
            'key' => (string) ($section['key'] ?? 'basic'),
            'title' => (string) ($section['title'] ?? ''),
            'summary' => (string) ($section['summary'] ?? ''),
            'preview_html' => $previewBuilder(),
            'params' => $params,
            'array_code' => $arrayCode,
            'field_code' => $fieldCode,
            'notes' => $notes,
            'source_refs' => [[
                'path' => 'app/showcase/service/components/form/linkages/LinkagesSectionBuilder.php',
                'label' => 'linkages 能力块',
                'description' => '按 section key 组装 linkages 的完整示例能力块。',
            ]],
        ];
    }
}
