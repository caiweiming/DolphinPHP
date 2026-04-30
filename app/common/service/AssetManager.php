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

namespace app\common\service;

use Exception;
use think\facade\Config;

/**
 * 静态资源管理器
 * 用于防止重复加载CSS/JS文件，支持依赖管理和加载顺序
 */
class AssetManager
{
    /**
     * 单例实例
     * @var AssetManager|null
     */
    private static ?AssetManager $instance = null;

    /**
     * CSS文件注册表
     * @var array
     */
    private array $cssFiles = [];

    /**
     * JS文件注册表
     * @var array
     */
    private array $jsFiles = [];

    /**
     * 内联CSS代码
     * @var array
     */
    private array $inlineCss = [];

    /**
     * 内联JS代码
     * @var array
     */
    private array $inlineJs = [];

    /**
     * JS初始化代码
     * @var array
     */
    private array $initJs = [];

    /**
     * 已加载的资源
     * @var array
     */
    private array $loaded = [];

    /**
     * 私有构造函数（单例模式）
     */
    private function __construct()
    {
    }

    /**
     * 获取单例实例
     * @return AssetManager
     */
    public static function instance(): AssetManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 添加CSS文件
     * @param string|array $files CSS文件路径或路径数组
     * @param int $priority 优先级，数值越小越先加载
     * @param array $dependencies 依赖的资源ID
     * @param string|null $id 资源唯一标识，当files为数组时，id将作为组标识，每个文件仍会生成独立ID
     * @return $this
     */
    public function addCss(string|array $files, int $priority = 50, array $dependencies = [], ?string $id = null): self
    {
        $files = (array)$files;

        // 如果提供了ID且有多个文件，将ID作为组标识，但每个文件仍需独立ID
        $isMultipleFiles = count($files) > 1;
        $groupId = $isMultipleFiles && $id ? $id : null;

        foreach ($files as $index => $file) {
            // 生成实际的资源ID
            if ($isMultipleFiles && $groupId) {
                // 多文件且有组ID：使用 "组ID_索引" 格式
                $assetId = $groupId . '_' . $index;
            } elseif (!$isMultipleFiles && $id) {
                // 单文件且有ID：直接使用提供的ID
                $assetId = $id;
            } else {
                // 其他情况：自动生成ID
                $assetId = $this->generateId('css', $file);
            }

            // 检查是否已加载
            if (isset($this->loaded[$assetId])) {
                continue;
            }

            $this->cssFiles[$assetId] = [
                'file'         => $this->parseAssetPath($file),
                'priority'     => $priority,
                'dependencies' => $dependencies,
                'loaded'       => false,
                'group_id'     => $groupId  // 记录组ID便于后续管理
            ];
        }

        // 如果是组加载，标记组ID（用于isLoaded检查）
        if ($groupId) {
            $this->loaded[$groupId] = true;
        }

        return $this;
    }

    /**
     * 添加JS文件
     * @param string|array $files JS文件路径或路径数组
     * @param int $priority 优先级，数值越小越先加载
     * @param array $dependencies 依赖的资源ID
     * @param string|null $id 资源唯一标识，当files为数组时，id将作为组标识，每个文件仍会生成独立ID
     * @return $this
     */
    public function addJs(string|array $files, int $priority = 50, array $dependencies = [], ?string $id = null): self
    {
        $files = (array)$files;

        // 如果提供了ID且有多个文件，将ID作为组标识，但每个文件仍需独立ID
        $isMultipleFiles = count($files) > 1;
        $groupId = $isMultipleFiles && $id ? $id : null;

        foreach ($files as $index => $file) {
            // 生成实际的资源ID
            if ($isMultipleFiles && $groupId) {
                // 多文件且有组ID：使用 "组ID_索引" 格式
                $assetId = $groupId . '_' . $index;
            } elseif (!$isMultipleFiles && $id) {
                // 单文件且有ID：直接使用提供的ID
                $assetId = $id;
            } else {
                // 其他情况：自动生成ID
                $assetId = $this->generateId('js', $file);
            }

            // 检查是否已加载
            if (isset($this->loaded[$assetId])) {
                continue;
            }

            $this->jsFiles[$assetId] = [
                'file'         => $this->parseAssetPath($file),
                'priority'     => $priority,
                'dependencies' => $dependencies,
                'loaded'       => false,
                'group_id'     => $groupId  // 记录组ID便于后续管理
            ];
        }

        // 如果是组加载，标记组ID（用于isLoaded检查）
        if ($groupId) {
            $this->loaded[$groupId] = true;
        }

        return $this;
    }

