<?php

namespace app\common\helper;

use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;
use Throwable;

/**
 * 轻量级日志助手类
 * 提供链式调用的日志记录接口
 *
 * @package app\common\helper
 * @author DolphinPHP
 * @version 1.0.0
 */
class Logger
{
    /**
     * 支持的日志级别
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * 常用的日志类型
     */
    public const TYPE_USER_ACTION = '用户操作';
    public const TYPE_SECURITY = '安全事件';
    public const TYPE_FILE_OPERATION = '文件操作';
    public const TYPE_SYSTEM = '系统操作';
    public const TYPE_API = 'API调用';
    public const TYPE_DATABASE = '数据库操作';
    public const TYPE_CACHE = '缓存操作';
    public const TYPE_TASK = '任务处理';
    public const TYPE_EXCEPTION = '异常事件';

    /**
     * 状态映射
     */
    private const STATUS_MAPPING = [
        self::LEVEL_DEBUG   => 0,
        self::LEVEL_INFO    => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR   => 3,
    ];

    /**
     * 日志标题
     */
    private string $title;

    /**
     * 上下文数据
     */
    private array $context = [];

    /**
     * 日志级别
     */
    private string $level = self::LEVEL_INFO;

    /**
     * 日志类型
     */
    private string $type = '';

    /**
     * 是否异步处理
     */
    private bool $async = false;

    /**
     * 静态配置缓存
     */
    private static ?array $config = null;

    /**
     * 构造函数
     *
     * @param string $title 日志标题
     */
    public function __construct(string $title = '')
    {
        $this->title = $title;
        $this->loadConfig();
    }

    /**
     * 静态创建方法
     *
     * @param string $title 日志标题
     * @return static
     */
    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    /**
     * 设置上下文数据
     *
     * @param array $data 上下文数据
     * @return self
     */
    public function context(array $data): self
    {
        $this->context = array_merge($this->context, $data);
        return $this;
    }

    /**
     * 设置日志标题
     *
     * @param string $title 标题
     * @return self
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置日志级别
     *
     * @param string $level 级别
     * @return self
     */
    public function level(string $level): self
    {
        $validLevels = self::$config['levels'] ?? [
            self::LEVEL_DEBUG,
            self::LEVEL_INFO,
            self::LEVEL_WARNING,
            self::LEVEL_ERROR
        ];

        if (in_array($level, $validLevels, true)) {
            $this->level = $level;
        }

        return $this;
    }

    /**
     * 设置日志类型
     *
     * @param string $type 类型
     * @return self
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置异步处理
     *
     * @param bool $async 是否异步
     * @return self
     */
    public function async(bool $async = true): self
    {
        $this->async = $async;
        return $this;
    }

    /**
     * 记录info级别日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function info(array $extra = []): void
    {
        $this->write(self::LEVEL_INFO, $extra);
    }

    /**
     * 记录成功日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function success(array $extra = []): void
    {
        $extra['status'] = 'success';
        $this->write(self::LEVEL_INFO, $extra);
    }

    /**
     * 记录warning级别日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function warning(array $extra = []): void
    {
        $this->write(self::LEVEL_WARNING, $extra);
    }

    /**
     * 记录error级别日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function error(array $extra = []): void
    {
        $this->write(self::LEVEL_ERROR, $extra);
    }

    /**
     * 记录debug级别日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function debug(array $extra = []): void
    {
        $this->write(self::LEVEL_DEBUG, $extra);
    }

    /**
     * 记录当前级别日志
     *
     * @param array $extra 额外数据
     * @throws Throwable
     */
    public function log(array $extra = []): void
    {
        $this->write($this->level, $extra);
    }

