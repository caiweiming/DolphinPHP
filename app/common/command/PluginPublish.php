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
 * 插件静态资源发布命令
 */
class PluginPublish extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:publish')
            ->addArgument('name', Argument::REQUIRED, '插件标识，例如 demo/hello')
            ->setDescription('发布指定插件的静态资源到 public/plugins');
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
            $descriptor = app(PluginManager::class)->publishAssets($name);
            $output->writeln('<info>静态资源发布成功：</info>' . $descriptor->getName());
            return 0;
        } catch (Throwable $e) {
            $output->error('静态资源发布失败：' . $e->getMessage());
            return 1;
        }
    }
}
