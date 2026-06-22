<?php
declare(strict_types=1);

namespace app\showcase\service\components\form\map;

use app\common\render\Form;
use app\common\render\form\items\amap\Amap;
use app\common\render\form\items\bmap\Bmap;
use app\common\render\form\items\qmap\Qmap;
use app\common\render\form\items\text\Text;

/**
 * 地图组件能力块构建器
 */
final class MapSectionBuilder
{
    public function build(array $component, array $section): array
    {
        $key = (string) ($section['key'] ?? 'basic');

        return match ($key) {
            'basic' => $this->basic($component, $section),
            'default_value' => $this->defaultValue($component, $section),
            'address_init' => $this->addressInit($component, $section),
            'center_zoom' => $this->centerZoom($component, $section),
            'address_field' => $this->addressField($component, $section),
            'readonly' => $this->readonly($component, $section),
            'vendor_options' => $this->vendorOptions($component, $section),
            'profile_form' => $this->profile($component, $section),
            default => $this->basic($component, $section),
        };
    }

    private function basic(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory'] . $meta['vendor_chain_basic'] . ';';

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => '坐标字段 + 地图 Key', 'value' => '最小可用地图组件至少要有字段名和厂商鉴权信息'],
            ],
            $meta['array_basic'],
            $fieldCode,
            ['地图类组件最核心的结果是一个坐标字段和一个地址字段。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_basic_', false), '基础选点')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item($this->makeMapItem($meta, 'basic')->tips('点击地图或搜索地址'))
                    ->fetch();
            }
        );
    }

    private function defaultValue(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->value('{$meta['sample_value']}')"
            . "\n    ->address('{$meta['sample_address']}');";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'value / address', 'value' => '编辑页可同时回显已有坐标和地址文本'],
            ],
            $meta['array_value'],
            $fieldCode,
            ['存在已保存坐标时，通常还会顺带把地址文本一起回显给开发者确认。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_value_', false), '默认值与地址回填')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        $this->makeMapItem($meta, 'default_value')
                            ->value($meta['sample_value'])
                            ->address($meta['sample_address'])
                    )
                    ->fetch();
            }
        );
    }

    private function centerZoom(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->center('{$meta['sample_center']}')"
            . "\n    ->zoom(12)"
            . "\n    ->height(420);";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'center / zoom / height', 'value' => '控制地图默认视野和画布高度'],
            ],
            $meta['array_center'],
            $fieldCode,
            ['地图画布较小时，适当提高高度会显著改善选点体验。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_center_', false), '中心点与缩放')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        $this->makeMapItem($meta, 'center_zoom')
                            ->center($meta['sample_center'])
                            ->zoom(12)
                            ->height(420)
                    )
                    ->fetch();
            }
        );
    }

    private function addressInit(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->address('{$meta['sample_address']}');";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'address', 'value' => '在没有坐标值时，仅靠地址文本初始化地图位置'],
            ],
            $meta['array_address_init'],
            $fieldCode,
            ['地址初始化特别适合“已有地址文案，但尚未存坐标”的旧数据迁移场景。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_address_init_', false), '仅地址初始化')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        $this->makeMapItem($meta, 'address_init')
                            ->address($meta['sample_address'])
                    )
                    ->fetch();
            }
        );
    }

    private function addressField(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->addressField('store_address')"
            . "\n    ->placeholder('请输入门店地址关键词');";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'address_field', 'value' => '自定义提交到后端的地址字段名'],
                ['name' => 'placeholder', 'value' => '引导搜索框输入更明确的地址关键词'],
            ],
            $meta['array_address_field'],
            $fieldCode,
            ['如果表里已经有固定地址字段命名，建议显式配置 `address_field`。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_address_', false), '地址字段与占位符')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        $this->makeMapItem($meta, 'address_field')
                            ->addressField('store_address')
                            ->placeholder('请输入门店地址关键词')
                    )
                    ->fetch();
            }
        );
    }

    private function readonly(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n" . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->value('{$meta['sample_value']}')"
            . "\n    ->address('{$meta['sample_address']}')"
            . "\n    ->readonly();";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'readonly', 'value' => '详情页和审批页只展示位置，不允许再次修改'],
            ],
            $meta['array_readonly'],
            $fieldCode,
            ['只读模式通常用于“看位置”而不是“改位置”的页面。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_readonly_', false), '只读模式')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(
                        $this->makeMapItem($meta, 'readonly')
                            ->value($meta['sample_value'])
                            ->address($meta['sample_address'])
                            ->readonly()
                    )
                    ->fetch();
            }
        );
    }

    private function vendorOptions(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));

        return $this->wrap(
            $component,
            $section,
            $meta['vendor_params'],
            $meta['vendor_array'],
            $meta['vendor_field'],
            $meta['vendor_notes'],
            function () use ($meta, $component, $section): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_vendor_', false), (string) ($section['title'] ?? '厂商配置'))
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(($meta['vendor_preview'])($this->makeMapItem($meta, 'vendor_options')))
                    ->fetch();
            }
        );
    }

    private function profile(array $component, array $section): array
    {
        $meta = $this->meta((string) ($component['key'] ?? ''));
        $fieldCode = $meta['field_import'] . "\n\n"
            . "Field::text('store_name', '门店名称', '请输入门店名称');\n\n"
            . $meta['field_factory']
            . $meta['vendor_chain_basic']
            . "\n    ->addressField('store_address');";

        return $this->wrap(
            $component,
            $section,
            [
                ['name' => 'store_name / location / store_address', 'value' => '门店信息和地图选点组合是最典型业务落点'],
            ],
            $meta['profile_array'],
            $fieldCode,
            ['地图选点通常和门店、仓库、网点等基础信息字段一起出现。'],
            function () use ($meta): string {
                Form::clearInstances();
                return Form::make(uniqid('showcase_map_profile_', false), '业务表单片段')
                    ->header(false)
                    ->template(root_path() . 'app/common/render/form/layout.html')
                    ->item(Text::make('store_name', '门店名称', '请输入门店名称'))
                    ->item(
                        $this->makeMapItem($meta, 'profile_form')
                            ->addressField('store_address')
                    )
                    ->fetch();
            }
        );
    }

    private function meta(string $key): array
    {
        return match ($key) {
            'rich.amap' => [
                'type' => 'amap',
                'class' => Amap::class,
                'label' => '高德地图',
                'field_import' => "use app\\common\\render\\form\\Field;",
                'field_factory' => "Field::amap('location', '高德地图')",
                'vendor_chain_basic' => "\n    ->key('your-amap-key')\n    ->securityJsCode('your-amap-security-code')",
                'sample_value' => '113.2644,23.1291',
                'sample_address' => '广东省广州市越秀区中山纪念堂',
                'sample_center' => '113.2644,23.1291',
                'array_basic' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'tips' => '点击地图或搜索地址',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
                'array_value' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'value' => '113.2644,23.1291',
        'address' => '广东省广州市越秀区中山纪念堂',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
                'array_center' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
            'center' => '113.2644,23.1291',
            'zoom' => 12,
            'height' => 420,
        ],
    ],
]
CODE,
                'array_address_init' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'address' => '广东省广州市越秀区中山纪念堂',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
                'array_address_field' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'address_field' => 'store_address',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
            'placeholder' => '请输入门店地址关键词',
        ],
    ],
]
CODE,
                'array_readonly' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'value' => '113.2644,23.1291',
        'address' => '广东省广州市越秀区中山纪念堂',
        'readonly' => true,
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
                'vendor_params' => [
                    ['name' => 'options.key', 'value' => '高德地图 JS API Key'],
                    ['name' => 'options.securityJsCode', 'value' => '高德地图安全密钥'],
                    ['name' => 'options.web_key', 'value' => 'WebService 回退时可额外配置 web_key'],
                ],
                'vendor_array' => <<<'CODE'
