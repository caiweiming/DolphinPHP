<?php
declare(strict_types=1);

namespace app\common\application;

use app\common\service\AppService;
use FilesystemIterator;
use PhpZip\Exception\ZipEntryNotFoundException;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Random\RandomException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use think\file\UploadedFile;
use think\facade\Db;
use Throwable;

/**
 * 应用分发包导入器
 */
class AppPackageImporter
{
    /**
     * 单个 app.json 大小上限
     */
    private const MAX_METADATA_SIZE = 262144;

    /**
     * @param AppMetadataValidator $metadataValidator
     * @param AppService $appService
     */
    public function __construct(
        protected AppMetadataValidator $metadataValidator,
        protected AppService           $appService
    )
    {
    }

    /**
     * 导入应用 ZIP 包
     * @param UploadedFile $file
     * @return array{name:string,title:string,path:string}
     * @throws RandomException
     */
    public function import(UploadedFile $file): array
    {
        $context = [
            'original_name' => $file->getOriginalName(),
            'size'          => (int)$file->getSize(),
        ];
        $appName = '';

        $this->validateUpload($file);
        $this->cleanupExpiredImports();

        $tempRoot         = $this->prepareImportDirectory();
        $sessionId        = 'import-' . date('YmdHis') . '-' . bin2hex(random_bytes(6));
        $workingDirectory = $tempRoot . DIRECTORY_SEPARATOR . $sessionId;
        $archiveDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'archive';
        $extractDirectory = $workingDirectory . DIRECTORY_SEPARATOR . 'extract';
        $this->ensureDirectory($archiveDirectory);
        $this->ensureDirectory($extractDirectory);

        $zip           = new ZipFile();
        $targetPath    = '';
        $movedToTarget = false;

        try {
            $storedFile = $file->move($archiveDirectory, $this->buildStoredFilename($file));
            $zip->openFile($storedFile->getPathname());

            $manifest    = $this->inspectArchive($zip);
            $appName     = (string)($manifest['app_name'] ?? '');
            $tempAppPath = $extractDirectory . DIRECTORY_SEPARATOR . $manifest['app_name'];
            $this->extractArchive($zip, $manifest['entries'], $tempAppPath);

            $metadata = $this->readMetadataFromFilesystem($tempAppPath);
            $error    = $this->metadataValidator->validate($metadata, $tempAppPath);
            if ($error !== '') {
                throw new RuntimeException($error);
            }

            $targetPath = $this->resolveTargetPath($manifest['app_name']);
            $this->assertTargetAvailable($targetPath, $manifest['app_name']);
            $this->moveDirectory($tempAppPath, $targetPath);
            $movedToTarget = true;

            $this->appService->registerImportedPackage($manifest['app_name']);

            $this->writeImportLog(
                $appName,
                'success',
                '应用包导入成功',
                $context + [
                    'target_path' => $targetPath,
                ]
            );

            return [
                'name'  => $manifest['app_name'],
                'title' => trim((string)($metadata['title'] ?? $manifest['app_name'])),
                'path'  => $targetPath,
            ];
        } catch (Throwable $e) {
            if ($movedToTarget && $targetPath !== '') {
                $this->deleteDirectory($targetPath);
            }

            $this->writeImportLog(
                $appName,
                'failed',
                '应用包导入失败：' . $e->getMessage(),
                $context + [
                    'detected_app_name' => $appName,
                    'exception_class'   => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            );

            throw new RuntimeException('应用导入失败：' . $e->getMessage(), 0, $e);
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
            throw new RuntimeException('上传文件无效，请重新选择应用包');
        }

        $size = (int)$file->getSize();
        if ($size <= 0) {
            throw new RuntimeException('上传文件不能为空');
        }

        $maxSize = max(0, (int)config('app_package.import.max_size', 0));
        if ($maxSize > 0 && $size > $maxSize) {
            throw new RuntimeException('应用包大小超出限制');
        }

        $extension         = strtolower($file->getOriginalExtension());
        $allowedExtensions = array_map(
            static fn(mixed $item): string => strtolower(trim((string)$item)),
            (array)config('app_package.import.allowed_extensions', ['zip'])
        );
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('仅支持上传 ZIP 应用包');
        }
    }

