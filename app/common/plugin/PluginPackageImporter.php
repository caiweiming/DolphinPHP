<?php
declare(strict_types=1);

namespace app\common\plugin;

use FilesystemIterator;
use PhpZip\Exception\ZipEntryNotFoundException;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Random\RandomException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use think\file\UploadedFile;
use Throwable;

/**
 * 插件分发包导入器
 */
class PluginPackageImporter
{
    /**
     * 单个 plugin.json 大小上限
     */
    private const MAX_METADATA_SIZE = 262144;

    /**
     * @param PluginRepository $pluginRepository
     * @param PluginMetadataValidator $metadataValidator
     */
    public function __construct(
        protected PluginRepository        $pluginRepository,
        protected PluginMetadataValidator $metadataValidator
    )
    {
    }

    /**
     * 导入插件 ZIP 包
     * @param UploadedFile $file
     * @return array{name:string,title:string,path:string}
     * @throws RandomException
     */
    public function import(UploadedFile $file): array
    {
        $this->validateUpload($file);
        $this->cleanupExpiredImports();

        $tempRoot         = $this->prepareImportDirectory();
        $sessionId        = 'import-' . date('YmdHis') . '-' . bin2hex(random_bytes(6));
        $workingDirectory = $tempRoot . DIRECTORY_SEPARATOR . $sessionId;
        $archiveDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'archive';
        $extractDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'extract';
        $this->ensureDirectory($archiveDirectory);
        $this->ensureDirectory($extractDirectory);

        $zip = new ZipFile();
        try {
            $storedFile = $file->move($archiveDirectory, $this->buildStoredFilename($file));
            $zip->openFile($storedFile->getPathname());

            $manifest       = $this->inspectArchive($zip);
            $tempPluginPath = $extractDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest['plugin_name']);
            $this->extractArchive($zip, $manifest['entries'], $tempPluginPath);

            $metadata = $this->readMetadataFromFilesystem($tempPluginPath);
            $error    = $this->metadataValidator->validate($metadata, $tempPluginPath);
            if ($error !== '') {
                throw new RuntimeException($error);
            }

            $targetPath = $this->resolveTargetPath($manifest['plugin_name']);
            $this->assertTargetAvailable($targetPath);
            $this->moveDirectory($tempPluginPath, $targetPath);
            $this->pluginRepository->clear();

            return [
                'name'  => $manifest['plugin_name'],
                'title' => trim((string)($metadata['title'] ?? $manifest['plugin_name'])),
                'path'  => $targetPath,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException('插件导入失败：' . $e->getMessage(), 0, $e);
        } finally {
            $zip->close();
            $this->deleteDirectory($workingDirectory);
        }
    }

    /**
     * 校验上传文件
     * @param UploadedFile $file
     * @return void
     */
    private function validateUpload(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new RuntimeException('上传文件无效，请重新选择插件包');
        }

        $size = (int)$file->getSize();
        if ($size <= 0) {
            throw new RuntimeException('上传文件不能为空');
        }

        $maxSize = max(0, (int)config('plugin.import.max_size', 0));
        if ($maxSize > 0 && $size > $maxSize) {
            throw new RuntimeException('插件包大小超出限制');
        }