[
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
            'web_key' => 'your-amap-web-key',
        ],
    ],
]
CODE,
                'vendor_field' => <<<'CODE'
use app\common\render\form\Field;

Field::amap('location', '高德地图')
    ->key('your-amap-key')
    ->securityJsCode('your-amap-security-code')
    ->options([
        'web_key' => 'your-amap-web-key',
    ]);
CODE,
                'vendor_notes' => ['高德地图场景下必须配置真实高德 Key 和 `securityJsCode` 才能正常使用；复杂项目还会补一个 `web_key` 兜底。'],
                'vendor_preview' => static fn(Amap $item): Amap => $item->options(['web_key' => 'your-amap-web-key']),
                'profile_array' => <<<'CODE'
[
    ['type' => 'text', 'name' => 'store_name', 'label' => '门店名称', 'tips' => '请输入门店名称'],
    [
        'type' => 'amap',
        'name' => 'location',
        'label' => '高德地图',
        'address_field' => 'store_address',
        'options' => [
            'key' => 'your-amap-key',
            'securityJsCode' => 'your-amap-security-code',
        ],
    ],
]
CODE,
            ],
            'rich.bmap' => [
                'type' => 'bmap',
                'class' => Bmap::class,
                'label' => '百度地图',
                'field_import' => "use app\\common\\render\\form\\Field;",
                'field_factory' => "Field::bmap('location', '百度地图')",
                'vendor_chain_basic' => "\n    ->ak('your-bmap-ak')",
                'sample_value' => '116.404,39.915',
                'sample_address' => '北京市西城区西长安街2号',
                'sample_center' => '116.404,39.915',
                'array_basic' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'tips' => '点击地图或搜索地址',
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
                'array_value' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'value' => '116.404,39.915',
        'address' => '北京市西城区西长安街2号',
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
                'array_center' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'options' => [
            'ak' => 'your-bmap-ak',
            'center' => '116.404,39.915',
            'zoom' => 12,
            'height' => 420,
        ],
    ],
]
CODE,
                'array_address_init' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'address' => '北京市西城区西长安街2号',
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
                'array_address_field' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'address_field' => 'store_address',
        'options' => [
            'ak' => 'your-bmap-ak',
            'placeholder' => '请输入门店地址关键词',
        ],
    ],
]
CODE,
                'array_readonly' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'value' => '116.404,39.915',
        'address' => '北京市西城区西长安街2号',
        'readonly' => true,
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
                'vendor_params' => [
                    ['name' => 'options.ak', 'value' => '百度地图 AK'],
                    ['name' => 'options.region', 'value' => '限制搜索联想区域'],
                    ['name' => 'options.geocoderParams', 'value' => '透传逆地址解析参数以调整地址返回结果'],
                ],
                'vendor_array' => <<<'CODE'
