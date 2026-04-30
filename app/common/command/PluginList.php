<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\plugin\PluginManager;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 插件列表命令
 */
class PluginList extends Command
{
    /**
     * 配置命令
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('plugin:list')
            ->setDescription('查看当前可发现的插件列表');
    }

    /**
     * 执行命令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $manager = app(PluginManager::class);
        $rows    = $manager->getDisplayRows();

        $output->writeln('');
        $output->writeln('<info>插件列表</info>');
        $output->writeln('');

        if ($rows === []) {
            $output->writeln('<comment>当前未发现任何插件。</comment>');
            return 0;
        }

        $table = new \think\console\Table();
        $table->setHeader(['标识', '标题', '版本', '状态', '已安装', '已启用']);
        foreach ($rows as $row) {
            $table->addRow([
                $row['name'] ?? '',
                $row['title'] ?? '',
                $row['version'] ?? '',
                $row['status_text'] ?? '',
                $row['installed_text'] ?? '否',
                $row['enabled_text'] ?? '否',
            ]);
        }

        $output->writeln($table->render());
        if (!$manager->storageReady()) {
            $output->writeln('');
            $output->writeln('<comment>提示：插件数据表尚未创建，当前仅显示磁盘扫描结果。</comment>');
        }

        return 0;
    }
}
