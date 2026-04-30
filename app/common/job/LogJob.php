<?php

namespace app\common\job;

use think\facade\Db;
use think\facade\Log;
use think\queue\Job;
use Throwable;

/**
 * 日志异步处理任务
 */
class LogJob
{
    /**
     * 执行任务
     *
     * @param Job $job 任务对象
     * @param array $data 日志数据
     * @return void
     */
    public function fire(Job $job, array $data): void
    {
        try {
            // 获取配置
            $config  = config('logging', []);
            $drivers = $this->getStorageDrivers($config);

            // 执行存储
            foreach ($drivers as $driver) {
                switch ($driver) {
                    case 'database':
                        $this->writeToDB($data, $config);
                        break;
                    case 'file':
                        $this->writeToFile($data, $config);
                        break;
                }
            }

            // 删除任务
            $job->delete();

        } catch (Throwable $e) {
            // 记录错误日志
            Log::error('异步日志处理失败', [
                'error'    => $e->getMessage(),
                'data'     => $data,
                'attempts' => $job->attempts()
            ]);

            // 重试机制
            if ($job->attempts() < 3) {
                $job->release(60); // 60秒后重试
            } else {
                $job->delete(); // 超过重试次数，删除任务
            }
        }
    }

    /**
     * 获取存储驱动列表
     *
     * @param array $config 配置
     * @return array
     */
    private function getStorageDrivers(array $config): array
    {
        $default = $config['storage']['default'] ?? 'database';

        if (is_string($default)) {
            return [$default];
        }

        return (array)$default;
    }

    /**
     * 写入数据库
     *
     * @param array $data 日志数据
     * @param array $config 配置
     * @return void
     */
    private function writeToDB(array $data, array $config): void
    {
        $table = $config['storage']['drivers']['database']['table'] ?? 'dp_admin_log';

        Db::name($table)->insert([
            'uid'         => $data['user_id'] ?? 0,
            'url'         => $data['url'] ?? '',
            'title'       => $data['title'],
            'content'     => json_encode($data['context'], $config['format']['json_options'] ?? JSON_UNESCAPED_UNICODE),
            'ip'          => $data['ip'] ?? '',
            'create_time' => $data['create_time'],
            'status'      => $this->getLevelStatus($data['level']),
        ]);
    }

    /**
     * 写入文件
     *
     * @param array $data 日志数据
     * @param array $config 配置
     * @return void
     */
    private function writeToFile(array $data, array $config): void
    {
        $fileConfig = $config['storage']['drivers']['file'] ?? [];
        $logPath    = $fileConfig['path'] ?? runtime_path('logs/operation');

        // 确保目录存在
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        $filename = $logPath . '/' . date('Y-m-d') . '.log';
        $logLine  = $this->formatLogLine($data, $config);

        file_put_contents($filename, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * 格式化日志行
     *
     * @param array $data 日志数据
     * @param array $config 配置
     * @return string
     */
    private function formatLogLine(array $data, array $config): string
    {
        $datetime = date($config['format']['datetime_format'] ?? 'Y-m-d H:i:s', $data['create_time']);
        $level    = strtoupper($data['level']);
        $title    = $data['title'];
        $context  = json_encode($data['context'], $config['format']['json_options'] ?? JSON_UNESCAPED_UNICODE);

        return "[$datetime] $level: $title $context";
    }

    /**
     * 获取级别对应的状态值
     *
     * @param string $level 级别
     * @return int
     */
    private function getLevelStatus(string $level): int
    {
        $mapping = [
            'debug'   => 0,
            'info'    => 1,
            'warning' => 2,
            'error'   => 3,
        ];

        return $mapping[$level] ?? 1;
    }
}