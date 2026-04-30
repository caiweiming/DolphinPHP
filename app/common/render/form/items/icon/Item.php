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

namespace app\common\render\form\items\icon;

use app\common\abstract\FormItem;
use app\common\service\IconLibraryService;
use think\facade\Config;
use Throwable;

/**
 * 图标选择器组件
 */
class Item extends FormItem
{
    /**
     * 图标列表提取缓存
     * @var array<string, array<int, string>>
     */
    protected static array $iconListCache = [];

    /**
     * 渲染
     * @param array $params
     * @return array
     * @throws Throwable
     */
    public function handle(array $params = []): array
    {
        $options                       = $params['options'] ?? [];
        $libsFiles                     = $params['libs_files'] ?? ($params['libs_file'] ?? []);
        $builtinOnly                   = !empty($params['builtin_only']);
        $iconLibraries                 = $this->buildIconLibraries($libsFiles, $builtinOnly);
        $value                         = $params['value'] ?? '';
        $params['default_icon']        = $value !== ''
            ? $value
            : ($options['default_icon'] ?? 'fa fa-icons');
        $params['icon_libraries']      = array_map(static function (array $library): array {
            return [
                'id'    => $library['id'],
                'label' => $library['label'],
                'count' => $library['count'],
            ];
        }, $iconLibraries);
        $params['icon_libraries_json'] = $this->encodeLibrariesJson($iconLibraries);
        $params['icon_empty']          = $iconLibraries === [];
        $params['options']             = dp_parse_options($options);
        return $params;
    }

    /**
     * 构建图标库数据
     * @param string|array $libsFiles
     * @param bool $builtinOnly
     * @return array
     * @throws Throwable
     */
    protected function buildIconLibraries(string|array $libsFiles = [], bool $builtinOnly = false): array
    {
        $libs = $this->loadLibs($libsFiles, $builtinOnly);
        if (empty($libs)) {
            return [];
        }

        $libraries = [];

        foreach ($libs as $lib) {
            $id    = trim((string)($lib['id'] ?? ''));
            $label = (string)($lib['label'] ?? $id);
            $html  = trim((string)($lib['html'] ?? ''));
            if ($id === '' || $html === '') {
                continue;
            }

            $icons = $this->extractIconsFromHtml($html);
            if ($icons === []) {
                continue;
            }

            $libraries[] = [
                'id'    => $id,
                'label' => $label,
                'icons' => $icons,
                'count' => count($icons),
            ];
        }

        return $libraries;
    }

    /**
     * 规范化图标库文件
     * @param string|array $files
     * @return array
     */
    protected function normalizeLibFiles(string|array $files): array
    {
        $files  = is_array($files) ? $files : ($files !== '' ? [$files] : []);
        $root   = root_path();
        $result = [];

        foreach ($files as $file) {
            if (!is_string($file) || $file === '') {
                continue;
            }
            $fullPath = $file;

            if (!str_starts_with($file, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $file)) {
                $fullPath = $root . ltrim($file, DIRECTORY_SEPARATOR);
            }

            $real = realpath($fullPath);
            if ($real === false || !is_file($real)) {
                continue;
            }
            if (!str_starts_with($real, $root)) {
                continue;
            }
            $result[] = $real;
        }

        return array_values(array_unique($result));
    }