    /**
     * 检查 ZIP 结构
     * @param ZipFile $zip
     * @return array{
     *      app_name:string,
     *      entries:array<int, array{entry:string,is_dir:bool,relative:string}>
     *  }
     * @throws ZipEntryNotFoundException
     * @throws ZipException
     */
    private function inspectArchive(ZipFile $zip): array
    {
        $allEntries = $zip->getListFiles();
        $maxEntries = max(1, (int)config('app_package.import.max_entries', 3000));
        if (count($allEntries) > $maxEntries) {
            throw new RuntimeException('应用包文件数量超出限制');
        }

        $appNames              = [];
        $metadataEntryName     = '';
        $effectiveEntries      = [];
        $totalUncompressedSize = 0;
        $maxUncompressedSize   = max(0, (int)config('app_package.import.max_uncompressed_size', 0));
        $manifestFilename      = (string)config('app_package.manifest', 'app.json');

        foreach ($allEntries as $entryName) {
            $normalizedEntry = $this->normalizeEntryName($entryName);
            if ($this->shouldIgnoreEntry($normalizedEntry)) {
                continue;
            }

            $segments = $this->parseSafeSegments($normalizedEntry);
            $entry    = $zip->getEntry($entryName);

            if ($entry->isEncrypted()) {
                throw new RuntimeException('应用包包含加密文件，无法导入');
            }

            if ($entry->isUnixSymlink()) {
                throw new RuntimeException('应用包包含符号链接，无法导入');
            }

            if ($segments[0] !== 'app') {
                throw new RuntimeException('应用包目录结构无效，必须以 app/<name>/ 为根目录');
            }

            if (count($segments) < 2) {
                if ($entry->isDirectory()) {
                    continue;
                }

                throw new RuntimeException('应用包目录结构无效，必须以 app/<name>/ 为根目录');
            }

            $appName = $segments[1];
            if (!$this->isValidAppName($appName)) {
                throw new RuntimeException('应用包目录命名不合法');
            }

            if (in_array($appName, (array)config('app_package.reserved_names', []), true)) {
                throw new RuntimeException('应用标识属于保留名称，禁止导入');
            }

            $appNames[$appName] = true;
            if (count($appNames) > 1) {
                throw new RuntimeException('一个应用包中只允许包含一个应用目录');
            }

            $relative = implode('/', array_slice($segments, 2));
            if (!$entry->isDirectory() && $relative === '') {
                throw new RuntimeException('应用包包含非法根条目');
            }

            if (!$entry->isDirectory()) {
                $entrySize = $entry->getUncompressedSize();
                if ($entrySize < 0) {
                    throw new RuntimeException('应用包包含无法识别大小的文件');
                }

                $totalUncompressedSize += $entrySize;
                if ($maxUncompressedSize > 0 && $totalUncompressedSize > $maxUncompressedSize) {
                    throw new RuntimeException('应用包解压后大小超出限制');
                }
            }

            if (!$entry->isDirectory() && $relative === $manifestFilename) {
                if ($metadataEntryName !== '' && $metadataEntryName !== $entryName) {
                    throw new RuntimeException('应用包中存在多个 app.json');
                }

                if ($entry->getUncompressedSize() > self::MAX_METADATA_SIZE) {
                    throw new RuntimeException('app.json 体积异常，无法导入');
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
            throw new RuntimeException('应用包中没有可导入的文件');
        }

        $appName = array_key_first($appNames);
        if (!is_string($appName) || $appName === '') {
            throw new RuntimeException('应用包中未找到有效的应用目录');
        }

        if ($metadataEntryName === '') {
            throw new RuntimeException('应用包缺少 app/<name>/app.json');
        }

        $metadata = $this->decodeMetadata($zip->getEntryContents($metadataEntryName));
        if (trim((string)($metadata['name'] ?? '')) !== $appName) {
            throw new RuntimeException('app.json 中的应用标识与目录不一致');
        }

        return [
            'app_name' => $appName,
            'entries'  => $effectiveEntries,
        ];
    }

    /**
     * 安全解压应用包
     * @param ZipFile $zip
     * @param array<int, array{entry:string,is_dir:bool,relative:string}> $entries
     * @param string $appPath
     * @throws ZipEntryNotFoundException
     * @throws ZipException
     */
    private function extractArchive(ZipFile $zip, array $entries, string $appPath): void
    {
        $this->ensureDirectory($appPath);

        foreach ($entries as $entry) {
            $relative = trim($entry['relative'], '/');
            if ($relative === '') {
                continue;
            }

            $destination = $appPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $this->assertPathWithinRoot($destination, $appPath);

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
                throw new RuntimeException('应用包文件写入失败');
            }

            try {
                if (!is_resource($input)) {
                    throw new RuntimeException('应用包文件读取失败');
                }

                stream_copy_to_stream($input, $output);
            } finally {
                if (is_resource($input)) {
                    fclose($input);
                }
                fclose($output);
            }
        }
    }

    /**
     * 从文件系统读取元数据
     * @param string $appPath
     * @return array<string, mixed>
     */
    private function readMetadataFromFilesystem(string $appPath): array
    {
        $manifestPath = rtrim($appPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . config('app_package.manifest', 'app.json');
        if (!is_file($manifestPath)) {
            throw new RuntimeException('应用包缺少 app.json');
        }

        $decoded = json_decode((string)file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('应用 app.json 解析失败');
        }

        return $decoded;
    }

    /**
     * 准备导入临时目录
     * @return string
     */
    private function prepareImportDirectory(): string
    {
        $directory = rtrim((string)config('app_package.import.temp_root', runtime_path() . 'apps/imports'), DIRECTORY_SEPARATOR);
        if ($directory === '') {
            throw new RuntimeException('应用导入目录配置无效');
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
        $directory = rtrim((string)config('app_package.import.temp_root', runtime_path() . 'apps/imports'), DIRECTORY_SEPARATOR);
        $ttl       = max(0, (int)config('app_package.import.keep_seconds', 3600));
        if ($directory === '' || !is_dir($directory) || $ttl <= 0) {
            return;
        }

        $expireAt = time() - $ttl;
        foreach (glob($directory . DIRECTORY_SEPARATOR . 'import-*') ?: [] as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $mtime = @filemtime($path);
            if ($mtime !== false && $mtime < $expireAt) {
                $this->deleteDirectory($path);
            }
        }
    }

    /**
     * 构建存档文件名
     * @param UploadedFile $file
     * @return string
     * @throws RandomException
     */
    private function buildStoredFilename(UploadedFile $file): string
    {
        $extension = strtolower(trim($file->getOriginalExtension()));
        $suffix    = $extension !== '' ? '.' . $extension : '.zip';

        return 'app-package-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . $suffix;
    }

    /**
     * 解析目标目录
     * @param string $appName
     * @return string
     */
    private function resolveTargetPath(string $appName): string
    {
        return rtrim((string)config('app_package.root', app()->getBasePath()), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $appName;
    }

    /**
     * 断言目标目录可用
     * @param string $targetPath
     * @param string $appName
     * @return void
     */
    private function assertTargetAvailable(string $targetPath, string $appName): void
    {
        if (in_array($appName, (array)config('app_package.reserved_names', []), true)) {
            throw new RuntimeException('应用标识属于保留名称，禁止导入');
        }

        if (is_dir($targetPath)) {
            throw new RuntimeException('应用已存在，禁止覆盖导入');
        }
    }

    /**
     * 移动目录
     * @param string $source
     * @param string $target
     * @return void
     */
    private function moveDirectory(string $source, string $target): void
    {
        $targetParent = dirname($target);
        $this->ensureDirectory($targetParent);

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
            $sourcePath = $item->getPathname();
            $relative   = substr($sourcePath, strlen($source) + 1);
            $targetPath = $target . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                $this->ensureDirectory($targetPath);
                continue;
            }

            $this->ensureDirectory(dirname($targetPath));
            if (!@copy($sourcePath, $targetPath)) {
                throw new RuntimeException('应用目录移动失败');
            }
        }
    }

    /**
     * 删除目录
     * @param string $path
     * @return void
     */
    private function deleteDirectory(string $path): void
    {
        if ($path === '' || !file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($pathname);
                continue;
            }

            @unlink($pathname);
        }

        @rmdir($path);
    }

    /**
     * 确保目录存在
     * @param string $path
     * @return void
     */
    private function ensureDirectory(string $path): void
    {
        if ($path === '') {
            throw new RuntimeException('目录路径无效');
        }

        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('目录创建失败');
        }
    }

