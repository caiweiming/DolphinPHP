<?php
declare(strict_types=1);

namespace app\common\application;

use PhpZip\ZipFile;
use RuntimeException;
use Throwable;

/**
 * 应用包归档探测器
 */
class AppPackageArchiveInspector
{
    /**
     * 从 ZIP 文件读取应用包元数据
     * @param string $path
     * @return array<string, mixed>
     */
    public function inspect(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('应用包文件不存在');
        }

        $manifestName = (string)config('app_package.manifest', 'app.json');
        $zip          = new ZipFile();

        try {
            $zip->openFile($path);

            $manifestEntry = '';
            $appName       = '';
            foreach ($zip->getListFiles() as $entryName) {
                $normalized = trim(str_replace('\\', '/', $entryName), '/');
                if ($normalized === '' || str_ends_with($normalized, '/')) {
                    continue;
                }

                $segments = array_values(array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== ''));
                if (count($segments) < 3 || $segments[0] !== 'app') {
                    continue;
                }

                if ($segments[2] !== $manifestName) {
                    continue;
                }

                if ($manifestEntry !== '' && $manifestEntry !== $entryName) {
                    throw new RuntimeException('应用包中存在多个 app.json');
                }

                $manifestEntry = $entryName;
                $appName       = $segments[1];
            }

            if ($manifestEntry === '') {
                throw new RuntimeException('应用包缺少 app/<name>/app.json');
            }

            $metadata = json_decode($zip->getEntryContents($manifestEntry), true);
            if (!is_array($metadata)) {
                throw new RuntimeException('应用包 app.json 解析失败');
            }

            return [
                'app_name'         => $appName !== '' ? $appName : trim((string)($metadata['name'] ?? '')),
                'name'             => trim((string)($metadata['name'] ?? '')),
                'title'            => trim((string)($metadata['title'] ?? '')),
                'description'      => trim((string)($metadata['description'] ?? '')),
                'version'          => trim((string)($metadata['version'] ?? '')),
                'protocol_version' => trim((string)($metadata['app_api_version'] ?? '')),
                'author'           => trim((string)($metadata['author'] ?? '')),
                'manifest'         => $metadata,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException('应用包探测失败：' . $e->getMessage(), 0, $e);
        } finally {
            $zip->close();
        }
    }
}