        $extension         = strtolower($file->getOriginalExtension());
        $allowedExtensions = array_map(
            static fn(mixed $item): string => strtolower(trim((string)$item)),
            (array)config('plugin.import.allowed_extensions', ['zip'])
        );
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('仅支持上传 ZIP 插件包');
        }
    }

    /**
     * 检查 ZIP 结构
     * @param ZipFile $zip
     * @return array{
     *      plugin_name:string,
     *      entries:array<int, array{entry:string,is_dir:bool,relative:string}>
     *  }
     * @throws ZipEntryNotFoundException
     * @throws ZipException
     */
    private function inspectArchive(ZipFile $zip): array
    {
        $allEntries = $zip->getListFiles();
        $maxEntries = max(1, (int)config('plugin.import.max_entries', 2000));
        if (count($allEntries) > $maxEntries) {
            throw new RuntimeException('插件包文件数量超出限制');
        }

        $pluginNames           = [];
        $metadataEntryName     = '';
        $effectiveEntries      = [];
        $totalUncompressedSize = 0;
        $maxUncompressedSize   = max(0, (int)config('plugin.import.max_uncompressed_size', 0));

        foreach ($allEntries as $entryName) {
            $normalizedEntry = $this->normalizeEntryName($entryName);
            if ($this->shouldIgnoreEntry($normalizedEntry)) {
                continue;
            }

            $segments = $this->parseSafeSegments($normalizedEntry);
            $entry    = $zip->getEntry($entryName);

            if ($entry->isEncrypted()) {
                throw new RuntimeException('插件包包含加密文件，无法导入');
            }

            if ($entry->isUnixSymlink()) {
                throw new RuntimeException('插件包包含符号链接，无法导入');
            }

            if ($segments[0] !== 'plugins') {
                throw new RuntimeException('插件包目录结构无效，必须以 plugins/<vendor>/<name>/ 为根目录');
            }

            if (count($segments) < 3) {
                if ($entry->isDirectory()) {
                    continue;
                }

                throw new RuntimeException('插件包目录结构无效，必须以 plugins/<vendor>/<name>/ 为根目录');
            }

            $vendor = $segments[1];
            $name   = $segments[2];
            if (!$this->isValidNameSegment($vendor) || !$this->isValidNameSegment($name)) {
                throw new RuntimeException('插件包目录命名不合法');
            }

            $pluginName               = $vendor . '/' . $name;
            $pluginNames[$pluginName] = true;
            if (count($pluginNames) > 1) {
                throw new RuntimeException('一个插件包中只允许包含一个插件目录');
            }

            $relative = implode('/', array_slice($segments, 3));
            if (!$entry->isDirectory() && $relative === '') {
                throw new RuntimeException('插件包包含非法根条目');
            }

            if (!$entry->isDirectory()) {
                $entrySize = $entry->getUncompressedSize();
                if ($entrySize < 0) {
                    throw new RuntimeException('插件包包含无法识别大小的文件');
                }

                $totalUncompressedSize += $entrySize;
                if ($maxUncompressedSize > 0 && $totalUncompressedSize > $maxUncompressedSize) {
                    throw new RuntimeException('插件包解压后大小超出限制');
                }
            }

            if (!$entry->isDirectory() && $relative === 'plugin.json') {
                if ($metadataEntryName !== '' && $metadataEntryName !== $entryName) {
                    throw new RuntimeException('插件包中存在多个 plugin.json');
                }

                if ($entry->getUncompressedSize() > self::MAX_METADATA_SIZE) {
                    throw new RuntimeException('plugin.json 体积异常，无法导入');
                }

                $metadataEntryName = $entryName;
            }

            $effectiveEntries[] = [
                'entry'    => $entryName,
                'is_dir'   => $entry->isDirectory(),
                'relative' => $relative,
            ];
        }

        if ($effectiveEntries === []) {
            throw new RuntimeException('插件包中没有可导入的文件');
        }

        $pluginName = array_key_first($pluginNames);
        if (!is_string($pluginName) || $pluginName === '') {
            throw new RuntimeException('插件包中未找到有效的插件目录');
        }

        if ($metadataEntryName === '') {
            throw new RuntimeException('插件包缺少 plugins/<vendor>/<name>/plugin.json');
        }

        $metadata = $this->decodeMetadata($zip->getEntryContents($metadataEntryName));
        if (trim((string)($metadata['name'] ?? '')) !== $pluginName) {
            throw new RuntimeException('plugin.json 中的插件标识与目录不一致');
        }

        return [
            'plugin_name' => $pluginName,
            'entries'     => $effectiveEntries,
        ];
    }

    /**
     * 安全解压插件包
     * @param ZipFile $zip
     * @param array<int, array{entry:string,is_dir:bool,relative:string}> $entries
     * @param string $pluginPath
     * @throws ZipEntryNotFoundException
     * @throws ZipException
     */
    private function extractArchive(ZipFile $zip, array $entries, string $pluginPath): void
    {
        $this->ensureDirectory($pluginPath);

        foreach ($entries as $entry) {
            $relative = trim($entry['relative'], '/');
            if ($relative === '') {
                continue;
            }

            $destination = $pluginPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $this->assertPathWithinRoot($destination, $pluginPath);

            if ($entry['is_dir']) {
                $this->ensureDirectory($destination);
                continue;
            }

            $parentDirectory = dirname($destination);
            $this->ensureDirectory($parentDirectory);

            $input  = $zip->getEntryStream($entry['entry']);
            $output = @fopen($destination, 'wb');
            if (!is_resource($output)) {
                if (is_resource($input)) {
                    fclose($input);
                }
                throw new RuntimeException('无法写入导入文件：' . str_replace(root_path(), '', $destination));
            }

            try {
                $copied = stream_copy_to_stream($input, $output);
                if ($copied === false) {
                    throw new RuntimeException('插件文件解压失败：' . $relative);
                }
            } finally {
                fclose($input);
                fclose($output);
            }

            @chmod($destination, 0644);
        }
    }

    /**
     * 读取已解压元数据
     * @param string $pluginPath
     * @return array<string, mixed>
     */
    private function readMetadataFromFilesystem(string $pluginPath): array
    {
        $metadataFile = rtrim($pluginPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_file($metadataFile)) {
            throw new RuntimeException('导入后的插件目录缺少 plugin.json');
        }

        return $this->decodeMetadata((string)file_get_contents($metadataFile));
    }

    /**
     * 解码 plugin.json
     * @param string $contents
     * @return array<string, mixed>
     */
    private function decodeMetadata(string $contents): array
    {
        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new RuntimeException('plugin.json 解析失败');
        }

        return $data;
    }

    /**
     * 准备临时导入目录
     * @return string
     */
    private function prepareImportDirectory(): string
    {
        $directory = rtrim((string)config('plugin.import.temp_root', runtime_path() . 'plugins/imports'), DIRECTORY_SEPARATOR);
        if ($directory === '') {
            throw new RuntimeException('插件导入目录配置无效');
        }

        $this->ensureDirectory($directory);
        return $directory;
    }

    /**
     * 清理过期导入目录
     * @return void
     */
    private function cleanupExpiredImports(): void
    {
        $directory = rtrim((string)config('plugin.import.temp_root', runtime_path() . 'plugins/imports'), DIRECTORY_SEPARATOR);
        $ttl       = max(0, (int)config('plugin.import.keep_seconds', 3600));
        if ($directory === '' || !is_dir($directory) || $ttl <= 0) {
            return;
        }

        $expireAt = time() - $ttl;
        foreach (glob($directory . DIRECTORY_SEPARATOR . 'import-*') ?: [] as $item) {
            if (!is_dir($item)) {
                continue;
            }

            $mtime = @filemtime($item);
            if ($mtime !== false && $mtime < $expireAt) {
                $this->deleteDirectory($item);
            }
        }
    }

    /**
     * 构建临时文件名
     * @param UploadedFile $file
     * @return string
     */
    private function buildStoredFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getOriginalExtension());
        return date('YmdHis') . '-' . substr($file->sha1(), 0, 16) . '.' . $extension;
    }

    /**
     * 解析 ZIP 条目
     * @param string $entryName
     * @return array<int, string>
     */
    private function parseSafeSegments(string $entryName): array
    {
        if ($entryName === '' || str_contains($entryName, "\0")) {
            throw new RuntimeException('插件包包含非法文件名');
        }

        if (preg_match('#^(?:/|[A-Za-z]:/)#', $entryName)) {
            throw new RuntimeException('插件包包含绝对路径条目');
        }

        $segments = explode('/', trim($entryName, '/'));
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('插件包包含非法路径条目');
            }
        }

        return $segments;
    }

    /**
     * 是否忽略条目
     * @param string $entryName
     * @return bool
     */
    private function shouldIgnoreEntry(string $entryName): bool
    {
        $trimmed = trim($entryName, '/');
        if ($trimmed === '') {
            return true;
        }

        $segments = explode('/', $trimmed);
        if (in_array('__MACOSX', $segments, true)) {
            return true;
        }

        $basename = end($segments) ?: $trimmed;
        return $basename === '.DS_Store';
    }

    /**
     * 规范化 ZIP 路径
     * @param string $entryName
     * @return string
     */
    private function normalizeEntryName(string $entryName): string
    {
        return preg_replace('#/+#', '/', str_replace('\\', '/', $entryName)) ?: '';
    }

    /**
     * 判断插件目录段是否合法
     * @param string $segment
     * @return bool
     */
    private function isValidNameSegment(string $segment): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $segment) === 1;
    }

    /**
     * 解析目标目录
     * @param string $pluginName
     * @return string
     */
    private function resolveTargetPath(string $pluginName): string
    {
        $root = rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR);
        if ($root === '') {
            throw new RuntimeException('插件根目录配置无效');
        }

        $this->ensureDirectory($root);
        return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pluginName);
    }

    /**
     * 校验目标目录可用
     * @param string $targetPath
     * @return void
     */
    private function assertTargetAvailable(string $targetPath): void
    {
        $allowOverwrite = (bool)config('plugin.import.allow_overwrite', false);
        if (!$allowOverwrite && file_exists($targetPath)) {
            throw new RuntimeException('同名插件已存在，请先手动删除旧插件目录后再导入');
        }

        $pluginRoot = rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR);
        $this->assertPathWithinRoot($targetPath, $pluginRoot);
    }

    /**
     * 移动目录到目标位置
     * @param string $source
     * @param string $target
     * @return void
     */
    private function moveDirectory(string $source, string $target): void
    {
        if (!is_dir($source)) {
            throw new RuntimeException('临时插件目录不存在，无法完成导入');
        }

        $this->ensureDirectory(dirname($target));
        if (@rename($source, $target)) {
            return;
        }

        $this->copyDirectory($source, $target);
        $this->deleteDirectory($source);
    }

    /**
     * 复制目录
     * @param string $source
     * @param string $target
     * @return void
     */
    private function copyDirectory(string $source, string $target): void
    {
        $this->ensureDirectory($target);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath   = $item->getPathname();
            $relativePath = substr($sourcePath, strlen(rtrim($source, DIRECTORY_SEPARATOR)) + 1);
            $destination  = $target . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                $this->ensureDirectory($destination);
                continue;
            }

            $this->ensureDirectory(dirname($destination));
            if (!@copy($sourcePath, $destination)) {
                throw new RuntimeException('插件文件写入失败：' . $relativePath);
            }
            @chmod($destination, 0644);
        }
    }

    /**
     * 确保目录存在
     * @param string $directory
     * @return void
     */
    private function ensureDirectory(string $directory): void
    {
        if ($directory === '') {
            throw new RuntimeException('目录路径无效');
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('目录创建失败：' . $directory);
        }
    }

    /**
     * 删除目录
     * @param string $directory
     * @return void
     */
    private function deleteDirectory(string $directory): void
    {
        if ($directory === '' || !file_exists($directory)) {
            return;
        }

        if (is_file($directory) || is_link($directory)) {
            @unlink($directory);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($directory);
    }

    /**
     * 校验路径不越界
     * @param string $path
     * @param string $root
     * @return void
     */
    private function assertPathWithinRoot(string $path, string $root): void
    {
        $normalizedPath = $this->normalizeFilesystemPath($path);
        $normalizedRoot = $this->normalizeFilesystemPath($root);
        if ($normalizedRoot === '' || $normalizedPath === '') {
            throw new RuntimeException('路径校验失败');
        }

        if ($normalizedPath !== $normalizedRoot && !str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            throw new RuntimeException('插件包试图写入插件目录之外的位置');
        }
    }

    /**
     * 规范化文件系统路径
     * @param string $path
     * @return string
     */
    private function normalizeFilesystemPath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
