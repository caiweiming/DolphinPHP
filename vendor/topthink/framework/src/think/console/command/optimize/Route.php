<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
namespace think\console\command\optimize;

use DirectoryIterator;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\event\RouteLoaded;

class Route extends Command
{
    protected function configure()
    {
        $this->setName('optimize:route')
            ->addArgument('dir', Argument::OPTIONAL, 'dir name .')
            ->setDescription('Build app route cache.');
    }

    protected function execute(Input $input, Output $output)
    {
        $dir = $input->getArgument('dir') ?: '';

        $path = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . ($dir ? $dir . DIRECTORY_SEPARATOR : '');
        if (!is_dir($path)) {
            try {
                mkdir($path, 0755, true);
            } catch (\Exception $e) {
                // 创建失败
            }
        }
        file_put_contents($path . 'route.php', $this->buildRouteCache($dir));
        $output->writeln('<info>Succeed!</info>');
    }

    protected function scanRoute($path, $root, $autoGroup)
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }

            if ($fileinfo->getType() == 'file' && $fileinfo->getExtension() == 'php') {
                $groupName = str_replace('\\', '/', substr_replace($fileinfo->getPath(), '', 0, strlen($root)));
                if ($groupName) {
                    $this->app->route->group($groupName, function()  use ($fileinfo) {
                        include $fileinfo->getRealPath();
                    });
                } else {
                    include $fileinfo->getRealPath();
                }
            } elseif ($autoGroup && $fileinfo->isDir()) {
                $this->scanRoute($fileinfo->getPathname(), $root, $autoGroup);
            }
        }
    }

    protected function buildRouteCache(?string $dir = null): string
    {
        $this->app->route->clear();
        $this->app->route->lazy(false);

        // 路由检测
        $autoGroup = $this->app->route->config('route_auto_group');
        $path = $this->app->getRootPath() . ($dir ? 'app' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '') . 'route' . DIRECTORY_SEPARATOR;

        $this->scanRoute($path, $path, $autoGroup);

        //触发路由载入完成事件
        $this->app->event->trigger(RouteLoaded::class);
        $rules = $this->app->route->getName();

        return '<?php ' . PHP_EOL . 'return ' . var_export($rules, true) . ';';
    }

}