    /**
     * 核心写入方法
     *
     * @param string $level 日志级别
     * @param array $extra 额外数据
     * @throws Throwable
     */
    private function write(string $level, array $extra = []): void
    {
        try {
            // 检查是否启用日志
            if (!$this->shouldLog($level)) {
                return;
            }

            // 构建日志数据
            $data = $this->buildLogData($level, $extra);

            // 根据配置选择处理方式
            if ($this->shouldProcessAsync()) {
                $this->writeAsync($data);
            } else {
                $this->writeSync($data);
            }

        } catch (Throwable $e) {
            // 静默处理异常，确保不影响主业务
            if (self::$config['middleware']['exception_silence'] ?? true) {
                Log::error('日志记录失败', [
                    'error' => $e->getMessage(),
                    'title' => $this->title,
                    'level' => $level
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * 构建日志数据
     *
     * @param string $level 日志级别
     * @param array $extra 额外数据
     * @return array
     */
    private function buildLogData(string $level, array $extra = []): array
    {
        $data = [
            'title'       => $this->title ?: $this->autoGenerateTitle(),
            'level'       => $level,
            'type'        => $this->type,
            'context'     => $this->filterSensitiveData(array_merge($this->context, $extra)),
            'create_time' => time(),
        ];

        // 自动收集数据
        $autoCollectData = $this->collectAutoData();
        $data            = array_merge($data, $autoCollectData);

        // 处理上下文大小限制
        $data['context'] = $this->limitContextSize($data['context']);

        // 生成请求ID用于追踪
        $data['request_id'] = $this->generateRequestId();

        return $data;
    }

    /**
     * 收集自动数据
     *
     * @return array
     */
    private function collectAutoData(): array
    {
        $data        = [];
        $autoCollect = self::$config['auto_collect'] ?? [];

        try {
            $request = request();

            // 收集用户信息
            if ($autoCollect['user_info'] ?? true) {
                $data['user_id']  = $this->getCurrentUserId();
                $data['username'] = $this->getCurrentUsername();
            }

            // 收集请求信息
            if ($autoCollect['request_info'] ?? true) {
                $data['ip']         = $request->ip();
                $data['url']        = $request->url();
                $data['method']     = $request->method();
                $data['user_agent'] = $request->header('user-agent', '');
            }

            // 收集会话信息
            if ($autoCollect['session_info'] ?? false) {
                $data['session_id'] = session_id();
            }

            // 收集环境信息
            if ($autoCollect['environment'] ?? false) {
                $data['environment'] = app()->env;
                $data['app_debug']   = config('app.app_debug', false);
            }

        } catch (Throwable $e) {
            // 记录数据收集失败的详细错误
            if (self::$config['debug'] ?? false) {
                Log::warning('自动数据收集失败', [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine()
                ]);
            }
        }

        return $data;
    }

    /**
     * 同步写入日志
     *
     * @param array $data 日志数据
     */
    private function writeSync(array $data): void
    {
        $drivers = $this->getStorageDrivers();

        foreach ($drivers as $driver) {
            match ($driver) {
                'database' => $this->writeToDB($data),
                'file' => $this->writeToFile($data),
                default => null,
            };
        }
    }

    /**
     * 异步写入日志
     *
     * @param array $data 日志数据
     * @throws Throwable
     */
    private function writeAsync(array $data): void
    {
        try {
            $queueConfig = self::$config['storage']['drivers']['queue'] ?? [];
            $queueName   = $queueConfig['queue'] ?? 'log';
            $delay       = $queueConfig['delay'] ?? 0;

            if ($delay > 0) {
                // 延迟执行使用later方法
                Queue::later(
                    $delay,
                    'app\common\job\LogJob',
                    $data,
                    $queueName
                );
            } else {
                // 立即执行使用push方法
                Queue::push(
                    'app\common\job\LogJob',
                    $data,
                    $queueName
                );
            }
        } catch (Throwable $e) {
            // 队列失败时自动降级到同步处理
            if (self::$config['middleware']['queue_fallback'] ?? true) {
                Log::warning('异步日志处理失败，降级为同步处理', [
                    'error' => $e->getMessage(),
                    'title' => $data['title'] ?? ''
                ]);

                // 降级为同步处理
                $this->writeSync($data);
            } else {
                // 如果不允许降级，抛出异常
                throw $e;
            }
        }
    }

    /**
     * 写入数据库
     *
     * @param array $data 日志数据
     */
    private function writeToDB(array $data): void
    {
        $table = self::$config['storage']['drivers']['database']['table'] ?? 'admin_log';

        Db::name($table)->insert([
            'uid'         => $data['user_id'] ?? 0,
            'url'         => $data['url'] ?? '',
            'title'       => $data['title'],
            'type'        => $data['type'] ?? '',
            'content'     => json_encode(
                $data['context'],
                self::$config['format']['json_options'] ?? JSON_UNESCAPED_UNICODE
            ),
            'ip'          => $data['ip'] ?? '',
            'create_time' => $data['create_time'],
            'status'      => $this->getLevelStatus($data['level']),
        ]);
    }

    /**
     * 写入文件
     *
     * @param array $data 日志数据
     */
    private function writeToFile(array $data): void
    {
        $fileConfig = self::$config['storage']['drivers']['file'] ?? [];
        $logPath    = $fileConfig['path'] ?? runtime_path('logs/operation');

        // 确保目录存在
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        $filename = $logPath . '/' . date('Y-m-d') . '.log';
        $logLine  = $this->formatLogLine($data);

        file_put_contents($filename, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);

        // 检查文件轮转
        if ($fileConfig['rotate'] ?? true) {
            $this->rotateLogFile($filename, $fileConfig);
        }
    }

    /**
     * 格式化日志行
     *
     * @param array $data 日志数据
     * @return string
     */
    private function formatLogLine(array $data): string
    {
        $datetime = date(
            self::$config['format']['datetime_format'] ?? 'Y-m-d H:i:s',
            $data['create_time']
        );
        $level    = strtoupper($data['level']);
        $title    = $data['title'];
        $context  = json_encode(
            $data['context'],
            self::$config['format']['json_options'] ?? JSON_UNESCAPED_UNICODE
        );

        return "[$datetime] $level: $title $context";
    }

    /**
     * 日志文件轮转
     *
     * @param string $filename 文件名
     * @param array $config 配置
     */
    private function rotateLogFile(string $filename, array $config): void
    {
        $maxSize = $this->parseSize($config['max_size'] ?? '10MB');

        if (file_exists($filename) && filesize($filename) > $maxSize) {
            $rotateCount = $config['rotate_count'] ?? 30;

            // 删除最旧的日志
            for ($i = $rotateCount; $i > 1; $i--) {
                $oldFile = $filename . '.' . $i;
                $newFile = $filename . '.' . ($i + 1);
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }

            // 重命名当前文件
            rename($filename, $filename . '.1');
        }
    }

    /**
     * 解析文件大小字符串
     *
     * @param string $size 大小字符串
     * @return int
     */
    private function parseSize(string $size): int
    {
        $size  = strtoupper($size);
        $bytes = (int)$size;

        return match (true) {
            str_contains($size, 'K') => $bytes * 1024,
            str_contains($size, 'M') => $bytes * 1024 * 1024,
            str_contains($size, 'G') => $bytes * 1024 * 1024 * 1024,
            default => $bytes,
        };
    }

    /**
     * 获取存储驱动列表
     *
     * @return array
     */
    private function getStorageDrivers(): array
    {
        $default = self::$config['storage']['default'] ?? 'database';

        return is_string($default) ? [$default] : (array)$default;
    }

    /**
     * 判断是否应该记录日志
     *
     * @param string $level 日志级别
     * @return bool
     */
    private function shouldLog(string $level): bool
    {
        // 检查全局开关
        if (!(self::$config['enable'] ?? true)) {
            return false;
        }

        // 检查级别过滤
        $levelFilter = self::$config['level_filter'] ?? self::LEVEL_INFO;
        $levels      = self::$config['levels'] ?? [
            self::LEVEL_DEBUG,
            self::LEVEL_INFO,
            self::LEVEL_WARNING,
            self::LEVEL_ERROR
        ];

        $currentIndex = array_search($level, $levels, true);
        $filterIndex  = array_search($levelFilter, $levels, true);

        return $currentIndex !== false
            && $filterIndex !== false
            && $currentIndex >= $filterIndex;
    }

    /**
     * 判断是否应该异步处理
     *
     * @return bool
     */
    private function shouldProcessAsync(): bool
    {
        // 实例级别设置
        if ($this->async) {
            return true;
        }

        // 全局配置
        return self::$config['performance']['async'] ?? false;
    }

    /**
     * 过滤敏感数据
     *
     * @param array $data 原始数据
     * @return array
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = self::$config['filter']['sensitive_fields'] ?? [];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return $data;
    }

    /**
     * 限制上下文数据大小
     *
     * @param array $context 上下文数据
     * @return array
     */
    private function limitContextSize(array $context): array
    {
        $maxSize = self::$config['filter']['max_context_size'] ?? 2048;
        $encoded = json_encode(
            $context,
            self::$config['format']['json_options'] ?? JSON_UNESCAPED_UNICODE
        );

        if (strlen($encoded) > $maxSize) {
            // 简单截断策略
            $context['_truncated'] = true;
            $context               = array_slice($context, 0, 5, true);
        }

        return $context;
    }

    /**
     * 自动生成标题
     *
     * @return string
     */
    private function autoGenerateTitle(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && str_contains($trace['class'], 'Controller')) {
                $controller = basename($trace['class']);
                $action     = $trace['function'] ?? 'unknown';
                return str_replace('Controller', '', $controller) . '::' . $action;
            }
        }

        return '系统操作';
    }

    /**
     * 获取当前用户ID
     *
     * @return int
     */
    private function getCurrentUserId(): int
    {
        try {
            $adminUser = session(config('system.admin_session'));
            if (is_array($adminUser) && isset($adminUser['id'])) {
                return (int)$adminUser['id'];
            }
        } catch (Throwable) {
            // 静默处理
        }
        return 0;
    }

    /**
     * 获取当前用户名
     *
     * @return string
     */
    private function getCurrentUsername(): string
    {
        try {
            $adminUser = session(config('system.admin_session'));
            if (is_array($adminUser) && isset($adminUser['username'])) {
                return (string)$adminUser['username'];
            }
        } catch (Throwable) {
            // 静默处理
        }
        return '';
    }

    /**
     * 生成请求ID
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        static $requestId = null;

        return $requestId ??= uniqid('', true);
    }

    /**
     * 获取级别对应的状态值
     *
     * @param string $level 级别
     * @return int
     */
    private function getLevelStatus(string $level): int
    {
        return self::STATUS_MAPPING[$level] ?? self::STATUS_MAPPING[self::LEVEL_INFO];
    }

    /**
     * 加载配置
     */
    private function loadConfig(): void
    {
        if (self::$config !== null) {
            return;
        }

        // 加载基础配置
        self::$config = config('logging', []);

        // 确保配置是数组
        if (!is_array(self::$config)) {
            self::$config = [];
        }

        // 设置默认值
        self::$config = [
            'enable'       => true,
            'levels'       => [self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR],
            'level_filter' => self::LEVEL_INFO,
            'storage'      => ['default' => 'database'],
            'performance'  => ['async' => false],
            'filter'       => ['sensitive_fields' => []],
            'environments' => [],
            ...self::$config
        ];

        // 合并环境特定配置
        try {
            $env       = app()->env ?? 'production';
            $envConfig = self::$config['environments'][$env] ?? null;

            if (is_array($envConfig) && !empty($envConfig)) {
                self::$config = $this->mergeConfig(self::$config, $envConfig);
            }
        } catch (Throwable) {
            // 配置合并失败时使用默认配置
        }
    }

    /**
     * 安全的配置合并方法
     *
     * @param array $base 基础配置
     * @param array $override 覆盖配置
     * @return array
     */
    private function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
