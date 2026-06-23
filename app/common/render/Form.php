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
declare (strict_types=1);

namespace app\common\render;

use Closure;
use Exception;
use app\common\plugin\PluginRegistry;
use think\Container;
use think\facade\Config;
use think\route\Url as UrlBuild;
use app\common\interface\FormRender as FormRenderInterface;
use app\common\abstract\FormItem;
use app\common\abstract\FormType;
use app\common\render\form\Config as FormConfig;
use app\common\render\form\Cache as FormCache;
use app\common\service\UploadDriverManager;

/**
 * 表单渲染器
 * @package app\common\render
 */
class Form extends Common implements FormRenderInterface
{
    /**
     * 允许的联动运算符
     * @var array<int, string>
     */
    private const WHEN_OPERATORS = ['eq', 'neq', 'in', 'notin', 'empty', 'notempty', 'gt', 'egt', 'lt', 'elt'];

    /**
     * 允许的联动动作
     * @var array<int, string>
     */
    private const WHEN_ACTIONS = ['show', 'hide', 'enable', 'disable', 'require', 'unrequire', 'clear'];

    /**
     * 表单实例集合
     * @var array
     */
    protected static array $instances = [];

    /**
     * 模板变量
     * @var array
     */
    protected array $vars = [];

    /**
     * 表单数据
     * @var array|object
     */
    protected array|object $data = [];

    /**
     * 表单项
     * @var array
     */
    protected array $items = [];

    /**
     * 当前表单项
     * @var array
     */
    protected array $currItem = [];

    /**
     * 内置表单项类型
     * @var array
     */
    protected array $types = [];

    /**
     * 表单编译状态
     * @var array 键为表单ID，值为编译状态
     */
    protected static array $compiledForms = [];

    /**
     * 行索引
     * @var int
     */
    protected int $rowIndex = 0;

    /**
     * 表单项模板
     * @var array
     */
    protected array $itemTemplate = [];

    /**
     * 嵌套字段追加的联动规则
     * @var array
     */
    protected array $nestedWhenRules = [];

    /**
     * 表单项处理映射
     * @var array
     */
    protected array $handleMap = [];

    /**
     * 初始化
     */
    protected function initialize(): void
    {
        parent::initialize();

        // 加载表单配置
        $this->vars = FormConfig::getDefaults();

        // 设置动态配置
        $this->types                  = $this->normalizeTypeMap((array)Config::get('form.types', []));
        $this->itemTemplate           = $this->normalizeTypeMap((array)Config::get('form.item_template', []));
        $this->colClass               = Config::get('form.col_class', []);
        $this->vars['dp_form_action'] = $this->request->url(true);
    }

    /**
     * 创建表单实例 已经存在则直接获取
     * @param string $id 表单id
     * @param string $title 表单标题
     * @return $this
     * @throws Exception
     */
    public function init(string $id = '', string $title = ''): static
    {
        if ($id == '') {
            throw new Exception(lang('dp#undefined form id'));
        }

        // 安全检查
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new Exception(lang('dp#invalid form id format', ['id' => $id]));
        }

