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
use think\App;
use think\facade\Config;
use think\View;
use app\common\Request;
use app\common\interface\ZRender;
use app\common\service\AssetManager;

/**
 * 渲染器公共类
 */
abstract class Common
{
    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * Request实例
     * @var \think\Request|Request
     */
    protected \think\Request|Request $request;

    /**
     * 视图实例
     * @var View
     */
    protected View $view;

    /**
     * 列的默认样式类名
     * @var string
     */
    protected string $colClass = '';

    /**
     * 模板变量
     * 注意：此属性会被子类重写以提供各自的默认值
     * @var array
     */
    protected array $vars = [];

    /**
     * 表单模板
     * @var string
     */
    protected string $template = '';

    /**
     * 资源管理器实例
     * @var AssetManager
     */
    protected AssetManager $assetManager;

    /**
     * 构造方法
     * @access public
     * @param App $app
     * @param View $view
     */
    public function __construct(App $app, View $view)
    {
        $this->app          = $app;
        $this->view         = $view;
        $this->request      = $this->app->request;
        $this->assetManager = AssetManager::instance();

        // 初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
    }

    /**
     * 添加额外js代码
     * @param string $content
     * @return $this
     */
    public function addExtraJs(string $content): static
    {
        $this->assetManager->addInlineJs($content);
        return $this;
    }

    /**
     * 添加额外css代码
     * @param string $content
     * @return $this
     */
    public function addExtraCss(string $content): static
    {
        $this->assetManager->addInlineCss($content);
        return $this;
    }

    /**
     * 添加额外初始化js命令
     * @param string $content
     * @param string $app 应用标识
     * @return $this
     */
    public function addInitJs(string $content, string $app = 'default'): static
    {
        $this->assetManager->addInitJs($content, $app);
        return $this;
    }

    /**
     * 追加js链接
     * @param string|array $urls
     * @param int $priority 优先级，数值越小越先加载
     * @param array $dependencies 依赖的资源ID
     * @return $this
     */
    public function addJsUrl(string|array $urls, int $priority = 50, array $dependencies = []): static
    {
        $this->assetManager->addJs($urls, $priority, $dependencies);
        return $this;
    }

    /**
     * 添加css链接
     * @param string|array $urls
     * @param int $priority 优先级，数值越小越先加载
     * @param array $dependencies 依赖的资源ID
     * @return $this
     */
    public function addCssUrl(string|array $urls, int $priority = 50, array $dependencies = []): static
    {
        $this->assetManager->addCss($urls, $priority, $dependencies);
        return $this;
    }

    /**
     * 获取资源管理器实例
     * @return AssetManager
     */
    public function getAssetManager(): AssetManager
    {
        return $this->assetManager;
    }

    /**
     * 获取所有资源数据
     * @return array
     */
    public function assets(): array
    {
        return $this->assetManager->getAssets();
    }

    /**
     * 获取单个模板变量
     * @param string $name
     * @return array|mixed|null
     * @throws Exception
     */
    public function getVar(string $name = ''): mixed
    {
        if (empty($name)) {
            throw new Exception(lang('dp#variable name cannot be empty'));
        }

        return $this->vars[$name] ?? null;
    }

