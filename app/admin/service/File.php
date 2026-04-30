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

namespace app\admin\service;

use app\admin\model\File as FileModel;
use app\common\interface\FileService as FileServiceInterface;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\file\UploadedFile;
use think\Model;

/**
 * 附件逻辑控制器
 * @package app\admin\logic
 */
class File extends Common implements FileServiceInterface
{
    /**
     * 获取文件信息
     * @param int|string $id 文件id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getFileById(int|string $id): mixed
    {
        if (empty($id)) {
            return null;
        }
        return FileModel::find($id);
    }

    /**
     * 获取文件信息
     * @param string $hash 文件sha1
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getFileByHash(string $hash): mixed
    {
        return FileModel::where('sha1', $hash)->find();
    }

    /**
     * 获取附件列表（支持类型过滤和数据权限）
     * @param string $type 类型：image/file/video/audio
     * @param array $params 参数（keyword/per_page）
     * @param int $userId 用户ID（为空时取当前登录用户）
     * @return mixed
     */
    public function getList(string $type = '', array $params = [], int $userId = 0): mixed
    {
        $types = config('upload.allowed_ext');

        $query = FileModel::where([]);

        if ($type !== '' && isset($types[$type])) {
            $query->whereIn('ext', $types[$type]);
        }

        $keyword = $params['keyword'] ?? '';
        if ($keyword !== '') {
            $query->whereLike('name', '%' . $keyword . '%');
        }

        $uid = $userId ?: dp_current_user_id();
        if (!dp_is_super_admin($uid)) {
            $query->where('uid', $uid);
        }

        $perPage = (int)($params['per_page'] ?? 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }
        if ($perPage > 50) {
            $perPage = 50;
        }

        $page = (int)($params['page'] ?? 0);
        if ($page > 0) {
            return $query->order('id', 'desc')->paginate($perPage, false, ['page' => $page]);
        }

        return $query->order('id', 'desc')->paginate($perPage);
    }

    /**
     * 保存文件信息
     * @param array $data
     * @return array
     */
    public function save(array $data = []): array
    {
        return FileModel::create($data)->toArray();
    }

    /**
     * 保存文件信息,必须传入文件对象和保存路径
     * @param UploadedFile $file
     * @param string $path
     * @param string $from
     * @return FileModel|Model
     */
    public function saveFile(UploadedFile $file, string $path = '', string $from = ''): FileModel|Model
    {
        $extension    = $from == 'cropper' ? 'png' : strtolower($file->getOriginalExtension());
        $originalName = $from == 'cropper' ? $file->sha1() . '.' . $extension : $file->getOriginalName();

        // 安全处理文件名，防止路径遍历和注入攻击
        $name = $this->sanitizeFilename($originalName);

        return FileModel::create([
            'uid'    => session(config('system.admin_session') . '.id'),
            'name'   => $name,
            'url'    => '/uploads/' . $path,
            'mime'   => $file->getMime(),
            'ext'    => $extension,
            'size'   => $file->getSize(),
            'sha1'   => $file->sha1(),
            'driver' => 'local',
        ]);
    }

    /**
     * 判断文件是否存在, 不存在返回null, 存在返回文件对象
     * @param int|string $id 文件id或者hash
     * @return FileModel|array|mixed|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function exists(int|string $id = 0): mixed
    {
        if (is_numeric($id)) {
            return FileModel::find($id);
        } else {
            return FileModel::where('sha1', $id)->find();
        }
    }

    /**
     * 判断文件大小是否超过限制
     * @param array|UploadedFile $file
     * @param string $type 文件类型（image/video/document/default）
     * @return bool
     */
    public function overSize(array|UploadedFile $file, string $type = 'default'): bool
    {
        // 从配置文件读取文件大小限制
        $size_limits = config('upload.size_limit');
        // 默认 10MB
        $size_limit = $size_limits[$type] ?? $size_limits['default'] ?? 10485760;

        $size = is_array($file) ? ($file['size'] ?? 0) : $file->getSize();
        return ($size_limit > 0 && $size > $size_limit);
    }

