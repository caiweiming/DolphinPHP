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

namespace app\common\service;

use app\common\model\IconLibrary as IconLibraryModel;
use Exception;
use think\facade\Cache;
use think\facade\Db;
use Throwable;

/**
 * 在线图标库服务
 */
class IconLibraryService
{
    /**
     * 图标库缓存键
     */
    private const CACHE_KEY_LIBS = 'icon_library:enabled_libs';

    /**
     * CSS 资源缓存键
     */
    private const CACHE_KEY_CSS = 'icon_library:autoload_css';

    /**
     * 缓存时间
     */
    private const CACHE_TTL = 600;

    /**
     * 远程 CSS 最大大小
     */
    private const MAX_REMOTE_CSS_BYTES = 2097152;

    /**
     * 图标库模型
     * @var IconLibraryModel
     */
    protected IconLibraryModel $model;

    /**
     * @param IconLibraryModel|null $model
     */
    public function __construct(?IconLibraryModel $model = null)
    {
        $this->model = $model ?? new IconLibraryModel();
    }

    /**
     * 获取启用的数据库图标库
     * @return array
     * @throws Throwable
     */
    public function getEnabledLibraries(): array
    {
        return Cache::remember(self::CACHE_KEY_LIBS, function () {
            $rows = $this->model
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            $result = [];
            foreach ($rows as $row) {
                $html  = trim((string)($row['html'] ?? ''));
                $libId = trim((string)($row['lib_id'] ?? ''));
                if ($libId === '' || $html === '') {
                    continue;
                }

                $result[] = [
                    'id'    => $libId,
                    'label' => $this->normalizeLabel((string)($row['label'] ?? $libId)),
                    'html'  => $html,
                ];
            }

            return $result;
        }, self::CACHE_TTL);
    }