    /**
     * 校验目标路径未越界
     * @param string $path
     * @param string $root
     * @return void
     */
    private function assertPathWithinRoot(string $path, string $root): void
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedPath = str_replace('\\', '/', $path);
        if ($normalizedPath === $normalizedRoot || str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            return;
        }

        throw new RuntimeException('应用包目标路径非法');
    }

    /**
     * 标准化归档条目名
     * @param string $entryName
     * @return string
     */
    private function normalizeEntryName(string $entryName): string
    {
        return trim(str_replace('\\', '/', $entryName), '/');
    }

    /**
     * 是否应忽略条目
     * @param string $entryName
     * @return bool
     */
    private function shouldIgnoreEntry(string $entryName): bool
    {
        return $entryName === '' || str_ends_with($entryName, '/');
    }

    /**
     * 安全解析路径段
     * @param string $entryName
     * @return array<int, string>
     */
    private function parseSafeSegments(string $entryName): array
    {
        $segments = array_values(array_filter(explode('/', $entryName), static fn(string $segment): bool => $segment !== ''));
        if ($segments === []) {
            throw new RuntimeException('应用包包含空路径条目');
        }

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || str_contains($segment, "\0")) {
                throw new RuntimeException('应用包包含非法路径条目');
            }
        }

        return $segments;
    }

    /**
     * 校验应用名
     * @param string $name
     * @return bool
     */
    private function isValidAppName(string $name): bool
    {
        return (bool)preg_match('/^[a-z][a-z0-9_-]{0,59}$/', $name);
    }

    /**
     * 解析元数据
     * @param string $contents
     * @return array<string, mixed>
     */
    private function decodeMetadata(string $contents): array
    {
        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('应用 app.json 解析失败');
        }

        return $decoded;
    }

    /**
     * 写导入日志
     * @param string $appName
     * @param string $status
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    private function writeImportLog(string $appName, string $status, string $message, array $context): void
    {
        try {
            Db::name((string)config('app_package.storage.log_table', 'admin_app_log'))->insert([
                'app_name'     => trim($appName),
                'operation'    => 'import',
                'status'       => $status,
                'message'      => $message,
                'context_json' => $this->encodeJson($context),
                'operator_id'  => 0,
                'create_time'  => time(),
            ]);
        } catch (Throwable) {
            // 导入日志写入失败不应影响主流程。
        }
    }

    /**
     * 编码 JSON
     * @param array<string, mixed> $context
     * @return string
     */
    private function encodeJson(array $context): string
    {
        try {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '{}' : $encoded;
        } catch (Throwable) {
            return '{}';
        }
    }
}
