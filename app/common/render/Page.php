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
use think\Container;
use think\route\Url as UrlBuild;
use app\common\interface\ZRender;
use app\common\interface\PageRender as PageRenderInterface;

/**
 * 页面渲染类
 * @package app\common\render
 */
class Page extends Common implements PageRenderInterface
{
    /**
     * 页面实例集合
     * @var array
     */
    protected static array $instances = [];

    /**
     * 内置模板变量
     * @var array
     */
    protected array $vars = [
        // 页面标题
        'dp_page_title'     => '',
        // 页面副标题
        'dp_page_pre_title' => '',
        // 页面header
        'dp_page_header'    => '',
        // 行数据
        'dp_page_rows'      => [],
        // css文件
        'dp_file_css'       => [],
        // js文件
        'dp_file_js'        => [],
        // 额外css代码
        'dp_extra_css'      => [],
        // 额外js代码
        'dp_extra_js'       => [],
        // js初始化
        'dp_init_js'        => [],
        // 换行
        'dp_newline'        => '',
        // 按钮组
        'dp_page_action'    => [
            // 顶部右侧按钮
            'top-right' => [],
        ]
    ];

    /**
     * 行索引
     * @var int
     */
    protected int $rowIndex = 0;

    /**
     * 初始化
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->vars['dp_newline'] = config('page.newline');
        $this->colClass           = config('page.col_class');
        $this->template           = dp_page_layout();
    }

    /**
     * 创建页面实例 已经存在则直接获取
     * @param string $id 页面标识
     * @return static
     * @throws Exception
     */
    public function init(string $id = ''): static
    {
        // 允许空ID，创建临时实例
        if ($id === '') {
            return $this;
        }

        // 安全检查：防止ID注入
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new Exception(lang('dp#invalid page id format', ['id' => $id]));
        }

