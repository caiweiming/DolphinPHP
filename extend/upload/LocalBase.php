<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace upload;

use app\common\interface\UploadDriver;

/**
 * 本地上传驱动抽象基类
 */
abstract class LocalBase extends Common implements UploadDriver
{
    /**
     * 获取驱动名称
     * @return string
     */
    protected function getDriverName(): string
    {
        return 'local';
    }

    /**
     * 获取默认配置
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            // 本地驱动通常不需要额外配置
            'upload_path' => 'uploads'
        ];
    }

    /**
     * 本地存储通用的处理方法
     * @param array $item
     * @return array
     */
    public function handle(array $item = []): array
    {
        return $this->customizeHandle($item);
    }

    /**
     * 自定义处理方法（子类可重写）
     * @param array $item
     * @return array
     */
    protected function customizeHandle(array $item): array
    {
        return $item;
    }

    /**
     * 驱动配置, 供前端js使用
     * @return array
     */
    public function config(): array
    {
        return [
            'url' => dp_url('admin/api/upload')->build()
        ];
    }

    /**
     * 本地存储特定的配置验证
     * @param array $errors
     * @param array $warnings
     */
    protected function validateDriverSpecificConfig(array &$errors, array &$warnings): void
    {
        $uploadPath = public_path() . 'uploads';

        // 检查上传目录是否存在
        if (!is_dir($uploadPath)) {
            $errors[] = "上传目录不存在: $uploadPath";
        } elseif (!is_writable($uploadPath)) {
            $errors[] = "上传目录不可写: $uploadPath";
        }

        // 检查磁盘空间
        $freeSpace = disk_free_space($uploadPath);
        if ($freeSpace !== false && $freeSpace < 100 * 1024 * 1024) { // 小于100MB
            $warnings[] = "磁盘空间不足: " . round($freeSpace / 1024 / 1024, 2) . "MB";
        }
    }
}