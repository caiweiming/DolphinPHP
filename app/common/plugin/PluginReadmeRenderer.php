<?php
declare(strict_types=1);

namespace app\common\plugin;

use DOMDocument;
use DOMElement;
use DOMException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use think\facade\Cache;
use Throwable;

/**
 * 插件 README 渲染器
 */
class PluginReadmeRenderer
{
    /**
     * 缓存前缀
     */
    private const CACHE_PREFIX = 'plugin_readme_render:';

    /**
     * Markdown 转换器
     * @var MarkdownConverter|null
     */
    private ?MarkdownConverter $converter = null;

    /**
     * 渲染 README 文件
     * @param string $path
     * @return array{html:string,raw:string,rendered:bool}
     */
    public function renderFile(string $path): array
    {
        if ($path === '' || !is_file($path)) {
            return [
                'html'     => '',
                'raw'      => '',
                'rendered' => false,
            ];
        }

        $raw = trim((string)file_get_contents($path));
        if ($raw === '') {
            return [
                'html'     => '',
                'raw'      => '',
                'rendered' => false,
            ];
        }

        $cacheKey = $this->buildCacheKey($path);

        try {
            $html = Cache::get($cacheKey);
            if (!is_string($html) || $html === '') {
                $html = $this->renderMarkdownWithContext($raw, $this->detectPluginRoot($path));
                if ($html !== '') {
                    Cache::set($cacheKey, $html, 300);
                }
            }
        } catch (Throwable) {
            $html = $this->renderMarkdownWithContext($raw, $this->detectPluginRoot($path));
        }

        return [
            'html'     => $html,
            'raw'      => $raw,
            'rendered' => $html !== '',
        ];
    }

    /**
     * 渲染 Markdown 文本
     * @param string $markdown
     * @return string
     */
    public function renderMarkdown(string $markdown): string
    {
        return $this->renderMarkdownWithContext($markdown, null);
    }

