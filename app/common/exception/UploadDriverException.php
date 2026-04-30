<?php
declare (strict_types=1);

namespace app\common\exception;

use Exception;
use Throwable;

/**
 * 上传驱动异常类
 */
class UploadDriverException extends Exception
{
    /**
     * 异常代码常量
     */
    public const DRIVER_NOT_FOUND = 1001;
    public const DRIVER_CONFIG_INVALID = 1002;
    public const DRIVER_CLASS_INVALID = 1003;
    public const DRIVER_CREATION_FAILED = 1004;
    public const PATH_SECURITY_VIOLATION = 1005;
    public const FILE_SECURITY_VIOLATION = 1006;

    /**
     * 构造函数
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        // 对外不暴露敏感信息
        $safeMessage = $this->getSafeMessage($message, $code);
        parent::__construct($safeMessage, $code, $previous);
    }

    /**
     * 获取安全的异常消息（不泄露系统信息）
     * @param string $message 原始消息
     * @param int $code 异常代码
     * @return string 安全消息
     */
    private function getSafeMessage(string $message, int $code): string
    {
        $safeMessages = [
            self::DRIVER_NOT_FOUND => '请求的上传驱动不存在',
            self::DRIVER_CONFIG_INVALID => '上传驱动配置无效',
            self::DRIVER_CLASS_INVALID => '上传驱动类无效',
            self::DRIVER_CREATION_FAILED => '上传驱动创建失败',
            self::PATH_SECURITY_VIOLATION => '路径安全检查失败',
            self::FILE_SECURITY_VIOLATION => '文件安全检查失败',
        ];

        // 在调试模式下显示详细信息，生产模式下只显示安全消息
        if (env('APP_DEBUG', false)) {
            return $safeMessages[$code] ?? $message;
        }

        return $safeMessages[$code] ?? '上传驱动系统错误';
    }
}