    /**
     * 获取所有模板变量
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * 设置单个模板变量
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws Exception
     */
    public function setVar(string $name, mixed $value): static
    {
        if (empty($name)) {
            throw new Exception(lang('dp#variable name cannot be empty'));
        }

        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * 追加模板变量
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws Exception
     */
    public function pushVar(string $name, mixed $value): static
    {
        if (empty($name)) {
            throw new Exception(lang('dp#variable name cannot be empty'));
        }

        $this->vars[$name][] = $value;
        return $this;
    }

    /**
     * 设置多个模板变量
     * @param array $variables
     * @return $this
     */
    public function setVars(array $variables): static
    {
        $this->vars = array_merge($this->vars, $variables);
        return $this;
    }

    /**
     * 解析列
     * @param mixed $col 列数据，可以是字符串、数组、闭包或对象
     * @return array|string
     * @throws Exception
     */
    protected function parseCol(mixed $col): array|string
    {
        // 换行符
        if (is_string($col) && in_array($col, ['newline', '-', 'break'])) {
            return '-';
        }

        $_col = [
            'content' => '',
            'class'   => $this->colClass,
            'attr'    => ''
        ];

        if ($col instanceof Closure) {
            $_col['content'] = $col($this);
        } elseif ($col instanceof ZRender) {
            $_col['content'] = $col->fetch();
        } elseif ('object' === gettype($col)) {
            $_col['content'] = $this->parseColObject($col);
        } elseif (is_array($col)) {
            $_col = array_merge($_col, $this->parseColArray($col));
        } else {
            $_col['content'] = $col;
        }

        $_col['content'] = $this->view->display($_col['content']);

        return $_col;
    }

    /**
     * 解析对象列
     * @param object $col
     * @return string
     * @throws Exception
     */
    protected function parseColObject(object $col): string
    {
        $class = get_class($col);
        if (!method_exists($col, 'handle')) {
            throw new Exception(lang('dp#undefined handle method', ['class' => $class]));
        }

        $content = $col->handle($this);
        if (!is_string($content)) {
            throw new Exception(lang('dp#must return a string', ['class' => $class]));
        }

        return $content;
    }

    /**
     * 解析数组列
     * @param array $col
     * @return array
     * @throws Exception
     */
    protected function parseColArray(array $col): array
    {
        $_col = [
            'content' => $col[0] ?? ($col['content'] ?? ''),
            'class'   => $col[1] ?? ($col['class'] ?? ''),
            'attr'    => $col[2] ?? ($col['attr'] ?? []),
        ];

        if ($_col['content'] instanceof Closure) {
            $_col['content'] = $_col['content']($this);
        } elseif ($_col['content'] instanceof ZRender) {
            $_col['content'] = $_col['content']->fetch();
        } elseif ('object' === gettype($_col['content'])) {
            $_col['content'] = $this->parseColObject($_col['content']);
        } elseif (is_array($_col['content'])) {
            if (is_numeric(key($_col['content']))) {
                $params = [
                    'type'    => $_col['content'][0] ?? '',
                    'name'    => $_col['content'][1] ?? '',
                    'label'   => $_col['content'][2] ?? '',
                    'tips'    => $_col['content'][3] ?? '',
                    'value'   => $_col['content'][4] ?? '',
                    'options' => $_col['content'][5] ?? [],
                    'width'   => $_col['content'][6] ?? '',
                ];
            } else {
                $params = $_col['content'];
            }

            if (!method_exists($this, 'item')) {
                throw new Exception(lang('dp#undefined item method', ['class' => get_class($this)]));
            }

            $_col['content'] = $this->item($params, true);
        }

        $_col['class'] = $this->parseClass($_col['class']);

        // 如果属性内有class，则合并到上级class
        if (isset($_col['attr']['class'])) {
            $_col['class'] .= ' ' . $_col['attr']['class'];
            unset($_col['attr']['class']);
        }

        $_col['class'] = trim($_col['class']);
        $_col['attr']  = dp_arr2str($_col['attr']);

        return $_col;
    }

    /**
     * 解析样式名称
     * @param mixed $class
     * @param string $default
     * @return string
     */
    protected function parseClass(mixed $class = '', string $default = ''): string
    {
        if (empty($class)) {
            return $default;
        }

        $classParts = explode(',', (string)$class);
        foreach ($classParts as $key => $value) {
            $classParts[$key] = $this->colClass . '-' . trim($value);
        }

        return $this->colClass . ' ' . implode(' ', $classParts);
    }

    /**
     * 获取布局文件
     * @throws Exception
     */
    protected function getLayoutTemplate(string $type = ''): void
    {
        if (empty($this->template)) {
            $template       = Config::get($type . '.view.layout', 'layout.html');
            $template       = dp_render_path() . $type . DIRECTORY_SEPARATOR . ltrim($template, '/');
            $this->template = $template;
        } elseif (!is_file($this->template)) {
            $this->template = dp_render_path() . $type . DIRECTORY_SEPARATOR . $this->template . '.' . Config::get('view.view_suffix');
        }

        if (!is_file($this->template)) {
            throw new Exception(lang('dp#layout template not exists', ['file' => $this->template]));
        }
    }

    /**
     * 将AssetManager的资源数据设置到模板变量中
     * @param array $assetData AssetManager返回的资源数据
     */
    protected function setAssetsToVars(array $assetData): void
    {
        $this->vars['dp_file_css'] = array_merge($this->vars['dp_file_css'] ?? [], $assetData['css']);

        // 合并JS文件
        $this->vars['dp_file_js'] = array_merge($this->vars['dp_file_js'] ?? [], $assetData['js']);

        // 合并额外CSS代码
        $this->vars['dp_extra_css'] = array_merge($this->vars['dp_extra_css'] ?? [], $assetData['extra_css']);

        // 合并额外JS代码
        $this->vars['dp_extra_js'] = array_merge($this->vars['dp_extra_js'] ?? [], $assetData['extra_js']);

        // 处理初始化JS
        if (!empty($assetData['init_js']['init'])) {
            foreach ($assetData['init_js']['init'] as $app => $initJs) {
                $this->vars['dp_init_js'][$app] = array_unique($initJs);
            }
        }

        // 去重处理
        $this->vars['dp_file_css']  = array_values(array_unique($this->vars['dp_file_css']));
        $this->vars['dp_file_js']   = array_values(array_unique($this->vars['dp_file_js']));
        $this->vars['dp_extra_css'] = array_values(array_unique($this->vars['dp_extra_css']));
        $this->vars['dp_extra_js']  = array_values(array_unique($this->vars['dp_extra_js']));
    }

    /**
     * 完成模板渲染的通用流程
     * @param string $template
     * @param array $vars
     * @return string
     * @throws Exception
     */
    protected function finalizeFetch(string $template = '', array $vars = []): string
    {
        // 使用AssetManager统一管理资源
        $assetData = $this->assetManager->getAssets();
        $this->setAssetsToVars($assetData);

        $this->vars = array_merge($this->vars, $vars);
        if ($template !== '') {
            $this->template = $template;
        }

        return $this->view->fetch($this->template, $this->vars);
    }
}
