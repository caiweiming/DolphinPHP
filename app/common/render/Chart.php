<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\render;

use app\common\abstract\ChartType as ChartTypeAbstract;
use app\common\abstract\ChartMapProvider as ChartMapProviderAbstract;
use app\common\plugin\PluginRegistry;
use app\common\render\chart\type\Bar;
use app\common\render\chart\type\Line;
use app\common\render\chart\type\Pie;
use app\common\render\chart\type\Scatter;
use Exception;
use think\Container;
use think\facade\Config;
use app\common\interface\ChartRender as ChartRenderInterface;
use app\common\interface\ChartMapProvider as ChartMapProviderInterface;
use app\common\interface\ChartTypeBuilder as ChartTypeBuilderInterface;

/**
 * 图表渲染器
 * @package app\common\render
 */
class Chart extends Common implements ChartRenderInterface
{
    /**
     * 默认图表类型构建器
     * @var array<string, class-string>
     */
    private const DEFAULT_TYPE_BUILDERS = [
        'line'    => Line::class,
        'bar'     => Bar::class,
        'pie'     => Pie::class,
        'scatter' => Scatter::class,
    ];

    /**
     * 图表实例集合
     * @var array<string, static>
     */
    protected static array $instances = [];

    /**
     * 图表类型构建器实例缓存
     * @var array<string, ChartTypeBuilderInterface>
     */
    protected array $typeBuilderInstances = [];

    /**
     * 地图专项 Provider 实例缓存
     * @var array<string, ChartMapProviderInterface>
     */
    protected array $mapProviderInstances = [];

    /**
     * 模板变量
     * @var array<string, mixed>
     */
    protected array $vars = [
        'dp_chart_id'             => 'dp_chart',
        'dp_chart_class'          => [],
        'dp_chart_attr'           => [],
        'dp_chart_style'          => [],
        'dp_chart_title'          => '',
        'dp_chart_subtitle'       => '',
        'dp_chart_renderer'       => 'canvas',
        'dp_chart_theme'          => '',
        'dp_chart_type'           => 'line',
        'dp_chart_categories'     => [],
        'dp_chart_series'         => [],
        'dp_chart_option'         => [],
        'dp_chart_option_replace' => false,
        'dp_chart_dataset_url'    => '',
        'dp_chart_dataset_method' => 'GET',
        'dp_chart_dataset_params' => [],
        'dp_chart_dataset_merge'  => true,
        'dp_chart_loading'        => [
            'show' => false,
            'text' => '',
        ],
        'dp_chart_empty'          => [
            'title'       => '暂无数据',
            'description' => '当前图表暂无可展示的数据',
        ],
        'dp_chart_height'         => '320px',
        'dp_chart_min_height'     => '240px',
        'dp_chart_payload'        => '',
        'dp_chart_map_key'        => '',
        'dp_chart_map_data'       => [],
        'dp_file_css'             => [],
        'dp_file_js'              => [],
        'dp_extra_css'            => [],
        'dp_extra_js'             => [],
        'dp_init_js'              => [],
    ];

    /**
     * 初始化
     */
    protected function initialize(): void
    {
        parent::initialize();

        $this->vars['dp_chart_renderer']       = (string)Config::get('chart.renderer', 'canvas');
        $this->vars['dp_chart_theme']          = (string)Config::get('chart.theme', '');
        $this->vars['dp_chart_height']         = $this->normalizeSize(Config::get('chart.height', '320px'), '320px');
        $this->vars['dp_chart_min_height']     = $this->normalizeSize(Config::get('chart.min_height', '240px'), '240px');
        $this->vars['dp_chart_loading']        = array_replace_recursive(
            $this->vars['dp_chart_loading'],
            (array)Config::get('chart.loading', [])
        );
        $this->vars['dp_chart_empty']          = array_replace_recursive(
            $this->vars['dp_chart_empty'],
            (array)Config::get('chart.empty', [])
        );
        $this->vars['dp_chart_option']         = (array)Config::get('chart.option', []);
        $this->vars['dp_chart_option_replace'] = false;
    }