    /**
     * 带上下文渲染 Markdown
     * @param string $markdown
     * @param string|null $pluginRoot
     * @return string
     */
    private function renderMarkdownWithContext(string $markdown, ?string $pluginRoot): string
    {
        $markdown = trim($markdown);
        if ($markdown === '') {
            return '';
        }

        try {
            $html = (string)$this->getConverter()->convert($markdown);
            $html = (string)dp_clean($html, 'markdown');

            if (is_string($pluginRoot) && $pluginRoot !== '') {
                $html = $this->rewriteHtml($html, $pluginRoot);
                $html = (string)dp_clean($html, 'markdown');
            }

            return $html;
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * 获取 Markdown 转换器
     * @return MarkdownConverter
     */
    private function getConverter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $this->converter = new MarkdownConverter($environment);

        return $this->converter;
    }

    /**
     * 构建缓存键
     * @param string $path
     * @return string
     */
    private function buildCacheKey(string $path): string
    {
        $mtime = (string)(@filemtime($path) ?: 0);
        return self::CACHE_PREFIX . md5($path . '|' . $mtime);
    }

    /**
     * 检测插件根目录
     * @param string $readmePath
     * @return string
     */
    private function detectPluginRoot(string $readmePath): string
    {
        $directory = is_dir($readmePath) ? $readmePath : dirname($readmePath);
        while ($directory !== '' && $directory !== DIRECTORY_SEPARATOR && is_dir($directory)) {
            if (is_file($directory . DIRECTORY_SEPARATOR . 'plugin.json')) {
                return $directory;
            }

            $parent = dirname($directory);
            if ($parent === $directory) {
                break;
            }

            $directory = $parent;
        }

        return '';
    }

    /**
     * 重写文档中的链接和图片
     * @param string $html
     * @param string $pluginRoot
     * @return string
     */
    private function rewriteHtml(string $html, string $pluginRoot): string
    {
        if ($html === '' || $pluginRoot === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $wrapped = '<div id="dp-plugin-readme-root">' . $html . '</div>';
            $document->loadHTML(
                '<?xml encoding="utf-8" ?>' . $wrapped,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            foreach ($document->getElementsByTagName('a') as $element) {
                if ($element instanceof DOMElement) {
                    $this->rewriteAnchor($element, $pluginRoot);
                }
            }

            foreach ($document->getElementsByTagName('img') as $element) {
                if ($element instanceof DOMElement) {
                    $this->rewriteImage($element, $pluginRoot);
                }
            }

            $root = $document->getElementById('dp-plugin-readme-root');
            if (!$root instanceof DOMElement) {
                return $html;
            }

            $output = '';
            foreach ($root->childNodes as $child) {
                $output .= $document->saveHTML($child);
            }

            return $output;
        } catch (Throwable) {
            return $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * 重写链接
     * @param DOMElement $element
     * @param string $pluginRoot
     * @return void
     */
    private function rewriteAnchor(DOMElement $element, string $pluginRoot): void
    {
        $href = trim($element->getAttribute('href'));
        if ($href === '' || str_starts_with($href, '#')) {
            return;
        }

        $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
            return;
        }

        if ($scheme === 'mailto') {
            return;
        }

        if ($scheme !== '') {
            $element->removeAttribute('href');
            $element->setAttribute('title', '已移除不安全链接');
            return;
        }

        $rewritten = $this->rewriteRelativePublicUrl($href, $pluginRoot);
        if ($rewritten === '') {
            $element->removeAttribute('href');
            $element->setAttribute('title', '仅支持插件 public 目录内的相对链接');
            return;
        }

        $element->setAttribute('href', $rewritten);
    }

    /**
     * 重写图片
     * @param DOMElement $element
     * @param string $pluginRoot
     * @return void
     * @throws DOMException
     */
    private function rewriteImage(DOMElement $element, string $pluginRoot): void
    {
        $src = trim($element->getAttribute('src'));
        if ($src === '') {
            $element->parentNode?->removeChild($element);
            return;
        }

        $scheme = strtolower((string)parse_url($src, PHP_URL_SCHEME));
        if ($scheme !== '') {
            $this->replaceNodeWithNotice($element, '已拦截外部图片，仅允许插件内静态资源');
            return;
        }

        $rewritten = $this->rewriteRelativePublicUrl($src, $pluginRoot, true);
        if ($rewritten === '') {
            $this->replaceNodeWithNotice($element, '图片需位于插件 public 目录且禁止使用 SVG');
            return;
        }

        $element->setAttribute('src', $rewritten);
        $element->setAttribute('loading', 'lazy');
    }

    /**
     * 将相对路径重写为插件 public URL
     * @param string $path
     * @param string $pluginRoot
     * @param bool $imageOnly
     * @return string
     */
    private function rewriteRelativePublicUrl(string $path, string $pluginRoot, bool $imageOnly = false): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $fragment     = (string)parse_url($path, PHP_URL_FRAGMENT);
        $relativePath = (string)parse_url($path, PHP_URL_PATH);
        if ($relativePath === '') {
            return '';
        }

        $publicPath = $this->resolvePublicFile($relativePath, $pluginRoot, $imageOnly);
        if ($publicPath === '') {
            return '';
        }

        $url = $this->buildPublicUrl($pluginRoot, $publicPath);
        if ($url === '') {
            return '';
        }

        return $fragment !== '' ? $url . '#' . $fragment : $url;
    }

    /**
     * 解析 public 文件真实路径
     * @param string $relativePath
     * @param string $pluginRoot
     * @param bool $imageOnly
     * @return string
     */
    private function resolvePublicFile(string $relativePath, string $pluginRoot, bool $imageOnly): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $candidates   = [
            $pluginRoot . DIRECTORY_SEPARATOR . $relativePath,
            $pluginRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath,
        ];

        $publicRoot = realpath($pluginRoot . DIRECTORY_SEPARATOR . 'public');
        if ($publicRoot === false) {
            return '';
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real === false || !is_file($real)) {
                continue;
            }

            if (!str_starts_with($real, $publicRoot . DIRECTORY_SEPARATOR) && $real !== $publicRoot) {
                continue;
            }

            $extension = strtolower((string)pathinfo($real, PATHINFO_EXTENSION));
            if ($imageOnly && !in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                return '';
            }

            if ($extension === 'svg') {
                return '';
            }

            return ltrim(str_replace('\\', '/', substr($real, strlen($publicRoot))), '/');
        }

        return '';
    }

    /**
     * 构建插件 public URL
     * @param string $pluginRoot
     * @param string $relativePublicPath
     * @return string
     */
    private function buildPublicUrl(string $pluginRoot, string $relativePublicPath): string
    {
        $pluginBase = rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR);
        $pluginRoot = rtrim($pluginRoot, DIRECTORY_SEPARATOR);

        if (!str_starts_with($pluginRoot, $pluginBase . DIRECTORY_SEPARATOR)) {
            return '';
        }

        $relativePlugin = str_replace('\\', '/', substr($pluginRoot, strlen($pluginBase) + 1));
        $segments       = array_values(array_filter(explode('/', $relativePlugin)));
        if (count($segments) < 2) {
            return '';
        }

        $base = '/' . trim((string)config('plugin.asset.url_prefix', 'plugins'), '/')
            . '/' . $segments[0]
            . '/' . $segments[1];

        return $base . '/' . ltrim($relativePublicPath, '/');
    }

    /**
     * 用提示文本替换节点
     * @param DOMElement $element
     * @param string $message
     * @return void
     * @throws DOMException
     */
    private function replaceNodeWithNotice(DOMElement $element, string $message): void
    {
        $document = $element->ownerDocument;
        if (!$document instanceof DOMDocument || $element->parentNode === null) {
            return;
        }

        $notice = $document->createElement('blockquote', $message);
        $element->parentNode->replaceChild($notice, $element);
    }
}