    /**
     * 判断文件格式是否合法
     * @param array|UploadedFile $file
     * @param string|null $filePath 文件实际路径（用于真实 MIME 检测）
     * @return bool
     */
    public function overExt(array|UploadedFile $file, string $filePath = null): bool
    {
        // 危险的文件扩展名黑名单
        $dangerous_ext = config('upload.deny_ext');

        // 从配置获取允许的扩展名
        $allowed_ext_config = config('upload.allowed_ext');
        $ext_limit          = [];
        if (is_array($allowed_ext_config)) {
            foreach ($allowed_ext_config as $exts) {
                $ext_limit = array_merge($ext_limit, $exts);
            }
        }

        // 对允许的扩展名进行安全过滤，移除任何危险扩展名
        $ext_limit = $this->filterSafeExtensions($ext_limit, $dangerous_ext);

        // 获取文件信息
        if (is_array($file)) {
            $filename  = $file['url'] ?? $file['name'] ?? '';
            $file_mime = $file['mime'] ?? '';
        } else {
            $filename  = $file->getOriginalName();
            $file_mime = $file->getMime();
        }

        // 检查所有扩展名（防止双扩展名绕过）
        $all_extensions = $this->getAllExtensions($filename);
        foreach ($all_extensions as $ext) {
            // 如果任何一个扩展名在危险列表中，拒绝
            if (in_array($ext, $dangerous_ext)) {
                return true;
            }
        }

        // 获取最后一个扩展名
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 如果提供了文件路径，进行真实 MIME 类型检测
        if ($filePath && file_exists($filePath)) {
            $real_mime = $this->getRealMimeType($filePath);
            // 检查真实 MIME 是否在黑名单中
            if (in_array($real_mime, config('upload.deny_mime'))) {
                return true;
            }

            // 检查文件内容是否包含 PHP 代码
            if ($this->containsPhpCode($filePath)) {
                return true;
            }

            // 对图片文件进行额外验证
            if (in_array($file_ext, config('upload.allowed_ext.image'))) {
                if (!$this->isValidImage($filePath)) {
                    return true;
                }
            }
        }

        return (
            // 检查MIME类型是否在黑名单中
            in_array($file_mime, config('upload.deny_mime')) ||
            // 检查扩展名是否在危险扩展名黑名单中
            in_array($file_ext, $dangerous_ext) ||
            // 检查扩展名是否在允许的白名单中
            !in_array($file_ext, $ext_limit)
        );
    }

    /**
     * 过滤安全的扩展名，移除危险扩展名
     * @param array $extensions 原始扩展名列表
     * @param array $dangerous_ext 危险扩展名黑名单
     * @return array
     */
    private function filterSafeExtensions(array $extensions, array $dangerous_ext): array
    {
        // 将所有扩展名转为小写
        $extensions    = array_map('strtolower', $extensions);
        $dangerous_ext = array_map('strtolower', $dangerous_ext);

        // 移除危险扩展名
        $safe_extensions = array_diff($extensions, $dangerous_ext);

        return array_values($safe_extensions);
    }

    /**
     * 获取文件名中的所有扩展名（防止双扩展名绕过）
     * @param string $filename 文件名
     * @return array 所有扩展名数组
     */
    private function getAllExtensions(string $filename): array
    {
        $parts = explode('.', $filename);
        if (count($parts) <= 1) {
            return [];
        }
        // 移除文件名部分，只保留扩展名
        array_shift($parts);
        return array_map('strtolower', $parts);
    }

    /**
     * 获取文件的真实 MIME 类型
     * @param string $filePath 文件路径
     * @return string MIME 类型
     */
    private function getRealMimeType(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mime ?: '';
    }

    /**
     * 检查文件内容是否包含 PHP 代码
     * @param string $filePath 文件路径
     * @return bool 包含返回 true
     */
    private function containsPhpCode(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // 读取文件前 8KB 内容进行检测
        $content = file_get_contents($filePath, false, null, 0, 8192);
        if ($content === false) {
            return false;
        }

        // 检查 PHP 标签和常见的 PHP 代码特征
        $patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<\?[^x]/i',  // 匹配 <? 但不匹配 <?xml
            '/\?>/i',
            '/<script[^>]*language[^>]*php/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证是否为真实的图片文件
     * @param string $filePath 文件路径
     * @return bool 是真实图片返回 true
     */
    private function isValidImage(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // 使用 getimagesize 验证是否为真实图片
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }

        // 检查图片 EXIF 数据中是否包含脚本
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($filePath);
                if ($exif && is_array($exif)) {
                    $exifStr = json_encode($exif);
                    if (preg_match('/<\?php|<script|eval\(|base64_decode/i', $exifStr)) {
                        return false;
                    }
                }
            } catch (Exception) {
                // EXIF 读取失败，继续验证
            }
        }

        return true;
    }

    /**
     * 安全处理文件名，防止路径遍历和注入攻击
     * @param string $filename 原始文件名
     * @return string 安全的文件名
     */
    private function sanitizeFilename(string $filename): string
    {
        // 移除路径分隔符和空字节
        $filename = str_replace(['/', '\\', "\0", '%00'], '', $filename);

        // 移除特殊字符，只保留字母、数字、下划线、连字符和点
        $filename = preg_replace('/[^\p{L}\p{N}_\-.]/u', '_', $filename);

        // 限制文件名长度（保留扩展名）
        $maxLength = 200;
        if (mb_strlen($filename) > $maxLength) {
            $ext      = pathinfo($filename, PATHINFO_EXTENSION);
            $name     = pathinfo($filename, PATHINFO_FILENAME);
            $name     = mb_substr($name, 0, $maxLength - mb_strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }

        // 移除文件名开头和结尾的点和空格
        $filename = trim($filename, '. ');

        // 如果文件名为空，生成一个随机文件名
        if (empty($filename)) {
            $filename = 'file_' . uniqid();
        }

        return $filename;
    }

    /**
     * 获取文件信息
     * @param int $id 文件ID
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getFileInfo(int $id): array
    {
        return FileModel::find($id)->toArray();
    }

    /**
     * 删除文件
     * @param int $id 文件ID
     * @return bool
     */
    public function deleteFile(int $id): bool
    {
        return FileModel::destroy($id);
    }
}
