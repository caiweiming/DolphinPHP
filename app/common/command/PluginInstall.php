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
 * 插件安装命令
 */
class PluginInstall extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:install')
            ->addArgument('name', Argument::REQUIRED, '插件标识，例如 acme/seo')
            ->setDescription('安装指定插件');
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
            $descriptor = app(PluginManager::class)->install($name);
            $output->writeln('<info>安装成功：</info>' . $descriptor->getName());
            return 0;
        } catch (Throwable $e) {
            $output->error('安装失败：' . $e->getMessage());
            return 1;
        }
    }
}