[
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'options' => [
            'ak' => 'your-bmap-ak',
            'region' => '北京',
            'geocoderParams' => [
                'extensions_poi' => 1,
            ],
        ],
    ],
]
CODE,
                'vendor_field' => <<<'CODE'
use app\common\render\form\Field;

Field::bmap('location', '百度地图')
    ->ak('your-bmap-ak')
    ->options([
        'region' => '北京',
        'geocoderParams' => [
            'extensions_poi' => 1,
        ],
    ]);
CODE,
                'vendor_notes' => ['百度地图场景下必须配置真实百度 AK 才能正常使用，常见做法是结合 `region` 缩小搜索联想范围，并用 `geocoderParams` 调整逆地址细节。'],
                'vendor_preview' => static fn(Bmap $item): Bmap => $item->options([
                    'region' => '北京',
                    'geocoderParams' => ['extensions_poi' => 1],
                ]),
                'profile_array' => <<<'CODE'
[
    ['type' => 'text', 'name' => 'store_name', 'label' => '门店名称', 'tips' => '请输入门店名称'],
    [
        'type' => 'bmap',
        'name' => 'location',
        'label' => '百度地图',
        'address_field' => 'store_address',
        'options' => [
            'ak' => 'your-bmap-ak',
        ],
    ],
]
CODE,
            ],
            default => [
                'type' => 'qmap',
                'class' => Qmap::class,
                'label' => '腾讯地图',
                'field_import' => "use app\\common\\render\\form\\Field;",
                'field_factory' => "Field::qmap('location', '腾讯地图')",
                'vendor_chain_basic' => "\n    ->key('your-qmap-key')",
                'sample_value' => '113.3245,23.1066',
                'sample_address' => '广东省广州市天河区珠江新城',
                'sample_center' => '113.3245,23.1066',
                'array_basic' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'tips' => '点击地图或搜索地址',
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
                'array_value' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'value' => '113.3245,23.1066',
        'address' => '广东省广州市天河区珠江新城',
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
                'array_center' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'options' => [
            'key' => 'your-qmap-key',
            'center' => '113.3245,23.1066',
            'zoom' => 12,
            'height' => 420,
        ],
    ],
]
CODE,
                'array_address_init' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'address' => '广东省广州市天河区珠江新城',
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
                'array_address_field' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'address_field' => 'store_address',
        'options' => [
            'key' => 'your-qmap-key',
            'placeholder' => '请输入门店地址关键词',
        ],
    ],
]
CODE,
                'array_readonly' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'value' => '113.3245,23.1066',
        'address' => '广东省广州市天河区珠江新城',
        'readonly' => true,
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
                'vendor_params' => [
                    ['name' => 'options.key', 'value' => '腾讯地图 Key'],
                    ['name' => 'options.get_poi', 'value' => '控制逆地址解析时是否返回 POI'],
                    ['name' => 'options.geocoderParams', 'value' => '透传逆地址解析参数以控制返回地址格式'],
                ],
                'vendor_array' => <<<'CODE'
