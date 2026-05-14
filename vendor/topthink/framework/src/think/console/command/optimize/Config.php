<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace think\console\command\optimize;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Config extends Command
{
    protected function configure()
    {
        $this->setName('optimize:config')
            ->addArgument('dir', Argument::OPTIONAL, 'dir name .')
            ->setDescription('Build config cache.');
    }

    protected function execute(Input $input, Output $output)
    {
        // 加载配置文件
        $dir     = $input->getArgument('dir') ?: '';
        $path    = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . ($dir ? $dir . DIRECTORY_SEPARATOR : '');
        if (!is_dir($path)) {
            try {
                mkdir($path, 0755, true);
            } catch (\Exception $e) {
                // 创建失败
            }
        }
        $file    = $path . 'config.php';
        $config  = $this->loadConfig($dir);
        $content = '<?php ' . PHP_EOL . 'return ' . var_export($config, true) . ';';
        if (file_put_contents($file, $content)) {
            $output->writeln("<info>Succeed!</info>");
        } else {
            $output->writeln("<error>config build fail</error>");
        }
    }

    public function loadConfig($dir = '')
    {
        $configPath = $this->app->getRootPath() . ($dir ? 'app' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '') . 'config' . DIRECTORY_SEPARATOR;
        $files      = [];

        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $this->app->getConfigExt());
        }

        foreach ($files as $file) {
            $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }

        return $this->app->config->get();
    }    
}