        // 如果实例已存在，直接返回
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }

        // 创建新实例
        $object                        = Container::getInstance()->make(static::class, [], true);
        $object->vars['dp_form_id']    = $id;
        $object->vars['dp_form_title'] = $title;
        self::$instances[$id]          = $object;
        return $object;
    }

    /**
     * 静态方式创建表单实例
     * @param string $id 表单id
     * @param string $title 表单标题
     * @return static
     * @throws Exception
     */
    public static function make(string $id = '', string $title = ''): static
    {
        $object = Container::getInstance()->make(static::class);
        return $object->init($id, $title);
    }

    /**
     * 检查指定ID的表单实例是否已存在
     * @param string $id 表单ID
     * @return bool
     */
    public static function exists(string $id): bool
    {
        return isset(self::$instances[$id]);
    }

    /**
     * 获取所有已创建的表单ID
     * @return array
     */
    public static function getUsedIds(): array
    {
        return array_keys(self::$instances);
    }

    /**
     * 清理表单实例缓存
     * @param string|null $id 指定清理的表单ID，为null时清理所有
     * @return void
     */
    public static function clearInstances(?string $id = null): void
    {
        if ($id === null) {
            self::$instances = [];
        } else {
            unset(self::$instances[$id]);
        }
    }

    /**
     * 设置表单布局模板
     * @param string $path 模板名称或路径
     * @return $this
     */
    public function template(string $path = ''): static
    {
        if ($path != '') {
            $this->template = $path;
        }
        return $this;
    }

    /**
     * 设置表单id
     * @param string $value 表单id名
     * @return $this
     */
    public function id(string $value = ''): static
    {
        $this->vars['dp_form_id'] = $value;
        return $this;
    }

    /**
     * 设置表单项处理器映射
     * @param string|array $type 表单项类型
     * @param string $class 表单项处理类
     * @param string $template 模板名称或路径
     * @return $this
     */
    public function itemHandler(string|array $type = '', string $class = '', string $template = ''): static
    {
        if (empty($type)) return $this;

        if (is_array($type)) {
            foreach ($type as $key => $item) {
                $params = is_array($item) ? $item : ['class' => $item];
                $this->itemHandler($key, $params['class'] ?? '', $params['template'] ?? '');
            }
        } else {
            $type = dp_normalize_extension_path($type);
            if (!empty($class)) {
                $this->handleMap[$type] = $class;
                empty($template) || $this->itemTemplate[$type] = $template;
            }
        }
        return $this;
    }

    /**
     * 设置表单数据
     * @param array|object $data 表单数据
     * @return $this
     */
    public function data(array|object $data = []): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置表单class
     * @param string $value class值,多个用空格隔开
     * @param bool $replace 是否替换原有值
     * @return $this
     */
    public function class(string $value = '', bool $replace = false): static
    {
        $this->vars['dp_form_class'] = $replace ? $value : 'dp-form ' . $value;
        return $this;
    }

    /**
     * 设置表单属性
     * @param string $value 多个属性,用空格隔开
     * @return $this
     */
    public function prop(string $value = ''): static
    {
        $this->vars['dp_form_prop'] = $value;
        return $this;
    }

    /**
     * 设置表单标题
     * @param string $value
     * @return $this
     */
    public function title(string $value = ''): static
    {
        $this->vars['dp_form_title'] = $value;
        return $this;
    }

    /**
     * 设置表单页提示信息
     * @param string|array $content 内容
     * @param string $title 标题
     * @param string $type 提示类型：success,info,danger,warning
     * @param string $pos 提示位置：top,bottom
     * @return $this
     */
    public function alert(string|array $content = '', string $title = '', string $type = 'info', string $pos = 'top'): static
    {
        if (!empty($content)) {
            if (is_array($content)) {
                $content = implode('<br>', $content);
            }

            $extra = [];
            if (str_contains($type, ':')) {
                list($type, $extra) = explode(':', $type);
                $extra = explode(',', $extra);
            }

            $pos = $pos == '' ? 'top' : $pos;

            $this->vars['dp_form_alert_' . $pos][] = [
                'content' => $content,
                'title'   => $title,
                'type'    => $type,
                'icon'    => in_array('icon', $extra),
                'close'   => in_array('close', $extra),
            ];
        }
        return $this;
    }

    /**
     * 设置表单头部
     * @param mixed $content 头部显示内容, false-隐藏整个头部
     * @return $this
     */
    public function header(mixed $content = true): static
    {
        $this->vars['dp_form_header'] = $content;
        return $this;
    }

    /**
     * 表单头部操作内容
     * @param mixed $content 显示内容
     * @param array $params 参数
     * @return $this
     */
    public function headerAction(mixed $content = '', array $params = []): static
    {
        if (!empty($content)) {
            if (is_array($content)) {
                foreach ($content as $item) {
                    $this->headerAction($item);
                }
            } else {
                $this->vars['dp_form_header_action'][] = match ($content) {
                    'btn', 'icon', 'close' => array_merge(['type' => $content], $params),
                    'dropdown' => [
                        'type'  => 'dropdown',
                        'items' => $params
                    ],
                    default => [
                        'type'    => 'html',
                        'content' => $content
                    ]
                };
            }
        }
        return $this;
    }

    /**
     * 设置表单底部
     * @param mixed $show 是否显示
     * @return $this
     */
    public function footer(mixed $show = true): static
    {
        $this->vars['dp_form_footer'] = $show;
        return $this;
    }

    /**
     * 设置表单底部操作区域
     * @param mixed $content 显示内容
     * @param bool $left 是否左侧显示
     * @return $this
     */
    public function footerAction(mixed $content = '', bool $left = false): static
    {
        if (!empty($content)) {
            if (is_array($content)) {
                foreach ($content as $item) {
                    $this->footerAction($item, $left);
                }
            } else {
                $pos                                    = true === $left ? 'left' : 'right';
                $this->vars['dp_form_action_' . $pos][] = [
                    'type'    => 'default',
                    'content' => $content,
                ];
            }
        }

        return $this;
    }

    /**
     * 提交按钮
     * @param mixed $title 标题
     * @param string $pos 位置
     * @param string $color 颜色
     * @param string $style 样式: pill,square
     * @return $this
     */
    public function btnSubmit(mixed $title = '', string $pos = 'right', string $color = 'primary', string $style = ''): static
    {
        $pos = $pos == 'left' ? 'left' : 'right';
        $this->parseButton('submit', $title, $pos, $color, $style);
        return $this;
    }

    /**
     * 返回按钮
     * @param mixed $title 标题
     * @param string $pos 位置
     * @param string $color 颜色
     * @param string $style 样式: pill,square
     * @return $this
     */
    public function btnBack(mixed $title = '', string $pos = 'left', string $color = 'ghost-info', string $style = ''): static
    {
        $pos = $pos == 'right' ? 'right' : 'left';
        $this->parseButton('back', $title, $pos, $color, $style);
        return $this;
    }

    /**
     * 设置表单提交地址
     * @param mixed $url 提交地址
     * @return $this
     */
    public function action(mixed $url = ''): static
    {
        if ($url instanceof UrlBuild) {
            $this->vars['dp_form_action'] = $url->build();
        } else {
            $this->vars['dp_form_action'] = $url;
        }
        return $this;
    }

    /**
     * 设置表单提交方式
     * @param string $method 方式，默认post
     * @param bool $ajax 是否ajax方式
     * @return $this
     */
    public function method(string $method = '', bool $ajax = true): static
    {
        if ($method != '') {
            $this->vars['dp_form_method'] = strtolower($method);
        }
        $this->vars['dp_form_ajax'] = $ajax;
        return $this;
    }

    /**
     * 设置表单提交确认
     * @param string|array $title 标题
     * @param string $text 内容
     * @param string $type 类型
     * @param string $confirmText 确认按钮文字
     * @param string $cancelText 取消按钮文字
     * @return $this
     */
    public function confirm(
        string|array $title = '确认操作',
        string       $text = '确认要执行此操作吗？',
        string       $type = 'warning',
        string       $confirmText = '确认',
        string       $cancelText = '取消',
    ): static
    {
        $this->vars['dp_form_confirm'] = is_array($title) ? $title : [
            'title'       => $title,
            'text'        => $text,
            'type'        => $type,
            'confirmText' => $confirmText,
            'cancelText'  => $cancelText
        ];
        return $this;
    }

    /**
     * 设置表单是否使用ajax方式
     * @param bool $ajax
     * @return $this
     */
    public function ajax(bool $ajax = true): static
    {
        $this->vars['dp_form_ajax'] = $ajax;
        return $this;
    }

    /**
     * 模板变量赋值
     * @param string $name 模板变量
     * @param mixed|null $value 变量值
     * @return $this
     */
    public function assign(string $name, mixed $value = null): static
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 批量添加表单项
     * @param array $items
     * @return $this
     * @throws Exception
     */
    public function items(array $items = []): static
    {
        foreach ($items as $item) {
            if (is_array($item) && array_is_list($item)) {
                $this->item(...$item);
            } else {
                $this->item($item);
            }
        }
        return $this;
    }

    /**
     * 添加单个表单项
     * @param mixed $type 表单项类型
     * @param string $name 表单项名称
     * @param string $label 表单项标题
     * @param string $tips 表单项提示
     * @param mixed $value 表单项默认值
     * @param mixed $options 额外选项
     * @return $this|string
     * @throws Exception
     */
    public function item(mixed $type, mixed $name = '', string $label = '', string $tips = '', mixed $value = '', mixed $options = []): string|static
    {
        if (empty($type)) {
            return $this;
        }

        $this->currItem = [
            'type'    => $type,
            'name'    => $name,
            'label'   => $label,
            'tips'    => $tips,
            'value'   => $value,
            'options' => $options,
        ];

        if ($type instanceof FormType) {
            $this->currItem = array_merge($this->currItem, $type->toArray());
        } elseif (is_array($type)) {
            $this->currItem = $type;
        } elseif ($type instanceof Closure) {
            $this->currItem = $type($this);
        }

        if (!isset($this->currItem['type'])) {
            throw new Exception(lang('dp#undefined type attribute'));
        }

        if (str_contains($this->currItem['type'], ':')) {
            list($type, $options) = explode(':', $this->currItem['type']);
            $this->currItem['type'] = $type;

            $options = explode('|', $options);
            if (in_array('*', $options)) {
                $this->currItem['required'] = true;
            }
        }

        $this->currItem['type'] = dp_normalize_extension_path((string)$this->currItem['type']);

        if (!isset($this->currItem['name']) || empty($this->currItem['name'])) {
            if ($this->shouldAutoGenerateItemName($this->currItem)) {
                $this->currItem['name'] = $this->generateAutoItemName($this->currItem['type']);
            } else {
                throw new Exception(lang('dp#undefined name attribute'));
            }
        }

        // 直接返回表单项html代码
        if (true === $name) {
            $item = $this->parseItem($this->currItem);
            if (!empty($item['when_rules'])) {
                $this->nestedWhenRules = array_merge($this->nestedWhenRules, $item['when_rules']);
            }
            return $item['html'] ?? '';
        }

        $this->items[$this->currItem['name']] = $this->currItem;
        return $this;
    }

    /**
     * 是否允许为展示型组件自动补全 name
     * @param array $item
     * @return bool
     */
    private function shouldAutoGenerateItemName(array $item): bool
    {
        $type = (string)($item['type'] ?? '');

        if ($type === 'html') {
            return true;
        }

        if ($type === 'static') {
            return empty($item['send']);
        }

        return false;
    }

    /**
     * 为展示型组件生成内部 name，避免覆盖真实业务字段
     * @param string $type
     * @return string
     */
    private function generateAutoItemName(string $type): string
    {
        return '__' . $type . '_' . (count($this->items) + 1) . '__';
    }

    /**
     * 设置表单项模板
     * @param string|array $type 表单项类型
     * @param string $template 模板名称或路径
     * @return $this
     */
    public function itemTemplate(string|array $type = '', string $template = ''): static
    {
        if (!empty($type)) {
            if (is_array($type)) {
                $this->itemTemplate = array_merge($this->itemTemplate, $this->normalizeTypeMap($type));
            } else {
                $type                      = dp_normalize_extension_path($type);
                $this->itemTemplate[$type] = $template;
            }
        }
        return $this;
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
        // 添加默认加载的静态资源到AssetManager
        $this->assets();
        // 编译表单
        $this->compile();

        return $this->finalizeFetch($template, $vars);
    }

    /**
     * 设置静态资源，css或js文件
     * @return array
     */
    public function assets(): array
    {
        // 添加默认加载的静态资源到AssetManager
        $this->assetManager->addJs(dp_static_render_path() . 'form/form.js', 10);
        $this->assetManager->addJs(dp_static_render_path() . 'form/components.js', 10);
        $this->assetManager->addCss(dp_static_render_path() . 'form/form.css', 90);

        // 获取AssetManager管理的资源
        return $this->assetManager->getAssets();
    }

    /**
     * 设置页面额外html代码
     * @param string $content
     * @param string $pos
     * @return $this
     */
    public function extraHtml(string $content, string $pos = 'top'): static
    {
        if (empty($content)) {
            return $this;
        }

        $this->vars['dp_form_extra_html'][$pos][] = $content;
        return $this;
    }

    /**
     * 设置页面额外html模板
     * @param string $template 模板文件
     * @param string $pos 位置
     * @param array $vars 模板变量
     * @return $this
     * @throws Exception
     */
    public function extraHtmlFile(string $template = '', string $pos = 'top', array $vars = []): static
    {
        if ($template != '') {
            if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
                $template = app_path() . 'view' . DIRECTORY_SEPARATOR . $this->request->controller(true) . DIRECTORY_SEPARATOR . (trim($template, '/')) . '.html';
            }
            if (!is_file($template)) {
                $content = '模板文件不存在：' . $template;
            } else {
                $content = FormCache::getTemplate($template);
                $content = $this->view->display($content, $vars);
            }

            $pos = $pos == '' ? 'top' : $pos;

            $this->vars['dp_form_extra_html'][$pos][] = $content;
        }

        return $this;
    }

    /**
     * 设置表单吸附
     * @param mixed $pos 位置: top,bottom
     * @param bool|int $num 距离
     * @return $this
     */
    public function sticky(mixed $pos = '', bool|int $num = 0): static
    {
        if ($pos === false) {
            $this->vars['dp_form_sticky']['top']    = false;
            $this->vars['dp_form_sticky']['bottom'] = false;
        } else {
            $this->vars['dp_form_sticky'][$pos] = $num;
        }
        return $this;
    }

    /**
     * 获取表单项html代码
     * @return mixed|string|null
     * @throws Exception
     */
    public function getHtml(): mixed
    {
        if (!empty($this->currItem)) {
            $content = $this->parseItem($this->currItem, 'html');
            unset($this->items[$this->currItem['name']]);
            return $content;
        }
        return '';
    }

    /**
     * 分析上传驱动（向后兼容方法）
     * @param array $item
     * @return array
     * @throws Exception
     */
    public function parseUploadDriver(array $item = []): array
    {
        // 调用新的管理器，保持接口兼容
        try {
            $item = UploadDriverManager::processItem($item);

            // 获取驱动名称和实例
            $driverName = $item['driver'] ?? dp_setting('upload.default_driver', config('upload.default', 'local'));
            $driver     = UploadDriverManager::driver($driverName, $item);

            // 使用AssetManager加载资源（保持原有行为）
            $driverAssetId = 'upload_driver_' . $driverName;
            if (!$this->assetManager->isLoaded($driverAssetId)) {
                // 加载JS文件到AssetManager
                if (!empty($driver->js())) {
                    $this->assetManager->addJs($driver->js(), 25);
                }
                // 加载CSS文件到AssetManager
                if (!empty($driver->css())) {
                    $this->assetManager->addCss($driver->css(), 25);
                }
                // 标记驱动资源为已加载
                $this->assetManager->markAsLoaded($driverAssetId);
            }

            // 加载驱动配置
            $this->vars['dp_form_upload_config'][$driverName] = $driver->config();

            return $item;

        } catch (Exception) {
            // 如果新系统失败，回退到旧系统
            return $this->parseUploadDriverLegacy($item);
        }
    }

    /**
     * 旧版上传驱动解析（向后兼容）
     * @param array $item
     * @return array
     * @throws Exception
     */
    private function parseUploadDriverLegacy(array $item = []): array
    {
        $item['driver'] = $item['driver'] ?? dp_setting('upload.default_driver', Config::get('upload.default', 'local'));

        // 安全检查：防止类名注入
        $allowedDrivers = Config::get('upload.allowed_drivers', []);
        if (!empty($allowedDrivers) && !in_array($item['driver'], $allowedDrivers)) {
            throw new Exception(lang('dp#invalid upload driver', ['class' => $item['driver']]));
        }

        // 安全的类名构造
        $class = 'upload\\' . $item['driver'] . '\\Driver';

        $driver = FormCache::getUploadDriverInstance($class);

        $item = $driver->handle($item);

        // 使用AssetManager加载资源
        $driverAssetId = 'upload_driver_' . $item['driver'];
        if (!$this->assetManager->isLoaded($driverAssetId)) {
            // 加载JS文件到AssetManager
            if (!empty($driver->js())) {
                $this->assetManager->addJs($driver->js(), 25);
            }
            // 加载CSS文件到AssetManager
            if (!empty($driver->css())) {
                $this->assetManager->addCss($driver->css(), 25);
            }
            // 标记驱动资源为已加载
            $this->assetManager->markAsLoaded($driverAssetId);
        }

        // 加载驱动配置
        $this->vars['dp_form_upload_config'][$item['driver']] = $driver->config();
        return $item;
    }

    /**
     * 编译
     * @throws Exception
     */
    private function compile(): void
    {
        $formId = $this->vars['dp_form_id'] ?? 'dp_form';

        if (!isset(self::$compiledForms[$formId])) {
            // 获取表单布局文件
            $this->getLayoutTemplate('form');
            // 处理底部按钮
            $this->parseBottomButton();
            // 处理吸附
            $this->parseSticky();
            // 处理表单项
            $this->parseItems();
            // 处理表单提交
            $this->parseAjax();

            self::$compiledForms[$formId] = true;
        }
    }

    /**
     * 处理表单提交
     */
    private function parseAjax(): void
    {
        if ($this->vars['dp_form_ajax']) {
            $this->vars['dp_form_submit_type'] = $this->vars['dp_form_method'] == 'post' ? 'dp-ajax-post' : 'dp-ajax-get';
        }

        $this->vars['dp_form_confirm'] = dp_parse_options($this->vars['dp_form_confirm']);
    }

    /**
     * 解析表单项
     * @param array $item
     * @param string $field
     * @return mixed|string|void
     * @throws Exception
     */
    private function parseItem(array $item, string $field = '')
    {
        // 如果设置了处理映射,则交给新映射的类处理
//        if (isset($this->handleMap[$item['type']])) {
//            $item = $this->parseExtendItem($this->handleMap[$item['type']], $item);
//        }
//        elseif (!isset($this->types[$item['type']])) {
//            // 自定义表单项
//            $item = $this->parseExtendItem($item);
//        }

        // 设置表单项值
        $item['type'] = dp_normalize_extension_path((string)($item['type'] ?? ''));
        if (isset($item['name']) && isset($this->data[$item['name']])) {
            $item['value'] = $this->data[$item['name']];
        } else {
            $item['value'] = $item['value'] ?? '';
        }

        // 表单项处理类
        $class = $this->handleMap[$item['type']] ?? ($this->types[$item['type']] ?? '');

        if ($class === '') {
            $class = app(PluginRegistry::class)->getFormItemClass($item['type']);
        }

        // 自定义扩展项
        if ($class == '') {
            $class = 'form\\' . str_replace('/', '\\', $item['type']) . '\\Item';
            $item  = $this->parseExtendItem($class, $item);
        }

        // 处理表单项
        $object = FormCache::getItemInstance($class);

        // 加载表单项静态资源
        $this->loadAssets($object, $class);

        $item = $object->handle($item, $this);

        // 统一补齐字段ID，供联动规则定位目标容器
        $item['id'] = $item['id'] ?? $item['name'];

        // 归一化字段联动规则（支持单条与多条）
        $item['when_rules'] = $this->normalizeWhenRules($item['when'] ?? null, $item['name'], $item['id']);

        $item['html'] = $this->parseItemHtml($item);
        return $item[$field] ?? $item;
    }

    /**
     * 解析扩展项
     * @param string $class
     * @param array $item
     * @return array
     * @throws Exception
     */
    private function parseExtendItem(string $class, array $item): array
    {
        if ($class == '') {
            return $item;
        }

        $reflection = FormCache::getItemReflection($class);

        // 扩展项所在目录
        $dir = dirname($reflection->getFileName()) . DIRECTORY_SEPARATOR;

        // 自定义表单项
        if (!isset($item['template']) || $item['template'] == '') {
            // 表单项主题模板路径
            $item['template'] = $dir . 'item.html';
        }

        return $item;
    }

    /**
     * 归一化类型映射表
     * @param array<string, mixed> $map
     * @return array<string, mixed>
     */
    private function normalizeTypeMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $normalized[dp_normalize_extension_path($key)] = $value;
        }

        return $normalized;
    }

    /**
     * 处理表单项
     * @throws Exception
     */
    private function parseItems(): void
    {
        if (!empty($this->items)) {
            $this->nestedWhenRules = [];
            $cols                  = [];
            $whenRules             = [];
            foreach ($this->items as $item) {
                $item                                       = $this->parseItem($item);
                $cols[$item['name']]                        = [
                    'content' => $item['html'],
                    'class'   => $this->parseClass($item['width'] ?? '', $this->colClass . ' col-12'),
                    'attr'    => ''
                ];
                $this->vars['dp_form_items'][$item['name']] = $item;

                if (!empty($item['when_rules'])) {
                    $whenRules = array_merge($whenRules, $item['when_rules']);
                }
            }

            if (!empty($this->nestedWhenRules)) {
                $whenRules = array_merge($whenRules, $this->nestedWhenRules);
            }

            $this->vars['dp_form_rows'][] = [
                'cols'  => $cols,
                'attr'  => dp_arr2str(['id' => $this->vars['dp_form_id'] . '_row_' . $this->rowIndex]),
                'class' => ''
            ];
            $this->rowIndex++;

            if (!empty($whenRules)) {
                $json                             = json_encode($whenRules);
                $this->vars['dp_form_when_rules'] = is_string($json) ? base64_encode($json) : '';
            }
        }
    }

    /**
     * 归一化字段联动规则列表
     * @param mixed $when
     * @param string $targetField
     * @param string $targetId
     * @return array
     */
    private function normalizeWhenRules(mixed $when, string $targetField, string $targetId): array
    {
        if (empty($when) || !is_array($when)) {
            return [];
        }

        // 列表规则：[['field'=>...], ['conditions'=>...]]
        if (array_is_list($when)) {
            $rules = [];
            foreach ($when as $rule) {
                $normalized = $this->normalizeWhenRule($rule, $targetField, $targetId);
                if (!empty($normalized)) {
                    $rules[] = $normalized;
                }
            }
            return $rules;
        }

        $normalized = $this->normalizeWhenRule($when, $targetField, $targetId);
        return empty($normalized) ? [] : [$normalized];
    }

    /**
     * 归一化字段联动规则
     * @param mixed $when
     * @param string $targetField
     * @param string $targetId
     * @return array|null
     */
    private function normalizeWhenRule(mixed $when, string $targetField, string $targetId): ?array
    {
        if (empty($when) || !is_array($when)) {
            return null;
        }

        $logic = strtolower((string)($when['logic'] ?? 'and'));
        $logic = in_array($logic, ['and', 'or'], true) ? $logic : 'and';

        $if = $when['if'] ?? null;
        if ($if === null) {
            $conditions = [$when];
        } elseif (is_array($if) && array_is_list($if)) {
            $conditions = $if;
        } elseif (is_array($if)) {
            $conditions = [$if];
        } else {
            return null;
        }

        $normalizedConditions = [];
        foreach ($conditions as $condition) {
            $normalized = $this->normalizeWhenCondition($condition);
            if (!empty($normalized)) {
                $normalizedConditions[] = $normalized;
            }
        }

        if (empty($normalizedConditions)) {
            return null;
        }

        return [
            'target'      => $targetField,
            'targetId'    => $targetId,
            'logic'       => $logic,
            'conditions'  => $normalizedConditions,
            'actions'     => $this->normalizeWhenActions($when['then'] ?? ['show']),
            'elseActions' => $this->normalizeWhenActions($when['else'] ?? []),
        ];
    }

    /**
     * 归一化单条联动条件
     * @param mixed $condition
     * @return array|null
     */
    private function normalizeWhenCondition(mixed $condition): ?array
    {
        if (!is_array($condition)) {
            return null;
        }

        $field = trim((string)($condition['field'] ?? ''));
        if ($field === '') {
            return null;
        }

        $operator = strtolower(trim((string)($condition['op'] ?? 'eq')));
        if (!in_array($operator, self::WHEN_OPERATORS, true)) {
            return null;
        }

        return [
            'field'    => $field,
            'operator' => $operator,
            'value'    => $condition['value'] ?? null,
        ];
    }

    /**
     * 归一化联动动作列表
     * @param mixed $actions
     * @return array
     */
    private function normalizeWhenActions(mixed $actions): array
    {
        $actions = is_array($actions) ? $actions : [$actions];
        $actions = array_map(static fn($action) => strtolower((string)$action), $actions);
        $actions = array_filter($actions, static fn($action) => in_array($action, self::WHEN_ACTIONS, true));
        return array_values(array_unique($actions));
    }

    /**
     * 解析表单项模板
     * @param array $item 表单项数据
     * @return string
     * @throws Exception
     */
    private function parseItemHtml(array $item = []): string
    {
        $html = '';

        if (!empty($item)) {
            // 视图实例
            $view = app('view', [], true);

            // 占位符提示
            $item['placeholder'] = $item['placeholder'] ?? null;

            // id
            $item['id'] = $item['id'] ?? $item['name'];

            // 属性
            if (is_array($item['props'] ?? null)) {
                $item['props'] = dp_arr2str($item['props']);
            }

            // 尺寸
            if (!empty($item['size'])) {
                $item['size'] = Config::get('form.input_size_prefix', '') . $item['size'];
            }

            // 类名
            if (!empty($item['class'])) {
                $item['class'] = is_array($item['class']) ? implode(' ', $item['class']) : $item['class'];
            }

            // 内部宽度
            if (isset($item['inner_width'])) {
                $item['inner_width'] = $this->parseClass($item['inner_width']);
            }

            // 表单id
            $item['_form_id'] = $this->vars['dp_form_id'];

            // 解析模板
            if (isset($item['html'])) {
                if ($item['html'] instanceof Closure) {
                    $item['html'] = $item['html']($this);
                } elseif ('object' === gettype($item['html'])) {
                    if (!method_exists($item['html'], 'handle')) {
                        throw new Exception(lang('dp#undefined handle method', ['class' => $item['html']]));
                    }
                    $item['html'] = $item['html']->handle($this);
                }
                $html = $view->display($item['html'], $item);
            } else {
                if (isset($item['template'])) {
                    $template = $item['template'];
                } elseif (isset($this->itemTemplate[$item['type']])) {
                    $template = $this->itemTemplate[$item['type']];
                } else {
                    $template = Config::get('form.item_template.' . $item['type'], 'item');
                }

                if ($template == '') {
                    throw new Exception(lang('dp#template not set', ['type' => $item['type']]));
                }

                if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
                    // 获取模板文件名
                    $template = dp_render_path() . 'form/items' . DIRECTORY_SEPARATOR . $item['type'] . DIRECTORY_SEPARATOR . $template . '.html';
                }

                $templateContent = FormCache::getTemplate($template);
                $html            = $view->display($templateContent, $item);
            }
        }

        return $html;
    }

    /**
     * 处理底部按钮
     */
    private function parseBottomButton(): void
    {
        foreach (['submit', 'back'] as $type) {
            if (!empty($this->vars['dp_form_button_' . $type])) {
                $this->vars['dp_form_action_' . $this->vars['dp_form_button_' . $type]['pos']][] = [
                    'type'    => $type,
                    'content' => $this->vars['dp_form_button_' . $type],
                ];
            }
        }
    }

    /**
     * 解析按钮
     * @param string $name 按钮名
     * @param mixed $title 标题
     * @param string $pos 位置
     * @param string $color 颜色
     * @param string $style 样式
     */
    private function parseButton(string $name = 'submit', mixed $title = '', string $pos = '', string $color = '', string $style = ''): void
    {
        if (false === $title) {
            $this->vars['dp_form_button_' . $name] = [];
        } else {
            if (!empty($title)) {
                $this->vars['dp_form_button_' . $name]['text'] = $title;
            }
            if (!empty($color)) {
                $this->vars['dp_form_button_' . $name]['color'] = $color;
            }
            if (!empty($style)) {
                $this->vars['dp_form_button_' . $name]['style'] = 'btn-' . $style;
            }

            $this->vars['dp_form_button_' . $name]['pos'] = $pos;
        }
    }

    /**
     * 处理吸附
     * @return void
     */
    private function parseSticky(): void
    {
        foreach ($this->vars['dp_form_sticky'] as $pos => $num) {
            $this->vars['dp_form_sticky'][$pos] = false === $num ? -1 : (true === $num ? 0 : $num);
        }
    }

    /**
     * 加载表单项静态资源
     * @param FormItem $item
     * @param string $class
     */
    private function loadAssets(FormItem $item, string $class = ''): void
    {
        // 使用AssetManager检查是否已加载
        $assetId = 'form_item_' . md5($class);
        if ($this->assetManager->isLoaded($assetId)) {
            return;
        }

        // 标记为已加载
        $this->assetManager->markAsLoaded($assetId);
        // 获取表单项静态资源
        $assets = $item->getAssets();

        // 使用AssetManager添加CSS资源
        if (!empty($assets['css'])) {
            $this->assetManager->addCss($assets['css'], 30);
        }

        // 使用AssetManager添加JS资源
        if (!empty($assets['js'])) {
            $this->assetManager->addJs($assets['js'], 30);
        }

        // 添加初始化JS到AssetManager
        if (!empty($assets['init'])) {
            foreach ($assets['init'] as $initJs) {
                $this->assetManager->addInitJs($initJs, 'DpForm');
            }
        }
    }

}