[
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'options' => [
            'key' => 'your-qmap-key',
            'get_poi' => 1,
            'geocoderParams' => [
                'poi_options' => 'address_format=short',
            ],
        ],
    ],
]
CODE,
                'vendor_field' => <<<'CODE'
use app\common\render\form\Field;

Field::qmap('location', '腾讯地图')
    ->key('your-qmap-key')
    ->options([
        'get_poi' => 1,
        'geocoderParams' => [
            'poi_options' => 'address_format=short',
        ],
    ]);
CODE,
                'vendor_notes' => ['腾讯地图场景下必须配置真实腾讯 Key 才能正常使用，`get_poi` 常用于改善逆地址回填结果，`geocoderParams` 可进一步约束返回格式。'],
                'vendor_preview' => static fn(Qmap $item): Qmap => $item->options([
                    'get_poi' => 1,
                    'geocoderParams' => ['poi_options' => 'address_format=short'],
                ]),
                'profile_array' => <<<'CODE'
[
    ['type' => 'text', 'name' => 'store_name', 'label' => '门店名称', 'tips' => '请输入门店名称'],
    [
        'type' => 'qmap',
        'name' => 'location',
        'label' => '腾讯地图',
        'address_field' => 'store_address',
        'options' => [
            'key' => 'your-qmap-key',
        ],
    ],
]
CODE,
            ],
        };
    }

    private function makeMapItem(array $meta, string $sectionKey): Amap|Bmap|Qmap
    {
        /** @var class-string<Amap|Bmap|Qmap> $class */
        $class = $meta['class'];
        /** @var Amap|Bmap|Qmap $item */
        $item = $class::make('location', $meta['label'], '点击地图或搜索地址')
            ->id('location_' . $meta['type'] . '_' . $sectionKey);

        return match ($meta['type']) {
            'amap' => $item->key('your-amap-key')->securityJsCode('your-amap-security-code'),
            'bmap' => $item->ak('your-bmap-ak'),
            default => $item->key('your-qmap-key'),
        };
    }

    private function wrap(array $component, array $section, array $params, string $arrayCode, string $fieldCode, array $notes, callable $previewBuilder): array
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
                'path' => 'app/showcase/service/components/form/map/MapSectionBuilder.php',
                'label' => '地图能力块',
                'description' => '按厂商和 section key 组装地图组件的完整示例能力块。',
            ]],
        ];
    }
}
