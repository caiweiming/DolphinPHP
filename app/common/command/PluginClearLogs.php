<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\plugin\PluginManager;
use Throwable;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * 插件日志清理命令
 */
class PluginClearLogs extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:clear-logs')
            ->addArgument('name', Argument::REQUIRED, '插件标识，例如 demo/hello')
            ->setDescription('清理指定插件的操作日志');
    }

    /**
     * 执行命令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $name = trim((string)$input->getArgument('name'));

        try {
            $deleted = app(PluginManager::class)->clearLogs($name);
            $output->writeln('<info>插件日志清理成功：</info>' . $name . '，共清理 ' . $deleted . ' 条记录');
            return 0;
        } catch (Throwable $e) {
            $output->error('插件日志清理失败：' . $e->getMessage());
            return 1;
        }
    }
}