        // 如果实例已存在，直接返回（保持原有的单例逻辑）
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }

        // 创建新实例
        $object               = Container::getInstance()->make(static::class, [], true);
        self::$instances[$id] = $object;

        return $object;
    }

    /**
     * 静态方式创建页面实例
     * @param string $id 页面标识
     * @return static
     * @throws Exception
     */
    public static function make(string $id = ''): static
    {
        $object = Container::getInstance()->make(static::class);
        return $object->init($id);
    }

    /**
     * 检查指定ID的页面是否已存在
     * @param string $id 页面ID
     * @return bool
     */
    public static function exists(string $id): bool
    {
        return isset(self::$instances[$id]);
    }

    /**
     * 获取所有已创建的页面ID
     * @return array
     */
    public static function getUsedIds(): array
    {
        return array_keys(self::$instances);
    }

    /**
     * 清理页面实例缓存
     * @param string|null $id 指定清理的页面ID，为null时清理所有
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
     * 解析和获取模板内容 用于输出
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板变量
     * @return string
     * @throws Exception
     */
    public function fetch(string $template = '', array $vars = []): string
    {
        // 添加默认加载的静态资源到 AssetManager
        $this->assetManager->addJs(dp_static_render_path() . 'page/page.js');

        // 使用AssetManager统一管理资源
        $assetData = $this->assetManager->getAssets();
        $this->setAssetsToVars($assetData);

        $vars = array_merge($this->vars, $vars);
        if ($template != '') {
            $this->template = $template;
        }

        return $this->view->fetch($this->template, $vars);
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed|null $value 变量值
     * @return $this
     */
    public function assign(string|array $name, mixed $value = null): static
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 视图过滤
     * @param callable|null $filter 过滤方法或闭包
     * @return $this
     */
    public function filter(callable $filter = null): static
    {
        $this->view->filter($filter);
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
     * 设置页面标题
     * @param string $value 页面标题
     * @param string $default 默认标题，即页面标题为空时显示
     * @return $this
     */
    public function title(string $value = '', string $default = ''): static
    {
        $this->vars['dp_page_title'] = $value == '' ? $default : $value;
        if ($this->vars['dp_page_title'] != '') {
            $this->vars['dp_page_header'] = 'show';
        }
        return $this;
    }

    /**
     * 设置页面副标题
     * @param string $value
     * @return $this
     */
    public function preTitle(string $value = ''): static
    {
        $this->vars['dp_page_pre_title'] = $value;
        if ($this->vars['dp_page_pre_title'] != '') {
            $this->vars['dp_page_header'] = 'show';
        }
        return $this;
    }

    /**
     * 设置页面操作按钮
     * @param string $name
     * @param array $attrs
     * @param string $pos
     * @return $this
     */
    public function action(string $name = '', array $attrs = [], string $pos = 'top-right'): static
    {
        $button = [
            'title'   => '',
            'name'    => $name,
            'class'   => 'btn',
            'href'    => 'javascript:void(0)',
            'url'     => '',
            'target'  => '',
            'icon'    => '',
            'svg'     => '',
            'id'      => '',
            'ajax'    => false,
            'confirm' => false,
            'pop'     => false,
            'props'   => [],
        ];
        if (!empty($attrs)) {
            $button = array_merge($button, $attrs);
        }

        $props    = is_array($button['props']) ? $button['props'] : [];
        $rawProps = is_array($button['props']) ? '' : trim((string)$button['props']);

        if ($button['url'] !== '') {
            $button['href'] = $this->parseActionUrl($button['url']);
        }

        if ($button['target'] !== '') {
            $props['target'] = $button['target'];
        }

        $actionConfig = [];
        if ($button['ajax'] !== false || $button['pop'] !== false || $button['confirm'] !== false) {
            $actionConfig = [
                'title'  => $button['title'] !== '' ? $button['title'] : '操作',
                'url'    => $button['href'],
                'target' => $button['target'] !== '' ? $button['target'] : '_self',
            ];
        }

        if ($button['ajax'] !== false) {
            $actionConfig['ajax'] = $this->normalizeActionAjax($button['ajax']);
        } elseif ($button['pop'] !== false) {
            $actionConfig['pop'] = $this->normalizeActionPop($button['pop'], $button['title']);
        }

        if ($button['confirm'] !== false) {
            $actionConfig['confirm'] = $this->normalizeActionConfirm($button['confirm']);
        }

        if ($actionConfig !== []) {
            $button['class']      = $this->appendActionClass($button['class']);
            $props['data-config'] = $actionConfig;
        }

        // class图标
        $button['icon'] = !empty($button['icon']) ? '<i class="dp-icon ' . $button['icon'] . '"></i> ' : '';

        // svg图标
        if ($button['svg'] != '') {
            $button['icon'] = $button['svg'];
        }

        $button['props'] = trim(($props !== [] ? dp_arr2str($props) : '') . ' ' . $rawProps);

        if ($button['title'] == '') {
            $button['class'] .= ' dp-btn-no-title';
        }

        $this->vars['dp_page_action'][$pos][] = $button;
        $this->vars['dp_page_header']         = 'show';
        return $this;
    }

    /**
     * 追加按钮 class，避免重复
     * @param string $class
     * @return string
     */
    private function appendActionClass(string $class): string
    {
        $class   = trim($class);
        $classes = $class === '' ? [] : (preg_split('/\s+/', $class) ?: []);
        if (!in_array('dp-page-action', $classes, true)) {
            $classes[] = 'dp-page-action';
        }

        return trim(implode(' ', array_filter($classes)));
    }

    /**
     * 规范化页面按钮 AJAX 配置
     * @param mixed $ajax
     * @return array<string, mixed>
     */
    private function normalizeActionAjax(mixed $ajax): array
    {
        $config = is_array($ajax) ? $ajax : ['type' => $ajax];
        $result = [
            'type' => strtolower((string)($config['type'] ?? $config['method'] ?? 'post')) === 'get' ? 'get' : 'post',
        ];

        if (!empty($config['form'])) {
            $result['form'] = (string)$config['form'];
        }
        if (!empty($config['no_refresh'])) {
            $result['no_refresh'] = true;
        }
        if (!empty($config['no_forward'])) {
            $result['no_forward'] = true;
        }

        return $result;
    }

    /**
     * 规范化页面按钮弹窗配置
     * @param mixed $pop
     * @param string $title
     * @return array<string, mixed>
     */
    private function normalizeActionPop(mixed $pop, string $title = ''): array
    {
        return is_array($pop)
            ? array_merge(['title' => $title !== '' ? $title : '操作'], $pop)
            : ['title' => $title !== '' ? $title : '操作'];
    }

    /**
     * 规范化页面按钮确认配置
     * @param mixed $confirm
     * @return array<string, mixed>
     */
    private function normalizeActionConfirm(mixed $confirm): array
    {
        $config  = is_array($confirm) ? $confirm : ['text' => (string)$confirm];
        $title   = trim((string)($config['title'] ?? '确认操作'));
        $text    = trim((string)($config['text'] ?? '确认要执行此操作吗？'));
        $options = [];

        if (!empty($config['type'])) {
            $options['icon'] = (string)$config['type'];
        }
        if (!empty($config['confirmText'])) {
            $options['confirmButtonText'] = (string)$config['confirmText'];
        }
        if (!empty($config['cancelText'])) {
            $options['cancelButtonText'] = (string)$config['cancelText'];
        }
        if (array_key_exists('iconColor', $config)) {
            $options['iconColor'] = $config['iconColor'];
        }
        if (array_key_exists('confirmButtonColor', $config)) {
            $options['confirmButtonColor'] = $config['confirmButtonColor'];
        }
        if (array_key_exists('showCancelButton', $config)) {
            $options['showCancelButton'] = (bool)$config['showCancelButton'];
        }
        if (array_key_exists('allowOutsideClick', $config)) {
            $options['allowOutsideClick'] = (bool)$config['allowOutsideClick'];
        }
        if (array_key_exists('allowEscapeKey', $config)) {
            $options['allowEscapeKey'] = (bool)$config['allowEscapeKey'];
        }
        if (array_key_exists('reverseButtons', $config)) {
            $options['reverseButtons'] = (bool)$config['reverseButtons'];
        }

        return [
            'title'   => $title !== '' ? $title : '确认操作',
            'text'    => $text,
            'options' => $options,
        ];
    }

    /**
     * 解析页面按钮 URL
     * @param mixed $url
     * @return string
     */
    private function parseActionUrl(mixed $url): string
    {
        if ($url instanceof UrlBuild) {
            $url = $url->build();
        }

        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with(strtolower($url), 'javascript:')) {
            return '';
        }

        if (!str_starts_with($url, '/') &&
            !str_starts_with($url, 'http:') &&
            !str_starts_with($url, 'https:')) {
            return (string)dp_url($url);
        }

        return $url;
    }

    /**
     * 添加单行内容
     * @param mixed $content
     * @param array $attr
     * @return $this
     * @throws Exception
     */
    public function row(mixed $content = [], array $attr = []): static
    {
        if (empty($content)) {
            return $this;
        }

        $content = $this->resolveContent($content);

        $content = (array)$content;
        foreach ($content as $key => $col) {
            $content[$key] = $this->parseCol($col);
        }

        // 设置行默认id
        if (!isset($attr['id']) || $attr['id'] == '') {
            $attr['id'] = 'dp_page_row_' . $this->rowIndex;
            $this->rowIndex++;
        }

        $this->vars['dp_page_rows'][] = [
            'cols'  => $content,
            'attr'  => dp_arr2str($attr),
            'class' => $attr['class'] ?? ''
        ];

        return $this;
    }

    /**
     * 批量添加行
     * @param array $rows 行数据数组
     * @param array $attr 默认行属性
     * @return $this
     * @throws Exception
     */
    public function rows(array $rows, array $attr = []): static
    {
        foreach ($rows as $row) {
            $this->row($row, $attr);
        }
        return $this;
    }

    /**
     * 统一网格布局方法 - 支持固定列数和响应式布局
     * @param array $items 项目数组
     * @param int|array $cols 列数配置
     *   - int: 固定列数 (1-12)
     *   - array: 响应式断点配置 ['sm' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4]
     * @param array $attr 行属性
     * @return $this
     * @throws Exception
     */
    public function grid(array $items, int|array $cols = 3, array $attr = []): static
    {
        // 处理固定列数
        if (is_int($cols)) {
            if ($cols < 1 || $cols > 12) {
                throw new Exception(lang('dp#grid columns must be between 1 and 12'));
            }

            $attr['class'] = ($attr['class'] ?? '') . " row-cols-$cols";
            $chunkSize     = $cols;
        } else {
            // 处理响应式断点配置
            $classes = [];
            $maxCols = 1;

            foreach ($cols as $breakpoint => $colCount) {
                if ($colCount < 1 || $colCount > 12) {
                    throw new Exception(lang("dp#grid columns for {:breakpoint} must be between 1 and 12", ['breakpoint' => $breakpoint]));
                }
                $classes[] = "row-cols-$breakpoint-$colCount";
                $maxCols   = max($maxCols, $colCount);
            }

            $attr['class'] = ($attr['class'] ?? '') . ' ' . implode(' ', $classes);
            $chunkSize     = $maxCols;
        }

        $chunks = array_chunk($items, $chunkSize);

        foreach ($chunks as $chunk) {
            $this->row($chunk, $attr);
        }

        return $this;
    }

    /**
     * 设置列的默认样式类名
     * @param string $class
     * @return $this
     */
    public function setColClass(string $class): static
    {
        $this->colClass = $class;
        return $this;
    }

    /**
     * 清空所有行数据
     * @return $this
     */
    public function clear(): static
    {
        $this->vars['dp_page_rows'] = [];
        $this->rowIndex             = 0;
        return $this;
    }

    /**
     * 渲染页面级标签容器
     * @param array $tabs
     * @param array $options
     * @return $this
     * @throws Exception
     */
    public function tabs(array $tabs = [], array $options = []): static
    {
        if (empty($tabs)) {
            return $this;
        }

        $options = array_merge([
            'id'       => 'dp-page-tabs-' . substr(md5((string)microtime(true)), 0, 8),
            'active'   => '',
            'remember' => false,
            'fill'     => false,
            'right'    => false,
            'class'    => '',
        ], $options);

        $options['id'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$options['id']) ?: 'dp-page-tabs';
        $currentUrl    = $this->normalizeUrl($this->request->url());
        $currentPath   = $this->normalizePath($this->request->url());
        $activeByUrl   = '';
        $firstEnabled  = '';
        $items         = [];
        $hasContent    = false;

        foreach ($tabs as $key => $tab) {
            if (!is_array($tab)) {
                continue;
            }

            $tabKey = $this->normalizeTabKey((string)$key, count($items));
            $href   = $this->parseTabUrl($tab['url'] ?? '');
            $mode   = $href !== '' ? 'url' : 'content';

            $disabled = !empty($tab['disabled']);
            if ($firstEnabled === '' && !$disabled) {
                $firstEnabled = $tabKey;
            }

            $normalizedHref = $this->normalizeUrl($href);
            if (
                $mode === 'url'
                && !$disabled
                && $activeByUrl === ''
                && (
                    $normalizedHref === $currentUrl
                    || (!$this->hasQueryString($href) && $this->normalizePath($href) === $currentPath)
                )
            ) {
                $activeByUrl = $tabKey;
            }

            $content = '';
            if ($mode === 'content') {
                $hasContent = true;
                $source     = $tab['form'] ?? ($tab['content'] ?? '');
                $content    = $this->renderTabContent($source);
            }

            $items[] = [
                'key'       => $tabKey,
                'title'     => (string)($tab['title'] ?? $tabKey),
                'icon'      => (string)($tab['icon'] ?? ''),
                'disabled'  => $disabled,
                'right'     => !empty($tab['right']),
                'mode'      => $mode,
                'href'      => $href === '' ? '#' : $href,
                'link'      => $mode === 'content' ? '#' . $options['id'] . '-pane-' . $tabKey : ($href === '' ? '#' : $href),
                'target_id' => $options['id'] . '-pane-' . $tabKey,
                'content'   => $content,
                'active'    => false,
            ];
        }

        if (empty($items)) {
            return $this;
        }

        $activeKey = $activeByUrl;
        if ($activeKey === '') {
            $preferred = (string)$options['active'];
            if ($preferred !== '') {
                foreach ($items as $item) {
                    if ($item['key'] === $preferred && !$item['disabled']) {
                        $activeKey = $preferred;
                        break;
                    }
                }
            }
        }
        if ($activeKey === '') {
            $activeKey = $firstEnabled !== '' ? $firstEnabled : $items[0]['key'];
        }

        $hasActiveContent = false;
        foreach ($items as &$item) {
            $item['active'] = ($item['key'] === $activeKey && !$item['disabled']);
            if ($item['active'] && $item['mode'] === 'content') {
                $hasActiveContent = true;
            }
        }
        unset($item);

        if ($hasContent && !$hasActiveContent) {
            foreach ($items as &$item) {
                $item['active'] = false;
            }
            unset($item);

            foreach ($items as &$item) {
                if ($item['mode'] === 'content' && !$item['disabled']) {
                    $item['active'] = true;
                    break;
                }
            }
            unset($item);
        }

        $template = dp_page_path() . 'tabs.html';
        if (!is_file($template)) {
            throw new Exception(lang('dp#template not exists', ['file' => $template]));
        }

        $html = $this->view->display((string)file_get_contents($template), [
            'tabs_id'       => $options['id'],
            'tabs'          => $items,
            'has_content'   => $hasContent,
            'tabs_options'  => $options,
            'tabs_remember' => $hasContent && !empty($options['remember']) ? 1 : 0,
        ]);

        return $this->row($html);
    }

    /**
     * 解析标签 URL
     * @param mixed $url
     * @return string
     */
    private function parseTabUrl(mixed $url): string
    {
        if ($url instanceof UrlBuild) {
            return $url->build();
        }
        return trim((string)$url);
    }

    /**
     * 规范化标签 key
     * @param string $key
     * @param int $index
     * @return string
     */
    private function normalizeTabKey(string $key, int $index = 0): string
    {
        $key = trim($key);
        if ($key === '' || is_numeric($key)) {
            $key = 'tab_' . $index;
        }
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) ?: ('tab_' . $index);
    }

    /**
     * 规范化路径
     * @param string $url
     * @return string
     */
    private function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = '/' . trim((string)$path, '/');
        return $path === '//' ? '/' : $path;
    }

    /**
     * 规范化 URL（路径 + 查询字符串）
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $path  = $this->normalizePath($url);
        $query = trim((string)(parse_url($url, PHP_URL_QUERY) ?? ''));

        return $query === '' ? $path : ($path . '?' . $query);
    }

    /**
     * 判断 URL 是否带查询参数
     * @param string $url
     * @return bool
     */
    private function hasQueryString(string $url): bool
    {
        return trim((string)(parse_url($url, PHP_URL_QUERY) ?? '')) !== '';
    }

    /**
     * 渲染标签内容
     * @param mixed $source
     * @return string
     * @throws Exception
     */
    private function renderTabContent(mixed $source): string
    {
        $source = $this->resolveContent($source);

        return $this->view->display((string)$source);
    }

    /**
     * 解析可渲染内容
     * @param mixed $content
     * @return mixed
     * @throws Exception
     */
    private function resolveContent(mixed $content): mixed
    {
        if ($content instanceof Closure) {
            return $content($this);
        }

        if ($content instanceof ZRender) {
            return $content->fetch();
        }

        if ('object' === gettype($content)) {
            if (!method_exists($content, 'handle')) {
                throw new Exception(lang('dp#undefined handle method', ['class' => get_class($content)]));
            }

            return $content->handle($this);
        }

        return $content;
    }
}