    /**
     * 添加内联CSS代码
     * @param string $css CSS代码
     * @param string|null $id 唯一标识
     * @return $this
     */
    public function addInlineCss(string $css, ?string $id = null): self
    {
        // 检查CSS代码是否为空
        $css = trim($css);
        if (empty($css)) {
            return $this;
        }

        $id = $id ?? md5($css);

        // 防止重复添加相同的CSS代码
        if (!isset($this->inlineCss[$id])) {
            $this->inlineCss[$id] = $css;
        }

        return $this;
    }

    /**
     * 添加内联JS代码
     * @param string $js JS代码
     * @param string|null $id 唯一标识
     * @return $this
     */
    public function addInlineJs(string $js, ?string $id = null): self
    {
        // 检查JS代码是否为空
        $js = trim($js);
        if (empty($js)) {
            return $this;
        }

        $id = $id ?? md5($js);

        // 防止重复添加相同的JS代码
        if (!isset($this->inlineJs[$id])) {
            $this->inlineJs[$id] = $js;
        }

        return $this;
    }

    /**
     * 添加初始化JS代码
     * @param string $js JS代码
     * @param string $app 应用标识
     * @return $this
     */
    public function addInitJs(string $js, string $app = 'default'): self
    {
        // 检查JS代码是否为空
        $js = trim($js);
        if (empty($js)) {
            return $this;
        }

        if (!isset($this->initJs[$app])) {
            $this->initJs[$app] = [];
        }

        // 防止重复添加
        if (!in_array($js, $this->initJs[$app])) {
            $this->initJs[$app][] = $js;
        }

        return $this;
    }

    /**
     * 获取已排序的CSS文件列表
     * @return array
     */
    public function getCssFiles(): array
    {
        return $this->getSortedAssets($this->cssFiles);
    }

    /**
     * 获取已排序的JS文件列表
     * @return array
     */
    public function getJsFiles(): array
    {
        return $this->getSortedAssets($this->jsFiles);
    }

    /**
     * 获取内联CSS代码
     * @return array
     */
    public function getInlineCss(): array
    {
        return array_values($this->inlineCss);
    }

    /**
     * 获取内联JS代码
     * @return array
     */
    public function getInlineJs(): array
    {
        return array_values($this->inlineJs);
    }

    /**
     * 获取初始化JS代码
     * @return array
     */
    public function getInitJs(): array
    {
        return $this->initJs;
    }

    /**
     * 获取所有资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'css'       => $this->getCssFiles(),
            'js'        => $this->getJsFiles(),
            'extra_css' => $this->getInlineCss(),
            'extra_js'  => $this->getInlineJs(),
            'init_js'   => [
                'init' => $this->getInitJs(),
                'app'  => array_keys($this->initJs)
            ]
        ];
    }

    /**
     * 清空所有资源
     * @return $this
     */
    public function clear(): self
    {
        $this->cssFiles  = [];
        $this->jsFiles   = [];
        $this->inlineCss = [];
        $this->inlineJs  = [];
        $this->initJs    = [];
        $this->loaded    = [];

        return $this;
    }

    /**
     * 标记资源为已加载
     * @param string $id 资源ID
     * @return $this
     */
    public function markAsLoaded(string $id): self
    {
        $this->loaded[$id] = true;

        // 更新对应的资源状态
        if (isset($this->cssFiles[$id])) {
            $this->cssFiles[$id]['loaded'] = true;
        }
        if (isset($this->jsFiles[$id])) {
            $this->jsFiles[$id]['loaded'] = true;
        }

        return $this;
    }

    /**
     * 检查资源是否已加载
     * @param string $id 资源ID
     * @return bool
     */
    public function isLoaded(string $id): bool
    {
        return isset($this->loaded[$id]);
    }

