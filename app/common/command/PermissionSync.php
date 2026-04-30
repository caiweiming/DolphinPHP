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
declare(strict_types=1);

namespace app\common\command;

use app\common\service\PermissionSyncService;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\console\Table;

/**
 * 权限同步命令
 *
 * 自动扫描控制器并生成权限数据（菜单权限 + 按钮权限）
 *
 * @package app\common\command
 */
class PermissionSync extends Command
{
    /**
     * 权限同步服务
     */
    protected PermissionSyncService $syncService;

    /**
     * 配置命令
     */
    protected function configure(): void
    {
        $this->setName('permission:sync')
            ->addArgument('app', Argument::OPTIONAL, '应用名称（admin/cms/api），留空扫描admin，多个用逗号分隔', 'admin')
            ->addOption('all', 'a', Option::VALUE_NONE, '扫描所有应用')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '预览模式（不写入数据库）')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制覆盖已有权限（默认增量更新）')
            ->addOption('path', 'p', Option::VALUE_REQUIRED, '自定义扫描路径')
            ->addOption('exclude', 'e', Option::VALUE_REQUIRED, '排除的控制器（多个用逗号分隔）')
            ->setDescription('自动扫描控制器并生成权限数据');
    }

    /**
     * 执行命令
     *
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        try {
            // 显示欢迎信息
            $this->showWelcome($output);

            // 初始化服务
            $this->syncService = new PermissionSyncService();

            // 解析参数
            $app     = $input->getArgument('app');
            $all     = $input->getOption('all');
            $dryRun  = $input->getOption('dry-run');
            $force   = $input->getOption('force');
            $path    = $input->getOption('path');
            $exclude = $input->getOption('exclude');

            // 处理应用参数
            if ($all) {
                $app = 'all';
            }

            // 构建选项
            $options = [
                'incremental'        => !$force,
                'dryRun'             => $dryRun,
                'excludeControllers' => $exclude ? explode(',', $exclude) : [],
                'customPath'         => $path ?? '',
            ];

            // 显示配置信息
            $this->showConfiguration($output, $app, $options);

            // 执行同步
            $result = $this->syncService->sync($app, $options);

            // 显示结果
            $this->showResult($output, $result, $dryRun);

            return 0;

        } catch (Exception $e) {
            $output->error('❌ 同步失败: ' . $e->getMessage());
            $output->writeln('');
            $output->writeln('<comment>详细错误:</comment>');
            $output->writeln($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * 显示欢迎信息
     *
     * @param Output $output
     */
    private function showWelcome(Output $output): void
    {
        $output->writeln('');
        $output->writeln('<fg=cyan>═══════════════════════════════════════════════════════</>');
        $output->writeln('<fg=cyan>       权限自动同步工具 - DolphinPHP Permission Sync</>');
        $output->writeln('<fg=cyan>═══════════════════════════════════════════════════════</>');
        $output->writeln('');
    }

    /**
     * 显示配置信息
     *
     * @param Output $output
     * @param string $app
     * @param array $options
     */
    private function showConfiguration(Output $output, string $app, array $options): void
    {
        $output->writeln('<info>📋 同步配置:</info>');
        $output->writeln('');

        // 应用名称
        if ($app === 'all') {
            $output->writeln('  扫描应用: <comment>所有应用</comment>');
        } elseif (str_contains($app, ',')) {
            $apps = explode(',', $app);
            $output->writeln('  扫描应用: <comment>' . implode(', ', $apps) . '</comment>');
        } else {
            $output->writeln('  扫描应用: <comment>' . $app . '</comment>');
        }

        // 扫描路径
        if ($options['customPath']) {
            $output->writeln('  扫描路径: <comment>' . $options['customPath'] . '</comment>');
        } else {
            $output->writeln('  扫描路径: <comment>app/{应用}/controller</comment>');
        }

        // 更新模式
        $mode = $options['incremental'] ? '增量更新（保留已有权限）' : '强制覆盖（删除已有权限）';
        $output->writeln('  更新模式: <comment>' . $mode . '</comment>');

        // 预览模式
        if ($options['dryRun']) {
            $output->writeln('  执行模式: <fg=yellow>预览模式（不写入数据库）</>');
        } else {
            $output->writeln('  执行模式: <fg=green>正式模式（写入数据库）</>');
        }

        // 排除控制器
        if (!empty($options['excludeControllers'])) {
            $output->writeln('  排除控制器: <comment>' . implode(', ', $options['excludeControllers']) . '</comment>');
        }

        $output->writeln('');
        $output->writeln('<info>⏳ 开始扫描...</info>');
        $output->writeln('');
    }

    /**
     * 显示结果
     *
     * @param Output $output
     * @param array $result
     * @param bool $dryRun
     */
    private function showResult(Output $output, array $result, bool $dryRun): void
    {
        $output->writeln('');
        $output->writeln('<info>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</info>');
        $output->writeln('<info>📊 同步结果' . ($dryRun ? '（预览）' : '') . ':</info>');
        $output->writeln('<info>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</info>');
        $output->writeln('');

        // 统计信息
        $added       = $result['added'] ?? 0;
        $skipped     = $result['skipped'] ?? 0;
        $failed      = $result['failed'] ?? 0;
        $menuCount   = $result['menuCount'] ?? 0;
        $buttonCount = $result['buttonCount'] ?? 0;
        $duration    = $result['duration'] ?? 0;

        // 应用信息
        if (!empty($result['apps'])) {
            $output->writeln('  <comment>扫描应用:</comment> ' . implode(', ', $result['apps']));
            $output->writeln('');
        }

        // 数量统计
        if ($added > 0) {
            $output->writeln('  <fg=green>✓ 添加:</fg=green> ' . $added . ' 个权限');
            $output->writeln('    - 菜单权限: ' . $menuCount . ' 个');
            $output->writeln('    - 按钮权限: ' . $buttonCount . ' 个');
        } else {
            $output->writeln('  <comment>⊘ 添加:</comment> 0 个权限');
        }

        if ($skipped > 0) {
            $output->writeln('  <comment>⊘ 跳过:</comment> ' . $skipped . ' 个权限（已存在）');
        }

        if ($failed > 0) {
            $output->writeln('  <fg=red>✗ 失败:</fg=red> ' . $failed . ' 个权限');
        }

        $output->writeln('');
        $output->writeln('  <comment>总耗时:</comment> ' . $duration . ' 秒');
        $output->writeln('');

        // 详细信息表格
        if (!empty($result['details'])) {
            $this->showDetails($output, $result['details']);
        }

        // 最终提示
        $output->writeln('');
        if ($dryRun) {
            $output->writeln('<fg=yellow>💡 这是预览模式，没有写入数据库</>');
            $output->writeln('<fg=yellow>   移除 --dry-run 参数以正式执行同步</>');
        } else {
            if ($failed === 0) {
                $output->writeln('<fg=green>✓ 同步成功！</>');
            } else {
                $output->writeln('<fg=yellow>⚠ 同步完成，但有 ' . $failed . ' 个权限失败</>');
            }
        }
        $output->writeln('');
    }

    /**
     * 显示详细信息表格
     *
     * @param Output $output
     * @param array $details
     */
    private function showDetails(Output $output, array $details): void
    {
        $output->writeln('<info>📝 详细信息:</info>');
        $output->writeln('');

        // 限制显示数量（防止输出过长）
        $maxShow = 20;
        $total   = count($details);

        if ($total > $maxShow) {
            $details = array_slice($details, 0, $maxShow);
            $output->writeln('  <comment>（仅显示前 ' . $maxShow . ' 条，共 ' . $total . ' 条）</comment>');
            $output->writeln('');
        }

        // 解析详细信息并构建表格数据
        $tableData = [];
        foreach ($details as $detail) {
            // 解析格式：[添加] 用户管理 (admin.user)
            if (preg_match('/\[(.+?)]\s+(.+?)\s+\((.+?)\)/', $detail, $matches)) {
                $status = $matches[1];
                $name   = $matches[2];
                $code   = $matches[3];

                // 根据状态设置颜色
                $statusText = match ($status) {
                    '添加' => '<fg=green>' . $status . '</>',
                    '跳过' => '<comment>' . $status . '</>',
                    '失败' => '<fg=red>' . $status . '</>',
                    default => $status,
                };

                $tableData[] = [$statusText, $name, $code];
            } else {
                // 如果解析失败，直接显示原始文本
                $tableData[] = ['', $detail, ''];
            }
        }

        // 渲染表格
        if (!empty($tableData)) {
            $table = new Table();
            $table->setHeader(['状态', '名称', '权限代码']);
            $table->setRows($tableData);
            $output->writeln($table->render());
        }

        $output->writeln('');
    }
}