    /**
     * 获取自动加载的在线图标库 CSS
     * @return array
     * @throws Throwable
     */
    public function getAutoloadCss(): array
    {
        return Cache::remember(self::CACHE_KEY_CSS, function () {
            $rows = $this->model
                ->where('status', 1)
                ->where('autoload', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            $cssList = [];
            foreach ($rows as $row) {
                $sourceUrl = trim((string)($row['source_url'] ?? ''));
                if ($sourceUrl !== '') {
                    $cssList[] = $sourceUrl;
                }
            }

            return array_values(array_unique($cssList));
        }, self::CACHE_TTL);
    }

    /**
     * 创建在线图标库
     * @param array $data
     * @return IconLibraryModel
     * @throws Exception
     */
    public function createOnlineLibrary(array $data): IconLibraryModel
    {
        $payload    = $this->normalizePayload($data);
        $definition = $this->syncDefinition($payload);
        $record     = $this->buildPersistData($payload, $definition);

        /** @var IconLibraryModel $library */
        $library = Db::transaction(function () use ($record) {
            return $this->model->create($record);
        });

        $this->clearCache();
        return $this->reloadModel($this->getLibraryPrimaryKey($library));
    }

    /**
     * 更新在线图标库
     * @param IconLibraryModel $library
     * @param array $data
     * @return IconLibraryModel
     * @throws Exception
     */
    public function updateOnlineLibrary(IconLibraryModel $library, array $data): IconLibraryModel
    {
        $payload    = $this->normalizePayload($data);
        $definition = $this->syncDefinition($payload);
        $record     = $this->buildPersistData($payload, $definition);

        Db::transaction(function () use ($library, $record) {
            $library->save($record);
        });

        $this->clearCache();
        return $this->reloadModel($this->getLibraryPrimaryKey($library));
    }

    /**
     * 刷新在线图标库
     * @param IconLibraryModel|int $library
     * @return IconLibraryModel
     * @throws Exception
     */
    public function refreshOnlineLibrary(IconLibraryModel|int $library): IconLibraryModel
    {
        $library = $this->resolveLibrary($library);
        $payload = [
            'lib_id'      => (string)$library['lib_id'],
            'label'       => (string)$library['label'],
            'source_type' => (string)$library['source_type'],
            'source_url'  => (string)$library['source_url'],
            'prefix'      => (string)$library['prefix'],
            'base_class'  => (string)$library['base_class'],
            'autoload'    => (int)$library['autoload'],
            'status'      => (int)$library['status'],
            'sort'        => (int)$library['sort'],
        ];

        try {
            $definition = $this->syncDefinition($payload);
            $library->save($this->buildPersistData($payload, $definition));
            $this->clearCache();
            return $this->reloadModel($this->getLibraryPrimaryKey($library));
        } catch (Exception $e) {
            $library->save([
                'sync_status' => 0,
                'last_error'  => $this->truncateError($e->getMessage()),
            ]);
            $this->clearCache();
            throw $e;
        }
    }

    /**
     * 清理图标库缓存
     * @return void
     */
    public function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY_LIBS);
        Cache::delete(self::CACHE_KEY_CSS);
    }

    /**
     * 根据 CSS 生成图标库定义
     * @param string $css
     * @param string $id
     * @param string $label
     * @param string $prefix
     * @param string $base
     * @return array
     * @throws Exception
     */
    public function buildLibraryDefinition(
        string $css,
        string $id,
        string $label = '',
        string $prefix = '',
        string $base = ''
    ): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new Exception('图标库标识不能为空');
        }

        $label   = $this->normalizeLabel(trim($label) !== '' ? $label : $id);
        $cssPath = $this->prepareCssPath($css);

        try {
            $classes = $this->extractClasses($cssPath['path'], $prefix);
        } finally {
            if (($cssPath['temporary'] ?? false) && is_file($cssPath['path'])) {
                @unlink($cssPath['path']);
            }
        }

        if ($classes === []) {
            throw new Exception('未解析到任何图标类，请检查图标库链接与前缀配置');
        }

        return [
            'id'         => $id,
            'label'      => $label,
            'html'       => $this->buildButtonsHtml($classes, $base),
            'icon_count' => count($classes),
        ];
    }

    /**
     * 校验在线图标库来源
     * @param string $sourceType
     * @param string $sourceUrl
     * @return void
     * @throws Exception
     */
    public function assertSupportedOnlineSource(string $sourceType, string $sourceUrl): void
    {
        if ($sourceType !== IconLibraryModel::SOURCE_TYPE_ICONFONT) {
            throw new Exception('当前仅支持阿里 Iconfont 在线图标库');
        }

        $sourceUrl = trim($sourceUrl);
        if ($sourceUrl === '') {
            throw new Exception('图标库 CSS 链接不能为空');
        }

        if (!preg_match('#^(https?:)?//at\.alicdn\.com/.+\.css(?:\?.*)?$#i', $sourceUrl)) {
            throw new Exception('当前仅支持阿里 Iconfont 生成的 at.alicdn.com CSS 链接');
        }
    }

    /**
     * 规范化请求数据
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function normalizePayload(array $data): array
    {
        $payload = [
            'lib_id'      => trim((string)($data['lib_id'] ?? '')),
            'label'       => trim((string)($data['label'] ?? '')),
            'source_type' => trim((string)($data['source_type'] ?? IconLibraryModel::SOURCE_TYPE_ICONFONT)),
            'source_url'  => trim((string)($data['source_url'] ?? '')),
            'prefix'      => trim((string)($data['prefix'] ?? '')),
            'base_class'  => trim((string)($data['base_class'] ?? '')),
            'autoload'    => (int)($data['autoload'] ?? 1),
            'status'      => (int)($data['status'] ?? 1),
            'sort'        => (int)($data['sort'] ?? 100),
        ];

        $payload['label']    = $this->normalizeLabel($payload['label'] !== '' ? $payload['label'] : $payload['lib_id']);
        $payload['autoload'] = $payload['autoload'] === 1 ? 1 : 0;
        $payload['status']   = $payload['status'] === 1 ? 1 : 0;
        $payload['sort']     = $payload['sort'] > 0 ? $payload['sort'] : 100;

        $this->assertSupportedOnlineSource($payload['source_type'], $payload['source_url']);

        if ($payload['prefix'] === '') {
            throw new Exception('图标类前缀不能为空');
        }

        return $payload;
    }

    /**
     * 执行同步并生成图标定义
     * @param array $payload
     * @return array
     * @throws Exception
     */
    protected function syncDefinition(array $payload): array
    {
        return $this->buildLibraryDefinition(
            $payload['source_url'],
            $payload['lib_id'],
            $payload['label'],
            $payload['prefix'],
            $payload['base_class']
        );
    }

    /**
     * 组装持久化数据
     * @param array $payload
     * @param array $definition
     * @return array
     */
    protected function buildPersistData(array $payload, array $definition): array
    {
        return [
            'lib_id'         => $payload['lib_id'],
            'label'          => $this->normalizeLabel((string)($definition['label'] ?? $payload['label'])),
            'source_type'    => $payload['source_type'],
            'source_url'     => $payload['source_url'],
            'prefix'         => $payload['prefix'],
            'base_class'     => $payload['base_class'],
            'autoload'       => $payload['autoload'],
            'html'           => $definition['html'],
            'icon_count'     => (int)$definition['icon_count'],
            'sort'           => $payload['sort'],
            'status'         => $payload['status'],
            'sync_status'    => 1,
            'last_sync_time' => time(),
            'last_error'     => '',
        ];
    }

    /**
     * 解析图标类
     * @param string $cssPath
     * @param string $prefix
     * @return array
     * @throws Exception
     */
    protected function extractClasses(string $cssPath, string $prefix = ''): array
    {
        $content = file_get_contents($cssPath);
        if ($content === false) {
            throw new Exception('读取 CSS 文件失败');
        }

        $classes = [];

        if (preg_match_all('/([^{}]+)\{[^{}]*--fa\s*:\s*[\'"][^\'"]+[\'"][^{}]*}/i', $content, $matches)) {
            foreach ($matches[1] as $selectors) {
                if (preg_match_all('/\.([a-zA-Z0-9_-]+)\b/', $selectors, $classMatches)) {
                    foreach ($classMatches[1] as $class) {
                        $classes[] = $class;
                    }
                }
            }
        }

        if (preg_match_all('/([^{}]+)\{[^{}]*\bcontent\s*:\s*[\'"][^\'"]+[\'"][^{}]*}/i', $content, $matches)) {
            foreach ($matches[1] as $selectors) {
                if (preg_match_all('/\.([a-zA-Z0-9_-]+)::?before\b/', $selectors, $classMatches)) {
                    foreach ($classMatches[1] as $class) {
                        $classes[] = $class;
                    }
                }
            }
        }

        $result = [];
        foreach ($classes as $class) {
            if ($prefix !== '' && !str_starts_with($class, $prefix)) {
                continue;
            }
            $result[] = $class;
        }

        $result = array_values(array_unique($result));
        sort($result, SORT_STRING);
        return $result;
    }

    /**
     * 构造图标按钮 HTML
     * @param array $classes
     * @param string $base
     * @return string
     */
    protected function buildButtonsHtml(array $classes, string $base = ''): string
    {
        $items = [];
        foreach ($classes as $class) {
            $value   = trim(($base !== '' ? $base . ' ' : '') . $class);
            $items[] = '<button type="button" class="dp-icon-item" data-value="' . $value
                . '" title="' . $value . '"><i class="' . $value . '"></i></button>';
        }

        return implode('', $items);
    }

    /**
     * 准备 CSS 文件路径
     * @param string $css
     * @return array
     * @throws Exception
     */
    protected function prepareCssPath(string $css): array
    {
        $css = trim($css);
        if ($css === '') {
            throw new Exception('CSS 文件路径不能为空');
        }

        if ($this->isRemotePath($css)) {
            $path = $this->downloadRemoteCss($css);
            if ($path === null) {
                throw new Exception('CSS 远程地址无法读取：' . $css);
            }

            return [
                'path'      => $path,
                'temporary' => true,
            ];
        }

        $root = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $path = $this->resolvePath($css, $root);
        if ($path === null || !is_file($path)) {
            throw new Exception('CSS 文件不存在：' . $css);
        }

        return [
            'path'      => $path,
            'temporary' => false,
        ];
    }

    /**
     * 是否远程路径
     * @param string $path
     * @return bool
     */
    protected function isRemotePath(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//');
    }

    /**
     * 解析项目路径
     * @param string $path
     * @param string $root
     * @return string|null
     */
    protected function resolvePath(string $path, string $root): ?string
    {
        if ($path === '') {
            return null;
        }

        if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = $root . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    /**
     * 下载远程 CSS
     * @param string $url
     * @return string|null
     */
    protected function downloadRemoteCss(string $url): ?string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'iconlib_');
        if ($tmpFile === false) {
            return null;
        }

        $content = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_USERAGENT, 'DolphinPHP-IconLib');
                $content = curl_exec($ch);
                curl_close($ch);
            }
        }

        if ($content === null || $content === false) {
            $context = stream_context_create([
                'http'  => [
                    'timeout' => 20,
                    'header'  => "User-Agent: DolphinPHP-IconLib\r\n",
                ],
                'https' => [
                    'timeout' => 20,
                    'header'  => "User-Agent: DolphinPHP-IconLib\r\n",
                ],
            ]);
            $content = @file_get_contents($url, false, $context);
        }

        if (!is_string($content) || $content === '') {
            @unlink($tmpFile);
            return null;
        }

        if (strlen($content) > self::MAX_REMOTE_CSS_BYTES) {
            @unlink($tmpFile);
            return null;
        }

        if (file_put_contents($tmpFile, $content) === false) {
            @unlink($tmpFile);
            return null;
        }

        return $tmpFile;
    }

    /**
     * @param IconLibraryModel|int $library
     * @return IconLibraryModel
     * @throws Exception
     */
    protected function resolveLibrary(IconLibraryModel|int $library): IconLibraryModel
    {
        if ($library instanceof IconLibraryModel) {
            return $library;
        }

        return $this->reloadModel($library);
    }

    /**
     * 安全获取模型主键，避免直接访问未初始化的强类型属性。
     * @param IconLibraryModel $library
     * @return int
     * @throws Exception
     */
    protected function getLibraryPrimaryKey(IconLibraryModel $library): int
    {
        $id = $library->getData('id');
        if ($id === null || $id === '') {
            $id = $library->getAttr('id');
        }

        $id = (int)$id;
        if ($id <= 0) {
            throw new Exception('图标库主键获取失败');
        }

        return $id;
    }

    /**
     * 重新加载模型
     * @param int $id
     * @return IconLibraryModel
     * @throws Exception
     */
    protected function reloadModel(int $id): IconLibraryModel
    {
        $library = $this->model->find($id);
        if (!$library) {
            throw new Exception('图标库不存在');
        }

        return $library;
    }

    /**
     * 截断错误消息
     * @param string $message
     * @return string
     */
    protected function truncateError(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '同步失败';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($message, 0, 500);
        }

        return substr($message, 0, 500);
    }

    /**
     * 规范化图标库显示名称，避免把字面量换行转义写入配置。
     * @param string $label
     * @return string
     */
    protected function normalizeLabel(string $label): string
    {
        $label = str_replace(["\\r\\n", "\\n", "\\r"], ' ', $label);
        $label = str_replace(["\r\n", "\n", "\r"], ' ', $label);

        if (function_exists('preg_replace')) {
            $label = preg_replace('/\s+/u', ' ', $label) ?? $label;
        }

        return trim($label);
    }
}