    /**
     * 创建图表实例 已经存在则直接获取
     * @param string $id 图表id
     * @return static
     * @throws Exception
     */
    public function init(string $id = ''): static
    {
        if ($id === '') {
            throw new Exception('未定义图表 ID');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new Exception('图表 ID 格式无效：' . $id);
        }

        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }

        $object                      = Container::getInstance()->make(static::class, [], true);
        $object->vars['dp_chart_id'] = $id;
        self::$instances[$id]        = $object;
        return $object;
    }

    /**
     * 静态方式创建图表实例
     * @param string $id 图表id
     * @return static
     * @throws Exception
     */
    public static function make(string $id = ''): static
    {
        $object = Container::getInstance()->make(static::class);
        return $object->init($id);
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed|null $value 变量值
     * @return static
     */
    public function assign(string|array $name, mixed $value = null): static
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array $vars 模板变量
     * @return string
     */
    public function display(string $content, array $vars = []): string
    {
        $vars = array_merge($this->vars, $vars);
        return $this->view->display($content, $vars);
    }

    /**
     * 渲染模板文件
     * @param string $template 模板文件
     * @param array $vars 模板变量
     * @return string
     * @throws Exception
     */
    public function fetch(string $template = '', array $vars = []): string
    {
        $this->registerCoreAssets();
        $this->registerTypeAssets();
        $this->registerMapAssets();

        $this->compile();

        return $this->finalizeFetch($template, $vars);
    }

    /**
     * 获取静态资源
     * @return array
     * @throws Exception
     */
    public function assets(): array
    {
        $this->registerCoreAssets();
        $this->registerTypeAssets();
        $this->registerMapAssets();

        return $this->assetManager->getAssets();
    }

    /**
     * 设置图表id
     * @param string $id
     * @return static
     */
    public function id(string $id = ''): static
    {
        if ($id !== '') {
            $this->vars['dp_chart_id'] = $id;
        }
        return $this;
    }

    /**
     * 设置图表标题
     * @param string $value
     * @param string $subtitle
     * @return static
     */
    public function title(string $value = '', string $subtitle = ''): static
    {
        $this->vars['dp_chart_title']    = $value;
        $this->vars['dp_chart_subtitle'] = $subtitle;
        return $this;
    }

    /**
     * 设置图表副标题
     * @param string $value
     * @return static
     */
    public function subtitle(string $value = ''): static
    {
        $this->vars['dp_chart_subtitle'] = $value;
        return $this;
    }

    /**
     * 设置图表类型
     * @param string $type
     * @return static
     * @throws Exception
     */
    public function type(string $type = 'line'): static
    {
        $type = dp_normalize_extension_path($type);
        if ($this->hasTypeBuilder($type)) {
            $this->vars['dp_chart_type'] = $type;
        }
        return $this;
    }

    /**
     * 设置地图专项扩展
     * @param string $mapKey
     * @param array $data
     * @return static
     */
    public function map(string $mapKey = '', array $data = []): static
    {
        $this->vars['dp_chart_map_key'] = dp_normalize_extension_path($mapKey);
        if ($data !== []) {
            $this->vars['dp_chart_map_data'] = $data;
        }

        return $this;
    }

    /**
     * 设置地图专项数据
     * @param array $data
     * @param bool $merge
     * @return static
     */
    public function mapData(array $data = [], bool $merge = true): static
    {
        if ($data === []) {
            return $this;
        }

        if ($merge) {
            $this->vars['dp_chart_map_data'] = array_replace_recursive($this->vars['dp_chart_map_data'], $data);
        } else {
            $this->vars['dp_chart_map_data'] = $data;
        }

        return $this;
    }

    /**
     * 设置图表主题
     * @param string $theme
     * @return static
     */
    public function theme(string $theme = ''): static
    {
        $this->vars['dp_chart_theme'] = $theme;
        return $this;
    }

    /**
     * 设置渲染器
     * @param string $renderer
     * @return static
     */
    public function renderer(string $renderer = 'canvas'): static
    {
        $renderer = strtolower(trim($renderer));
        if (in_array($renderer, ['canvas', 'svg'], true)) {
            $this->vars['dp_chart_renderer'] = $renderer;
        }
        return $this;
    }

    /**
     * 设置图表高度
     * @param string|int $height
     * @return static
     */
    public function height(string|int $height): static
    {
        $this->vars['dp_chart_height'] = $this->normalizeSize($height, $this->vars['dp_chart_height']);
        return $this;
    }

    /**
     * 设置图表最小高度
     * @param string|int $height
     * @return static
     */
    public function minHeight(string|int $height): static
    {
        $this->vars['dp_chart_min_height'] = $this->normalizeSize($height, $this->vars['dp_chart_min_height']);
        return $this;
    }

    /**
     * 设置容器类名
     * @param string|array $class
     * @return static
     */
    public function class(string|array $class = ''): static
    {
        if ($class === '' || $class === []) {
            return $this;
        }

        $class                        = is_array($class) ? $class : (preg_split('/\s+/', trim($class)) ?: []);
        $this->vars['dp_chart_class'] = array_values(array_unique(array_merge($this->vars['dp_chart_class'], $class)));
        return $this;
    }

    /**
     * 设置容器属性
     * @param array $attr
     * @return static
     */
    public function attr(array $attr = []): static
    {
        if ($attr !== []) {
            $this->vars['dp_chart_attr'] = array_merge($this->vars['dp_chart_attr'], $attr);
        }
        return $this;
    }

    /**
     * 设置容器样式
     * @param string|array $style
     * @return static
     */
    public function style(string|array $style = []): static
    {
        if ($style === '' || $style === []) {
            return $this;
        }

        if (is_string($style)) {
            $this->vars['dp_chart_style'][] = $style;
        } else {
            $this->vars['dp_chart_style'] = array_merge($this->vars['dp_chart_style'], $style);
        }

        return $this;
    }

    /**
     * 设置图例
     * @param bool|array $legend
     * @return static
     */
    public function legend(bool|array $legend = true): static
    {
        $this->vars['dp_chart_option']['legend'] = is_bool($legend) ? ['show' => $legend] : $legend;
        return $this;
    }

    /**
     * 设置类目
     * @param array $categories
     * @return static
     */
    public function categories(array $categories = []): static
    {
        $this->vars['dp_chart_categories'] = array_values($categories);
        return $this;
    }

    /**
     * 设置系列
     * @param array $series
     * @return static
     */
    public function series(array $series = []): static
    {
        $this->vars['dp_chart_series'] = $series;
        return $this;
    }

    /**
     * 设置原生图表配置
     * @param array $option
     * @param bool $replace
     * @return static
     */
    public function option(array $option = [], bool $replace = false): static
    {
        if ($replace) {
            $this->vars['dp_chart_option_replace'] = true;
            $this->vars['dp_chart_option']         = $option;
            return $this;
        }

        $this->vars['dp_chart_option'] = array_replace_recursive($this->vars['dp_chart_option'], $option);
        return $this;
    }

    /**
     * 设置图表数据源
     * @param string $url
     * @param string $method
     * @param array $params
     * @return static
     */
    public function dataset(string $url = '', string $method = 'GET', array $params = []): static
    {
        $this->vars['dp_chart_dataset_url']    = $url;
        $this->vars['dp_chart_dataset_method'] = strtoupper($method);
        $this->vars['dp_chart_dataset_params'] = $params;

        if ($url !== '') {
            $this->vars['dp_chart_loading']['show'] = true;
        }

        return $this;
    }

    /**
     * 设置是否合并 Ajax 数据源 option
     * @param bool $merge
     * @return static
     */
    public function datasetMerge(bool $merge = true): static
    {
        $this->vars['dp_chart_dataset_merge'] = $merge;
        return $this;
    }

    /**
     * 设置加载态
     * @param bool|array $loading
     * @return static
     */
    public function loading(bool|array $loading = true): static
    {
        if (is_bool($loading)) {
            $this->vars['dp_chart_loading']['show'] = $loading;
        } else {
            $this->vars['dp_chart_loading'] = array_replace_recursive($this->vars['dp_chart_loading'], $loading);
        }
        return $this;
    }

    /**
     * 设置空态
     * @param string|array $empty
     * @return static
     */
    public function empty(string|array $empty = ''): static
    {
        if (is_string($empty)) {
            $this->vars['dp_chart_empty']['description'] = $empty;
        } elseif ($empty !== []) {
            $this->vars['dp_chart_empty'] = array_replace_recursive($this->vars['dp_chart_empty'], $empty);
        }
        return $this;
    }

    /**
     * 编译图表
     * @return void
     * @throws Exception
     */
    private function compile(): void
    {
        $this->getLayoutTemplate('chart');

        $classes = ['dp-chart-card'];
        if (!empty($this->vars['dp_chart_class'])) {
            $classes = array_merge($classes, (array)$this->vars['dp_chart_class']);
        }
        $this->vars['dp_chart_class']   = implode(' ', array_unique(array_filter($classes)));
        $this->vars['dp_chart_attr']    = dp_arr2str($this->vars['dp_chart_attr']);
        $this->vars['dp_chart_style']   = $this->buildStyleString();
        $this->vars['dp_chart_payload'] = dp_parse_options($this->buildPayload());
    }

    /**
     * 构建图表载荷
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildPayload(): array
    {
        return [
            'id'          => $this->vars['dp_chart_id'],
            'renderer'    => $this->vars['dp_chart_renderer'],
            'theme'       => $this->vars['dp_chart_theme'],
            'type'        => $this->vars['dp_chart_type'],
            'option'      => $this->buildOption(),
            'typePayload' => $this->buildTypePayload(),
            'map'         => $this->buildMapPayload(),
            'dataset'     => [
                'url'    => $this->vars['dp_chart_dataset_url'],
                'method' => $this->vars['dp_chart_dataset_method'],
                'params' => $this->vars['dp_chart_dataset_params'],
                'merge'  => $this->vars['dp_chart_dataset_merge'],
            ],
            'loading'     => $this->vars['dp_chart_loading'],
            'empty'       => $this->vars['dp_chart_empty'],
        ];
    }

    /**
     * 构建图表配置
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildOption(): array
    {
        if ($this->vars['dp_chart_option_replace']) {
            return $this->vars['dp_chart_option'];
        }

        $defaultOption = (array)Config::get('chart.option', []);
        $typeOption    = $this->hasMap() ? [] : $this->buildTypeOption();

        return array_replace_recursive($defaultOption, $typeOption, $this->vars['dp_chart_option']);
    }

    /**
     * 构建类型配置
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildTypeOption(): array
    {
        $builder = $this->resolveTypeBuilder($this->vars['dp_chart_type']);
        if ($builder === null) {
            return [];
        }

        return $builder->build($this->buildTypeContext());
    }

    /**
     * 构建类型扩展载荷
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildTypePayload(): array
    {
        $builder = $this->resolveTypeBuilder($this->vars['dp_chart_type']);
        if ($builder === null) {
            return [];
        }

        return $builder->payload($this->buildTypeContext());
    }

    /**
     * 构建图表类型上下文
     * @return array<string, mixed>
     */
    private function buildTypeContext(): array
    {
        return [
            'id'         => $this->vars['dp_chart_id'],
            'type'       => $this->vars['dp_chart_type'],
            'categories' => $this->vars['dp_chart_categories'],
            'series'     => $this->vars['dp_chart_series'],
            'option'     => $this->vars['dp_chart_option'],
        ];
    }

    /**
     * 是否启用地图专项扩展
     * @return bool
     */
    private function hasMap(): bool
    {
        return trim((string)$this->vars['dp_chart_map_key']) !== '';
    }

    /**
     * 构建地图专项上下文
     * @return array<string, mixed>
     */
    private function buildMapContext(): array
    {
        return [
            'id'      => $this->vars['dp_chart_id'],
            'type'    => $this->vars['dp_chart_type'],
            'mapKey'  => $this->vars['dp_chart_map_key'],
            'mapData' => $this->vars['dp_chart_map_data'],
            'option'  => $this->vars['dp_chart_option'],
        ];
    }

    /**
     * 构建地图专项载荷
     * @return array<string, mixed>
     * @throws Exception
     */
    private function buildMapPayload(): array
    {
        if (!$this->hasMap()) {
            return ['enabled' => false];
        }

        $provider = $this->resolveMapProvider((string)$this->vars['dp_chart_map_key']);
        if ($provider === null) {
            throw new Exception('未找到地图扩展 Provider：' . $this->vars['dp_chart_map_key']);
        }

        $context = $this->buildMapContext();

        return [
            'enabled'         => true,
            'mapKey'          => $this->vars['dp_chart_map_key'],
            'meta'            => $provider->meta($context),
            'definition'      => $provider->definition($context),
            'data'            => $provider->normalize((array)$this->vars['dp_chart_map_data'], $context),
            'providerPayload' => $provider->payload($context),
        ];
    }

    /**
     * 获取类型构建器映射
     * @return array<string, class-string>
     */
    private function getTypeBuilders(): array
    {
        $builders = [];
        foreach ((array)Config::get('chart.types', []) as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $builders[dp_normalize_extension_path($key)] = $value;
        }

        return $builders;
    }

    /**
     * 获取地图扩展 Provider 映射
     * @return array<string, class-string>
     */
    private function getMapProviders(): array
    {
        $providers = [];
        foreach ((array)Config::get('chart.maps', []) as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $providers[dp_normalize_extension_path($key)] = $value;
        }

        return $providers;
    }

    /**
     * 是否存在类型构建器
     * @param string $type
     * @return bool
     * @throws Exception
     */
    private function hasTypeBuilder(string $type): bool
    {
        return $this->resolveTypeBuilderClass($type) !== '';
    }

    /**
     * 解析类型构建器类
     * @param string $type
     * @return string
     * @throws Exception
     */
    private function resolveTypeBuilderClass(string $type): string
    {
        $type = dp_normalize_extension_path($type);

        $pluginClass = app(PluginRegistry::class)->getChartTypeClass($type);
        if ($pluginClass !== '') {
            return $pluginClass;
        }

        $builders = $this->getTypeBuilders();
        if (isset($builders[$type]) && is_string($builders[$type]) && $builders[$type] !== '') {
            return $builders[$type];
        }

        $extendClass = $this->resolveExtendTypeBuilderClass($type);
        if ($extendClass !== '') {
            return $extendClass;
        }

        $builderClass = self::DEFAULT_TYPE_BUILDERS[$type] ?? '';
        return is_string($builderClass) ? $builderClass : '';
    }

    /**
     * 解析地图扩展 Provider 类
     * @param string $mapKey
     * @return string
     * @throws Exception
     */
    private function resolveMapProviderClass(string $mapKey): string
    {
        $mapKey = dp_normalize_extension_path($mapKey);

        $pluginClass = app(PluginRegistry::class)->getChartMapClass($mapKey);
        if ($pluginClass !== '') {
            return $pluginClass;
        }

        $providers = $this->getMapProviders();
        if (isset($providers[$mapKey]) && is_string($providers[$mapKey]) && $providers[$mapKey] !== '') {
            return $providers[$mapKey];
        }

        return $this->resolveExtendMapProviderClass($mapKey);
    }

    /**
     * 解析扩展类型构建器类
     * @param string $type
     * @return string
     * @throws Exception
     */
    private function resolveExtendTypeBuilderClass(string $type): string
    {
        $type = dp_normalize_extension_path($type);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:[.\/\\\\][A-Za-z][A-Za-z0-9_]*)*$/', $type)) {
            return '';
        }

        $namespacePath = str_replace(['.', '/', '\\'], '\\', $type);
        $relativePath  = str_replace(['.', '/', '\\'], DIRECTORY_SEPARATOR, $type);
        $class         = 'chart\\' . $namespacePath . '\\Type';
        $classFile     = dp_extend_chart_path() . $relativePath . DIRECTORY_SEPARATOR . 'Type.php';

        if (!is_file($classFile)) {
            return '';
        }

        if (!class_exists($class)) {
            return '';
        }

        if (!is_subclass_of($class, ChartTypeAbstract::class)) {
            throw new Exception("Class $class must extend " . ChartTypeAbstract::class);
        }

        return $class;
    }

    /**
     * 解析扩展地图 Provider 类
     * @param string $mapKey
     * @return string
     * @throws Exception
     */
    private function resolveExtendMapProviderClass(string $mapKey): string
    {
        $mapKey = dp_normalize_extension_path($mapKey);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:[.\/\\\\][A-Za-z][A-Za-z0-9_]*)*$/', $mapKey)) {
            return '';
        }

        $namespacePath = str_replace(['.', '/', '\\'], '\\', $mapKey);
        $relativePath  = str_replace(['.', '/', '\\'], DIRECTORY_SEPARATOR, $mapKey);
        $class         = 'chart_map\\' . $namespacePath . '\\Provider';
        $classFile     = dp_extend_chart_map_path() . $relativePath . DIRECTORY_SEPARATOR . 'Provider.php';

        if (!is_file($classFile)) {
            return '';
        }

        if (!class_exists($class, false)) {
            require_once $classFile;
        }

        if (!class_exists($class)) {
            return '';
        }

        if (!is_subclass_of($class, ChartMapProviderAbstract::class)) {
            throw new Exception("Class $class must extend " . ChartMapProviderAbstract::class);
        }

        return $class;
    }

    /**
     * 获取类型构建器实例
     * @param string $type
     * @return ChartTypeBuilderInterface|null
     * @throws Exception
     */
    private function resolveTypeBuilder(string $type): ?ChartTypeBuilderInterface
    {
        $builderClass = $this->resolveTypeBuilderClass($type);
        if ($builderClass === '') {
            return null;
        }

        if (isset($this->typeBuilderInstances[$builderClass])) {
            return $this->typeBuilderInstances[$builderClass];
        }

        $builder = Container::getInstance()->make($builderClass);
        if (!$builder instanceof ChartTypeBuilderInterface) {
            throw new Exception("图表类型构建器必须实现 " . ChartTypeBuilderInterface::class . "：$builderClass");
        }

        $this->typeBuilderInstances[$builderClass] = $builder;
        return $builder;
    }

    /**
     * 获取地图扩展 Provider 实例
     * @param string $mapKey
     * @return ChartMapProviderInterface|null
     * @throws Exception
     */
    private function resolveMapProvider(string $mapKey): ?ChartMapProviderInterface
    {
        $providerClass = $this->resolveMapProviderClass($mapKey);
        if ($providerClass === '') {
            return null;
        }

        if (isset($this->mapProviderInstances[$providerClass])) {
            return $this->mapProviderInstances[$providerClass];
        }

        $provider = Container::getInstance()->make($providerClass);
        if (!$provider instanceof ChartMapProviderInterface) {
            throw new Exception("图表地图扩展 Provider 必须实现 " . ChartMapProviderInterface::class . "：$providerClass");
        }

        $this->mapProviderInstances[$providerClass] = $provider;
        return $provider;
    }

    /**
     * 注册核心资源
     * @return void
     */
    private function registerCoreAssets(): void
    {
        $this->assetManager->addJs(dp_static_libs_path() . 'echarts/echarts.min.js', 20, [], 'echarts-lib');
        $this->assetManager->addJs(dp_static_render_path() . 'chart/chart.js', 30, ['echarts-lib'], 'chart-js');
        $this->assetManager->addCss(dp_static_render_path() . 'chart/chart.css', 30, [], 'chart-css');
    }

    /**
     * 注册类型扩展资源
     * @return void
     * @throws Exception
     */
    private function registerTypeAssets(): void
    {
        $builder = $this->resolveTypeBuilder($this->vars['dp_chart_type']);
        if ($builder === null) {
            return;
        }

        $assets = $builder->assets($this->buildTypeContext());
        $this->registerAssetBatch('css', $assets['css'] ?? []);
        $this->registerAssetBatch('js', $assets['js'] ?? []);

        foreach ($assets['extra_css'] ?? [] as $index => $content) {
            $this->assetManager->addInlineCss((string)$content, 'chart-type-extra-css-' . $this->vars['dp_chart_type'] . '-' . $index);
        }

        foreach ($assets['extra_js'] ?? [] as $index => $content) {
            $this->assetManager->addInlineJs((string)$content, 'chart-type-extra-js-' . $this->vars['dp_chart_type'] . '-' . $index);
        }

        foreach ($assets['init_js'] ?? [] as $index => $content) {
            $this->assetManager->addInitJs((string)$content, 'chart-type-' . $this->vars['dp_chart_type'] . '-' . $index);
        }
    }

    /**
     * 注册地图专项扩展资源
     * @return void
     * @throws Exception
     */
    private function registerMapAssets(): void
    {
        if (!$this->hasMap()) {
            return;
        }

        $provider = $this->resolveMapProvider((string)$this->vars['dp_chart_map_key']);
        if (!$provider instanceof ChartMapProviderAbstract) {
            return;
        }

        $assets = $provider->assets($this->buildMapContext());
        $prefix = 'chart_map-' . str_replace(['/', '\\', '.'], '-', (string)$this->vars['dp_chart_map_key']);

        $this->registerAssetBatch('css', (array)($assets['css'] ?? []), $prefix);
        $this->registerAssetBatch('js', (array)($assets['js'] ?? []), $prefix, ['chart-js']);

        foreach ((array)($assets['extra_css'] ?? []) as $index => $content) {
            $this->assetManager->addInlineCss((string)$content, $prefix . '-extra-css-' . $index);
        }

        foreach ((array)($assets['extra_js'] ?? []) as $index => $content) {
            $this->assetManager->addInlineJs((string)$content, $prefix . '-extra-js-' . $index);
        }

        foreach ((array)($assets['init_js'] ?? []) as $index => $content) {
            $this->assetManager->addInitJs((string)$content, $prefix . '-init-' . $index);
        }
    }

    /**
     * 注册资源列表
     * @param string $kind
     * @param array $items
     * @param string $prefix
     * @param array $defaultDependencies
     * @return void
     */
    private function registerAssetBatch(string $kind, array $items, string $prefix = '', array $defaultDependencies = []): void
    {
        $prefix = $prefix !== '' ? $prefix : 'chart-type-' . $this->vars['dp_chart_type'];

        foreach ($items as $index => $item) {
            if (is_string($item)) {
                if ($kind === 'css') {
                    $this->assetManager->addCss($item, 40, [], $prefix . '-css-' . $index);
                } else {
                    $dependencies = $defaultDependencies !== [] ? $defaultDependencies : ['chart-js'];
                    $this->assetManager->addJs($item, 40, $dependencies, $prefix . '-js-' . $index);
                }
                continue;
            }

            if (!is_array($item) || empty($item['file'])) {
                continue;
            }

            $priority     = (int)($item['priority'] ?? 40);
            $dependencies = (array)($item['dependencies'] ?? ($kind === 'js' ? ($defaultDependencies !== [] ? $defaultDependencies : ['chart-js']) : []));
            $id           = isset($item['id']) && is_string($item['id']) && $item['id'] !== ''
                ? $item['id']
                : $prefix . '-' . $kind . '-' . $index;

            if ($kind === 'css') {
                $this->assetManager->addCss((string)$item['file'], $priority, $dependencies, $id);
            } else {
                $this->assetManager->addJs((string)$item['file'], $priority, $dependencies, $id);
            }
        }
    }

    /**
     * 构建样式字符串
     * @return string
     */
    private function buildStyleString(): string
    {
        $style = $this->vars['dp_chart_style'];

        if (!is_array($style)) {
            $style = [$style];
        }

        $style['height']     = $this->vars['dp_chart_height'];
        $style['min-height'] = $this->vars['dp_chart_min_height'];

        $result = [];
        foreach ($style as $key => $value) {
            if (is_int($key)) {
                $chunk = trim((string)$value);
                if ($chunk !== '') {
                    $result[] = rtrim($chunk, ';');
                }
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $result[] = trim((string)$key) . ':' . trim((string)$value);
        }

        return implode(';', $result);
    }

    /**
     * 归一化尺寸值
     * @param string|int|float|null $size
     * @param string $default
     * @return string
     */
    private function normalizeSize(string|int|float|null $size, string $default): string
    {
        if ($size === null || $size === '') {
            return $default;
        }

        if (is_numeric($size)) {
            return rtrim((string)$size, '.0') . 'px';
        }

        return trim((string)$size);
    }
}
