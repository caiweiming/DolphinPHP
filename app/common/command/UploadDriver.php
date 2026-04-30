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

namespace app\common\command;

use app\common\service\UploadDriverManager;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Exception;

/**
 * 上传驱动管理命令
 */
class UploadDriver extends Command
{
    protected function configure(): void
    {
        $this->setName('upload:driver')
            ->setDescription('上传驱动管理工具')
            ->addArgument('action', Argument::REQUIRED, '操作类型: list|info|health|discover|clear-cache')
            ->addArgument('driver', Argument::OPTIONAL, '驱动名称（用于info和health操作）')
            ->addOption('format', 'f', Option::VALUE_OPTIONAL, '输出格式: table|json', 'table')
            ->setHelp('
使用示例：
  php think upload:driver list              # 列出所有可用驱动
  php think upload:driver list -v           # 详细列出所有可用驱动
  php think upload:driver info local        # 显示指定驱动详细信息
  php think upload:driver health            # 检查所有驱动健康状态
  php think upload:driver health qiniu      # 检查指定驱动健康状态
  php think upload:driver discover          # 重新发现驱动
  php think upload:driver clear-cache       # 清除驱动缓存
  
选项说明：
  -f, --format     输出格式 (table|json)
  -v, --verbose    显示详细输出信息
            ');
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = $input->getArgument('action');
        $driver = $input->getArgument('driver');
        $format = $input->getOption('format');
        $verbose = $input->getOption('verbose');

        try {
            return match ($action) {
                'list' => $this->listDrivers($output, $format, $verbose),
                'info' => $this->showDriverInfo($output, $driver, $format),
                'health' => $this->checkHealth($output, $driver, $format),
                'discover' => $this->discoverDrivers($output),
                'clear-cache' => $this->clearCache($output),
                default => $this->showError($output, "未知操作: {$action}")
            };
        } catch (Exception $e) {
            return $this->showError($output, $e->getMessage());
        }
    }

    /**
     * 列出所有可用驱动
     */
    private function listDrivers(Output $output, string $format, bool $verbose): int
    {
        $drivers = UploadDriverManager::getAvailableDrivers();

        if (empty($drivers)) {
            $output->writeln('<info>未发现任何上传驱动</info>');
            return 0;
        }

        if ($format === 'json') {
            $data = [];
            foreach ($drivers as $name => $config) {
                $data[$name] = $verbose ? $config : [
                    'name' => $config['meta']['name'] ?? $name,
                    'version' => $config['meta']['version'] ?? 'unknown',
                    'class' => $config['driver']['class'] ?? ''
                ];
            }
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $output->writeln('<info>可用的上传驱动：</info>');
            $output->writeln('');

            $rows = [];
            foreach ($drivers as $name => $config) {
                $rows[] = [
                    $name,
                    $config['meta']['name'] ?? $name,
                    $config['meta']['version'] ?? 'unknown',
                    $config['driver']['class'] ?? ''
                ];
            }

            $this->displayTable($output, ['驱动名', '显示名称', '版本', '处理类'], $rows);

            if ($verbose) {
                $output->writeln('');
                $output->writeln('<comment>使用 "upload:driver info <driver>" 查看详细信息</comment>');
            }
        }

        return 0;
    }

    /**
     * 显示驱动详细信息
     */
    private function showDriverInfo(Output $output, ?string $driver, string $format): int
    {
        if (!$driver) {
            return $this->showError($output, '请指定驱动名称');
        }

        $config = UploadDriverManager::getDriverFullConfig($driver);
        if (!$config) {
            return $this->showError($output, "驱动 '{$driver}' 不存在");
        }

        if ($format === 'json') {
            $output->writeln(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $output->writeln("<info>驱动信息: {$driver}</info>");
            $output->writeln('');

            // 基本信息
            $meta = $config['meta'] ?? [];
            $this->showSection($output, '基本信息', [
                '名称' => $meta['name'] ?? 'N/A',
                '版本' => $meta['version'] ?? 'N/A',
                '描述' => $meta['description'] ?? 'N/A',
                '作者' => $meta['author'] ?? 'N/A',
                '主页' => $meta['homepage'] ?? 'N/A'
            ]);

            // 驱动类信息
            $driverInfo = $config['driver'] ?? [];
            $this->showSection($output, '驱动类', [
                '类名' => $driverInfo['class'] ?? 'N/A',
                '驱动名' => $driverInfo['name'] ?? 'N/A'
            ]);

            // 系统要求
            if (!empty($meta['requires'])) {
                $this->showSection($output, '系统要求', $meta['requires']);
            }
        }

        return 0;
    }

    /**
     * 检查驱动健康状态
     */
    private function checkHealth(Output $output, ?string $driverName, string $format): int
    {
        if ($driverName) {
            // 检查单个驱动
            try {
                $driver = UploadDriverManager::driver($driverName);
                $result = $driver->healthCheck();

                if ($format === 'json') {
                    $output->writeln(json_encode([$driverName => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $status = $result['healthy'] ? '<info>健康</info>' : '<error>异常</error>';
                    $output->writeln("驱动 {$driverName}: {$status}");

                    if (!empty($result['errors'])) {
                        $output->writeln('<error>错误信息：</error>');
                        foreach ($result['errors'] as $error) {
                            $output->writeln("  - {$error}");
                        }
                    }

                    if (!empty($result['warnings'])) {
                        $output->writeln('<comment>警告信息：</comment>');
                        foreach ($result['warnings'] as $warning) {
                            $output->writeln("  - {$warning}");
                        }
                    }
                }
            } catch (Exception $e) {
                return $this->showError($output, "检查驱动 {$driverName} 失败: " . $e->getMessage());
            }
        } else {
            // 检查所有驱动
            $drivers = UploadDriverManager::getAvailableDrivers();
            $results = [];
            $healthyCount = 0;

            foreach ($drivers as $name => $config) {
                try {
                    $driver = UploadDriverManager::driver($name);
                    $result = $driver->healthCheck();
                    $results[$name] = $result;
                    if ($result['healthy']) {
                        $healthyCount++;
                    }
                } catch (Exception $e) {
                    $results[$name] = [
                        'healthy' => false,
                        'errors' => ['实例化失败: ' . $e->getMessage()]
                    ];
                }
            }

            if ($format === 'json') {
                $output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $output->writeln('<info>驱动健康检查结果：</info>');
                $output->writeln('');

                $rows = [];
                foreach ($results as $name => $result) {
                    $status = $result['healthy'] ? '健康' : '异常';
                    $errors = empty($result['errors']) ? '' : implode('; ', $result['errors']);
                    $rows[] = [$name, $status, $errors];
                }

                $this->displayTable($output, ['驱动名', '状态', '错误信息'], $rows);

                $totalCount = count($results);
                $output->writeln('');
                $output->writeln("总计: {$totalCount} 个驱动，{$healthyCount} 个健康，" . ($totalCount - $healthyCount) . ' 个异常');
            }
        }

        return 0;
    }

    /**
     * 重新发现驱动
     */
    private function discoverDrivers(Output $output): int
    {
        $output->writeln('<info>重新发现上传驱动...</info>');

        try {
            UploadDriverManager::clearCache(); // 清除缓存强制重新发现
            UploadDriverManager::discover();
            $discovered = UploadDriverManager::getAvailableDrivers();
            $count = count($discovered);

            $output->writeln("<info>发现 {$count} 个驱动：</info>");
            foreach ($discovered as $name => $config) {
                $displayName = $config['meta']['name'] ?? $name;
                $output->writeln("  - {$name} ({$displayName})");
            }

            $output->writeln('');
            $output->writeln('<info>驱动发现完成</info>');
        } catch (Exception $e) {
            return $this->showError($output, '驱动发现失败: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * 清除驱动缓存
     */
    private function clearCache(Output $output): int
    {
        $output->writeln('<info>清除上传驱动缓存...</info>');

        try {
            UploadDriverManager::clearCache();
            $output->writeln('<info>缓存清除完成</info>');
        } catch (Exception $e) {
            return $this->showError($output, '缓存清除失败: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * 显示段落信息
     */
    private function showSection(Output $output, string $title, array $data): void
    {
        $output->writeln("<comment>{$title}：</comment>");
        foreach ($data as $key => $value) {
            $output->writeln("  <info>{$key}</info>: {$value}");
        }
        $output->writeln('');
    }

    /**
     * 显示表格
     */
    private function displayTable(Output $output, array $headers, array $rows): void
    {
        // 计算列宽
        $columnWidths = [];
        foreach ($headers as $i => $header) {
            $columnWidths[$i] = max(mb_strlen($header), 10);
        }
        
        // 计算每列的最大宽度
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidths[$i] = max($columnWidths[$i], mb_strlen((string)$cell));
            }
        }
        
        // 输出表头
        $headerLine = '| ';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $columnWidths[$i], ' ', STR_PAD_BOTH) . ' | ';
        }
        $output->writeln($headerLine);
        
        // 输出分隔线
        $separatorLine = '+';
        foreach ($columnWidths as $width) {
            $separatorLine .= str_repeat('-', $width + 2) . '+';
        }
        $output->writeln($separatorLine);
        
        // 输出数据行
        foreach ($rows as $row) {
            $rowLine = '| ';
            foreach ($row as $i => $cell) {
                $rowLine .= str_pad((string)$cell, $columnWidths[$i], ' ', STR_PAD_RIGHT) . ' | ';
            }
            $output->writeln($rowLine);
        }
        
        // 输出底部分隔线
        $output->writeln($separatorLine);
    }

    /**
     * 显示错误信息
     */
    private function showError(Output $output, string $message): int
    {
        $output->writeln("<error>{$message}</error>");
        return 1;
    }
}
