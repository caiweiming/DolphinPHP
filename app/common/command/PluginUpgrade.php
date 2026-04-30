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
 * 插件升级命令
 */
class PluginUpgrade extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:upgrade')
            ->addArgument('name', Argument::REQUIRED, '插件标识，例如 acme/seo')
            ->setDescription('升级指定插件到当前磁盘版本');
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
            $descriptor = app(PluginManager::class)->upgrade($name);
            $output->writeln('<info>升级成功：</info>' . $descriptor->getName() . ' -> ' . $descriptor->getVersion());
            return 0;
        } catch (Throwable $e) {
            $output->error('升级失败：' . $e->getMessage());
            return 1;
        }
    }
}