    /**
     * 移除指定的资源组
     * @param string $groupId 组ID
     * @return $this
     */
    public function removeGroup(string $groupId): self
    {
        // 移除CSS文件中的组资源
        foreach ($this->cssFiles as $id => $asset) {
            if (($asset['group_id'] ?? null) === $groupId) {
                unset($this->cssFiles[$id]);
                unset($this->loaded[$id]);
            }
        }

        // 移除JS文件中的组资源
        foreach ($this->jsFiles as $id => $asset) {
            if (($asset['group_id'] ?? null) === $groupId) {
                unset($this->jsFiles[$id]);
                unset($this->loaded[$id]);
            }
        }

        // 移除组标记
        unset($this->loaded[$groupId]);

        return $this;
    }

    /**
     * 获取指定组的所有资源
     * @param string $groupId 组ID
     * @return array ['css' => [], 'js' => []]
     */
    public function getGroupAssets(string $groupId): array
    {
        $result = ['css' => [], 'js' => []];

        // 获取CSS组资源
        foreach ($this->cssFiles as $id => $asset) {
            if (($asset['group_id'] ?? null) === $groupId) {
                $result['css'][$id] = $asset;
            }
        }

        // 获取JS组资源
        foreach ($this->jsFiles as $id => $asset) {
            if (($asset['group_id'] ?? null) === $groupId) {
                $result['js'][$id] = $asset;
            }
        }

        return $result;
    }

    /**
     * 检查资源组是否已加载
     * @param string $groupId 组ID
     * @return bool
     */
    public function isGroupLoaded(string $groupId): bool
    {
        return isset($this->loaded[$groupId]);
    }

    /**
     * 生成资源唯一标识
     * @param string $type 资源类型
     * @param string $file 文件路径
     * @return string
     */
    private function generateId(string $type, string $file): string
    {
        return '__auto_' . $type . '_' . md5($file);
    }

    /**
     * 解析资源路径（应用模板替换字符串）
     * @param string $path 原始路径
     * @return string
     */
    private function parseAssetPath(string $path): string
    {
        try {
            $replaceString = Config::get('view.tpl_replace_string', []);
        } catch (Exception) {
            // 如果配置系统未初始化，直接返回原路径
            return $path;
        }

        if (!empty($replaceString)) {
            $search  = array_keys($replaceString);
            $replace = array_values($replaceString);
            $path    = str_replace($search, $replace, $path);
        }

        return $path;
    }

    /**
     * 根据优先级和依赖关系对资源进行排序
     * @param array $assets 资源数组
     * @return array
     */
    private function getSortedAssets(array $assets): array
    {
        // 解决依赖关系
        $resolved = [];
        $this->resolveDependencies($assets, $resolved);

        // 按优先级排序
        uasort($resolved, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // 提取文件路径
        return array_column($resolved, 'file');
    }

    /**
     * 递归解决依赖关系
     * @param array $assets 资源数组
     * @param array $resolved 已解决的资源
     * @param array $resolving 正在解决的资源（用于检测循环依赖）
     */
    private function resolveDependencies(array $assets, array &$resolved, array $resolving = []): void
    {
        foreach ($assets as $id => $asset) {
            if (isset($resolved[$id])) {
                continue;
            }

            $this->resolveDependenciesSingle($assets, $resolved, $resolving, $id);
        }
    }

    /**
     * 递归解决单个资源的依赖关系
     * @param array $assets 资源数组
     * @param array $resolved 已解决的资源
     * @param array $resolving 正在解决的资源（用于检测循环依赖）
     * @param string $id 要解决的资源ID
     */
    private function resolveDependenciesSingle(array $assets, array &$resolved, array &$resolving, string $id): void
    {
        if (isset($resolved[$id])) {
            return;
        }

        if (isset($resolving[$id])) {
            // 检测到循环依赖，记录警告并跳过
            error_log("AssetManager: 检测到循环依赖 - 资源ID: {$id}");
            return;
        }

        if (!isset($assets[$id])) {
            return;
        }

        $asset = $assets[$id];
        $resolving[$id] = true;

        // 先解决依赖
        foreach ($asset['dependencies'] as $depId) {
            if (isset($assets[$depId]) && !isset($resolved[$depId])) {
                // 递归解决依赖
                $this->resolveDependenciesSingle($assets, $resolved, $resolving, $depId);
            } elseif (!isset($assets[$depId])) {
                // 记录未找到的依赖
                error_log("AssetManager: 依赖资源未找到 - 资源ID: {$id}, 依赖ID: {$depId}");
            }
        }

        $resolved[$id] = $asset;
        unset($resolving[$id]);
    }
} 