    /**
     * 读取图标库配置
     * @param string|array $libsFiles
     * @param bool $builtinOnly
     * @return array
     * @throws Throwable
     */
    protected function loadLibs(string|array $libsFiles = [], bool $builtinOnly = false): array
    {
        $libs       = [];
        $defaultDir = __DIR__ . DIRECTORY_SEPARATOR . 'libs';
        if (is_dir($defaultDir)) {
            foreach (glob($defaultDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
                $data = include $file;
                if (is_array($data)) {
                    $libs = $this->appendLibrary($libs, $data);
                }
            }
        }

        if ($builtinOnly) {
            return array_values($libs);
        }

        $configFiles = [];
        $config      = config('icon');
        if (is_array($config) && !empty($config['libs']) && is_array($config['libs'])) {
            foreach ($config['libs'] as $lib) {
                if (!is_array($lib)) {
                    continue;
                }
                if (isset($lib['enabled']) && !$lib['enabled']) {
                    continue;
                }
                $file = $lib['file'] ?? '';
                if (is_string($file) && $file !== '') {
                    $configFiles[] = $file;
                }
            }
        }

        $extraFiles = $this->normalizeLibFiles(array_merge($configFiles, (array)$libsFiles));
        foreach ($extraFiles as $file) {
            $data = include $file;
            if (is_array($data)) {
                $libs = $this->appendLibrary($libs, $data);
            }
        }

        /** @var IconLibraryService $service */
        $service = app(IconLibraryService::class);
        foreach ($service->getEnabledLibraries() as $library) {
            $libs = $this->appendLibrary($libs, $library);
        }

        return array_values($libs);
    }

    /**
     * 从按钮 HTML 中提取图标 class 值
     * @param string $html
     * @return array<int, string>
     */
    protected function extractIconsFromHtml(string $html): array
    {
        $cacheKey = md5($html);
        if (isset(self::$iconListCache[$cacheKey])) {
            return self::$iconListCache[$cacheKey];
        }

        $icons = [];
        if (preg_match_all('/\bdata-value=(["\'])(.*?)\1/i', $html, $matches)) {
            foreach ($matches[2] as $value) {
                $value = trim($value);
                if ($value === '' || isset($icons[$value])) {
                    continue;
                }
                $icons[$value] = $value;
            }
        }

        return self::$iconListCache[$cacheKey] = array_values($icons);
    }

    /**
     * 编码图标库 JSON，供前端懒渲染使用
     * @param array $libraries
     * @return string
     */
    protected function encodeLibrariesJson(array $libraries): string
    {
        $json = json_encode(
            $libraries,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        return is_string($json) ? $json : '[]';
    }

    /**
     * 获取资源
     * @return array[]
     * @throws Throwable
     */
    public function getAssets(): array
    {
        return [
            'css'  => $this->getLibsCss(),
            'init' => ['icon']
        ];
    }

    /**
     * 获取扩展图标库css路径
     * @return array
     * @throws Throwable
     */
    private function getLibsCss(): array
    {
        $config = Config::get('icon', [
            'auto_load_css' => true,
            'libs'          => [],
        ]);
        if (!($config['auto_load_css'] ?? true)) {
            return [];
        }

        $libs = $config['libs'] ?? [];
        if (!is_array($libs)) {
            return [];
        }

        $cssList = [];
        foreach ($libs as $lib) {
            if (!is_array($lib)) {
                continue;
            }
            if (isset($lib['enabled']) && !$lib['enabled']) {
                continue;
            }
            if (isset($lib['autoload']) && !$lib['autoload']) {
                continue;
            }
            $css = $lib['css'] ?? '';
            if (is_string($css) && $css !== '') {
                $cssList[] = $css;
            } elseif (is_array($css)) {
                foreach ($css as $item) {
                    if (is_string($item) && $item !== '') {
                        $cssList[] = $item;
                    }
                }
            }
        }

        /** @var IconLibraryService $service */
        $service = app(IconLibraryService::class);
        $cssList = array_merge($cssList, $service->getAutoloadCss());

        return array_values(array_unique($cssList));
    }

    /**
     * 按图标库标识合并库定义
     * @param array $libs
     * @param array $library
     * @return array
     */
    private function appendLibrary(array $libs, array $library): array
    {
        $id   = trim((string)($library['id'] ?? ''));
        $html = trim((string)($library['html'] ?? ''));
        if ($id === '' || $html === '') {
            return $libs;
        }

        $libs[$id] = $library;
        return $libs;
    }
}
