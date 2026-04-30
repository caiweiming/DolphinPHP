<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello\Command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 示例插件命令
 */
class HelloCommand extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('demo:hello')
            ->setDescription('运行示例插件的最小命令');
    }

    /**
     * 执行命令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('demo/hello plugin command is ready');
        return 0;
    }
}
