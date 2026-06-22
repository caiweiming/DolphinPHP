<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\linkage;

use app\common\render\Form;
use app\common\render\form\items\linkage\Linkage;
use app\common\render\form\items\select\Select;
use app\common\render\form\items\text\Text;

/**
 * linkage 能力块构建器
 */
final class LinkageSectionBuilder
{
    private const SHOWCASE_LINKAGE_URL = "showcase/admin.demo_api/options";

    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($section),
            'default_value' => $this->defaultValue($section),
            'level_labels' => $this->levelLabels($section),
            'level_url' => $this->levelUrl($section),
            'submit_all' => $this->submitAll($section),
            'multiple' => $this->multiple($section),
            'request_fields' => $this->requestFields($section),
            'profile_form' => $this->profile($section),
            default => $this->basic($section),
        };
    }

    private function basic(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'url / levels', 'value' => '远程联动最基础的两个核心参数'],
                ['name' => 'options', 'value' => '可给首级直接提供静态候选项'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'district',
        'label' => '地区',
        'url' => '/admin/api/regions',
        'options' => [
            'gd' => '广东省',
            'zj' => '浙江省',
        ],
        'levels' => [
            ['key' => 'province', 'label' => '省份', 'placeholder' => '请选择省份'],
            ['key' => 'city', 'label' => '城市', 'placeholder' => '请选择城市'],
            ['key' => 'district', 'label' => '区县', 'placeholder' => '请选择区县'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('district', '地区')
    ->url('/admin/api/regions')
    ->options([
        'gd' => '广东省',
        'zj' => '浙江省',
    ])
    ->levels([
        ['key' => 'province', 'label' => '省份', 'placeholder' => '请选择省份'],
        ['key' => 'city', 'label' => '城市', 'placeholder' => '请选择城市'],
        ['key' => 'district', 'label' => '区县', 'placeholder' => '请选择区县'],
    ]);
CODE,
            ['如果首级选项是固定的，直接给 `options` 能减少一次首屏远程请求。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_basic_', false), '基础远程联动')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('district', '地区')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->levels([
                                ['key' => 'region', 'label' => '大区', 'placeholder' => '请选择大区'],
                                ['key' => 'site', 'label' => '站点', 'placeholder' => '请选择站点'],
                                ['key' => 'team', 'label' => '团队', 'placeholder' => '请选择团队'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'value', 'value' => '支持按级别 key 传回填值'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'district',
        'label' => '地区',
        'url' => '/admin/api/regions',
        'value' => [
            'province' => 'gd',
            'city' => 'sz',
            'district' => 'nanshan',
        ],
        'levels' => [
            ['key' => 'province', 'label' => '省份'],
            ['key' => 'city', 'label' => '城市'],
            ['key' => 'district', 'label' => '区县'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('district', '地区')
    ->url('/admin/api/regions')
    ->value([
        'province' => 'gd',
        'city' => 'sz',
        'district' => 'nanshan',
    ])
    ->levels([
        ['key' => 'province', 'label' => '省份'],
        ['key' => 'city', 'label' => '城市'],
        ['key' => 'district', 'label' => '区县'],
    ]);
CODE,
            ['编辑页回填时，`value` 的 key 要和 `levels[].key` 保持一致。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_value_', false), '默认值与回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('district', '地区')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->value([
                                'region' => 'north',
                                'site' => 'north-bj',
                                'team' => 'north-bj-cy',
                            ])
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                ['key' => 'site', 'label' => '站点'],
                                ['key' => 'team', 'label' => '团队'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function levelLabels(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'levels[].label / placeholder', 'value' => '每一级都可以定义独立标题和占位文案'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'org_path',
        'label' => '组织路径',
        'url' => '/admin/api/orgTree',
        'levels' => [
            ['key' => 'group', 'label' => '事业群', 'placeholder' => '请选择事业群'],
            ['key' => 'dept', 'label' => '部门', 'placeholder' => '请选择部门'],
            ['key' => 'team', 'label' => '小组', 'placeholder' => '请选择小组'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('org_path', '组织路径')
    ->url('/admin/api/orgTree')
    ->levels([
        ['key' => 'group', 'label' => '事业群', 'placeholder' => '请选择事业群'],
        ['key' => 'dept', 'label' => '部门', 'placeholder' => '请选择部门'],
        ['key' => 'team', 'label' => '小组', 'placeholder' => '请选择小组'],
    ]);
CODE,
            ['`levels` 不只是决定字段 key，也决定了组件在界面上的语义表达。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_levels_', false), '级别占位符与标签')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('org_path', '组织路径')
                            ->url($showcaseUrl)
                            ->levels([
                                ['key' => 'region', 'label' => '大区', 'placeholder' => '请选择大区'],
                                ['key' => 'site', 'label' => '站点', 'placeholder' => '请选择站点'],
                                ['key' => 'team', 'label' => '团队', 'placeholder' => '请选择团队'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function levelUrl(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'levels[].url', 'value' => '可以对某一级单独覆盖接口地址'],
                ['name' => 'levels[].request_keys', 'value' => '控制上一级值以什么参数名提交'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'warehouse_area',
        'label' => '仓配区域',
        'url' => '/admin/api/getWarehouses',
        'levels' => [
            ['key' => 'warehouse', 'label' => '仓库'],
            [
                'key' => 'area',
                'label' => '配送区域',
                'url' => '/admin/api/getWarehouseAreas',
                'request_keys' => ['warehouse_id'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('warehouse_area', '仓配区域')
    ->url('/admin/api/getWarehouses')
    ->levels([
        ['key' => 'warehouse', 'label' => '仓库'],
        [
            'key' => 'area',
            'label' => '配送区域',
            'url' => '/admin/api/getWarehouseAreas',
            'request_keys' => ['warehouse_id'],
        ],
    ]);
CODE,
            ['当不同层级背后并不是同一张表或同一个接口时，这种写法最直接。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_url_', false), '按级别覆盖 url')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('warehouse_area', '仓配区域')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                [
                                    'key' => 'site',
                                    'label' => '站点',
                                    'url' => $showcaseUrl,
                                    'request_keys' => ['region'],
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function submitAll(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'submit_all(false)', 'value' => '只提交末级结果，前几级仅用于联动过程'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'district_id',
        'label' => '区县',
        'url' => '/admin/api/regions',
        'submit_all' => false,
        'levels' => [
            ['key' => 'province', 'label' => '省份'],
            ['key' => 'city', 'label' => '城市'],
            ['key' => 'district', 'label' => '区县'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('district_id', '区县')
    ->url('/admin/api/regions')
    ->submitAll(false)
    ->levels([
        ['key' => 'province', 'label' => '省份'],
        ['key' => 'city', 'label' => '城市'],
        ['key' => 'district', 'label' => '区县'],
    ]);
CODE,
            ['如果后端只关心末级 id，建议关闭 `submit_all` 简化提交结构。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_submit_', false), 'submit_all 与末级提交')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('district_id', '区县')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->submitAll(false)
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                ['key' => 'site', 'label' => '站点'],
                                ['key' => 'team', 'label' => '团队'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function multiple(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'multiple()', 'value' => '只对最后一级生效，让末级 select 支持多选'],
                ['name' => 'submit_all(false)', 'value' => '末级多选场景通常配合只提交末级数组结果'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'tag_ids',
        'label' => '内容标签',
        'url' => '/admin/api/contentTags',
        'submit_all' => false,
        'multiple' => true,
        'levels' => [
            ['key' => 'group', 'label' => '标签组'],
            ['key' => 'tags', 'label' => '标签'],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('tag_ids', '内容标签')
    ->url('/admin/api/contentTags')
    ->submitAll(false)
    ->multiple()
    ->levels([
        ['key' => 'group', 'label' => '标签组'],
        ['key' => 'tags', 'label' => '标签'],
    ]);
CODE,
            ['多选只会出现在最后一级，这一点和普通多层 select 的组合不同。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_multi_', false), 'multiple 末级多选')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Linkage::make('tag_ids', '内容标签')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->submitAll(false)
                            ->multiple()
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                ['key' => 'site', 'label' => '站点'],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function requestFields(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'levels[].request_fields', 'value' => '把上游字段值映射到自定义请求参数名'],
                ['name' => 'levels[].params', 'value' => '给某一级固定追加附带参数'],
            ],
            <<<'CODE'
[
    [
        'type' => 'linkage',
        'name' => 'channel_goods',
        'label' => '渠道商品',
        'url' => '/admin/api/channels',
        'levels' => [
            ['key' => 'channel', 'label' => '渠道'],
            [
                'key' => 'goods',
                'label' => '商品',
                'url' => '/admin/api/channelGoods',
                'request_fields' => ['channel_code' => 'channel'],
                'params' => ['status' => 1, 'scene' => 'showcase'],
            ],
        ],
    ],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::linkage('channel_goods', '渠道商品')
    ->url('/admin/api/channels')
    ->levels([
        ['key' => 'channel', 'label' => '渠道'],
        [
            'key' => 'goods',
            'label' => '商品',
            'url' => '/admin/api/channelGoods',
            'request_fields' => ['channel_code' => 'channel'],
            'params' => ['status' => 1, 'scene' => 'showcase'],
        ],
    ]);
CODE,
            ['这一块本质上是在声明“联动协议”，非常适合直接给开发者照抄。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_fields_', false), 'request_fields 与 params')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Select::make('channel', '渠道')
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->value('north')
                    )
                    ->item(
                        Linkage::make('channel_goods', '渠道商品')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                [
                                    'key' => 'site',
                                    'label' => '站点',
                                    'url' => $showcaseUrl,
                                    'request_fields' => ['scene' => 'channel'],
                                    'params' => ['status' => 1, 'scene' => 'showcase'],
                                ],
                            ])
                    )
                    ->fetch();
            }
        );
    }

    private function profile(array $section): array
    {
        $showcaseUrl = (string) dp_url(self::SHOWCASE_LINKAGE_URL, ['dataset' => 'linkage']);

        return $this->wrap(
            $section,
            [
                ['name' => 'channel / warehouse_area / sync_mode', 'value' => '渠道与履约范围配置的典型组合'],
            ],
            <<<'CODE'
[
    [
        'type' => 'select',
        'name' => 'channel',
        'label' => '运营渠道',
        'options' => ['mall' => '商城', 'douyin' => '抖音', 'wx' => '微信小店'],
        'value' => 'mall',
    ],
    [
        'type' => 'linkage',
        'name' => 'warehouse_area',
        'label' => '仓配区域',
        'url' => '/admin/api/getWarehouses',
        'levels' => [
            ['key' => 'warehouse', 'label' => '仓库'],
            ['key' => 'area', 'label' => '配送区域', 'url' => '/admin/api/getWarehouseAreas'],
        ],
    ],
    ['type' => 'text', 'name' => 'sync_mode', 'label' => '同步说明', 'value' => '保存后按渠道重建可配送区域缓存'],
]
CODE,
            <<<'CODE'
use app\common\render\form\Field;

Field::select('channel', '运营渠道')
    ->options(['mall' => '商城', 'douyin' => '抖音', 'wx' => '微信小店'])
    ->value('mall');

Field::linkage('warehouse_area', '仓配区域')
    ->url('/admin/api/getWarehouses')
    ->levels([
        ['key' => 'warehouse', 'label' => '仓库'],
        ['key' => 'area', 'label' => '配送区域', 'url' => '/admin/api/getWarehouseAreas'],
    ]);

Field::text('sync_mode', '同步说明')
    ->value('保存后按渠道重建可配送区域缓存');
CODE,
            ['linkage 很少单独存在，通常都是某个更大业务配置流程的一环。'],
            function () use ($showcaseUrl): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_linkage_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        Select::make('channel', '运营渠道')
                            ->options(['mall' => '商城', 'douyin' => '抖音', 'wx' => '微信小店'])
                            ->value('mall')
                    )
                    ->item(
                        Linkage::make('warehouse_area', '仓配区域')
                            ->url($showcaseUrl)
                            ->options([
                                'north' => '华北大区',
                                'east' => '华东大区',
                            ])
                            ->levels([
                                ['key' => 'region', 'label' => '大区'],
                                ['key' => 'site', 'label' => '站点', 'url' => $showcaseUrl],
                            ])
                    )
                    ->item(
                        Text::make('sync_mode', '同步说明')
                            ->value('保存后按渠道重建可配送区域缓存')
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
                'path' => 'app/showcase/service/components/form/linkage/LinkageSectionBuilder.php',
                'label' => 'linkage 能力块',
                'description' => '按 section key 组装 linkage 的完整示例能力块。',
            ]],
        ];
    }
}
