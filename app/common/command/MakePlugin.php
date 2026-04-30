<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 插件骨架脚手架命令
 */
class MakePlugin extends Command
{
    /**
     * 命令配置
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('make:dp-plugin')
            ->setDescription('生成标准插件骨架模板')
            ->addArgument('name', Argument::REQUIRED, '插件标识，例如 acme/seo')
            ->addOption('title', 't', Option::VALUE_OPTIONAL, '插件标题，默认根据插件名自动生成')
            ->addOption('description', 'd', Option::VALUE_OPTIONAL, '插件描述')
            ->addOption('author', 'a', Option::VALUE_OPTIONAL, '插件作者')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件')
            ->setHelp(<<<'HELP'
使用示例：
  php think make:dp-plugin acme/seo
  php think make:dp-plugin demo/hello-world --title="站点 SEO 插件" --author="DolphinPHP Team"
  php think make:dp-plugin acme/seo --force
HELP
            );
    }

    /**
     * 执行命令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $rawName = trim((string)$input->getArgument('name'));
        $force   = (bool)$input->getOption('force');

        if (!$this->isValidPluginName($rawName)) {
            $output->writeln('<error>插件标识无效，必须使用 vendor/name 形式，且仅允许小写字母、数字和中划线。</error>');
            return 1;
        }

        [$vendor, $plugin] = explode('/', $rawName, 2);
        $pluginRoot = rtrim((string)config('plugin.root', root_path() . 'plugins'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $vendor
            . DIRECTORY_SEPARATOR . $plugin;

        $title       = trim((string)$input->getOption('title'));
        $description = trim((string)$input->getOption('description'));
        $author      = trim((string)$input->getOption('author'));

        $title       = $title !== '' ? $title : $this->makeDefaultTitle($plugin);
        $description = $description !== '' ? $description : ('用于扩展 DolphinPHP 的 ' . $title . ' 插件。');
        $author      = $author !== '' ? $author : 'DolphinPHP Team';

        $targets  = $this->buildTargetFiles($pluginRoot);
        $existing = array_filter($targets, static fn(string $path): bool => is_file($path));

        if ($existing !== [] && !$force) {
            $output->writeln('<error>以下文件已存在，请使用 --force 覆盖：</error>');
            foreach ($existing as $path) {
                $output->writeln('  - ' . $path);
            }
            return 1;
        }

        $stubDir   = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR;
        $stubFiles = [
            'composer_json'    => $stubDir . 'composer.json.stub',
            'plugin_json'      => $stubDir . 'plugin.json.stub',
            'plugin_class'     => $stubDir . 'Plugin.php.stub',
            'service_provider' => $stubDir . 'ServiceProvider.php.stub',
            'routes'           => $stubDir . 'routes.php.stub',
            'permissions'      => $stubDir . 'permissions.php.stub',
            'readme'           => $stubDir . 'README.md.stub',
            'lang'             => $stubDir . 'lang.zh-cn.php.stub',
            'view'             => $stubDir . 'view.index.html.stub',
            'gitkeep'          => $stubDir . 'gitkeep.stub',
        ];

        foreach ($stubFiles as $path) {
            if (!is_file($path)) {
                $output->writeln('<error>模板文件不存在：' . $path . '</error>');
                return 1;
            }
        }

        $namespaceSegments = array_map([$this, 'studlySegment'], [$vendor, $plugin]);
        $namespace         = 'Plugins\\' . implode('\\', $namespaceSegments);
        $routePrefix       = 'plugin/' . $vendor . '/' . $plugin;
        $permissionPrefix  = dp_plugin_permission_prefix($rawName);

        $variables = [
            '{{plugin_name}}'           => $rawName,
            '{{plugin_title}}'          => $title,
            '{{plugin_description}}'    => $description,
            '{{plugin_author}}'         => $author,
            '{{plugin_package_type}}'   => (string)config('plugin.composer.package_type', 'dolphinphp-plugin'),
            '{{plugin_api_version}}'    => (string)config('plugin.api_version', '1.0'),
            '{{plugin_namespace}}'      => $namespace,
            '{{plugin_namespace_json}}' => str_replace('\\', '\\\\', $namespace),
            '{{plugin_route}}'          => $routePrefix,
            '{{plugin_vendor}}'         => $vendor,
            '{{plugin_short}}'          => $plugin,
            '{{permission_prefix}}'     => $permissionPrefix,
            '{{asset_key}}'             => '__PLUGIN_' . strtoupper(str_replace(['/', '-'], '_', $rawName)) . '__',
            '{{generated_date}}'        => date('Y-m-d'),
        ];

        foreach ($targets as $key => $target) {
            $directory = dirname($target);
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                $output->writeln('<error>无法创建目录：' . $directory . '</error>');
                return 1;
            }

            $stubKey = match ($key) {
                'composer_json' => 'composer_json',
                'plugin_json' => 'plugin_json',
                'plugin_class' => 'plugin_class',
                'service_provider' => 'service_provider',
                'routes' => 'routes',
                'permissions' => 'permissions',
                'readme' => 'readme',
                'lang' => 'lang',
                'view' => 'view',
                default => 'gitkeep',
            };

            $content = $this->renderStub($stubFiles[$stubKey], $variables);
            if (file_put_contents($target, $content) === false) {
                $output->writeln('<error>文件写入失败：' . $target . '</error>');
                return 1;
            }
        }

        $output->writeln('<info>插件骨架生成成功：</info>');
        foreach ($targets as $path) {
            $output->writeln('  - ' . $this->toRelativePath($path));
        }

        $output->writeln('');
        $output->writeln('<comment>下一步：</comment>');
        $output->writeln('  1. 修改 plugin.json 中的标题、描述、依赖和版本信息');
        $output->writeln('  2. 检查 composer.json，确认包名、类型和 autoload 与 plugin.json 对齐');
        $output->writeln('  3. 完善 README.md，说明插件用途、配置项、入口路由和使用方式');
        $output->writeln('  4. 在 src/ServiceProvider.php 中注册插件能力或 Hook');
        $output->writeln('  5. 在 permissions.php 中声明插件菜单和权限');
        $output->writeln('  6. 使用 php think plugin:install ' . $rawName . ' 安装并验证生命周期');
        $output->writeln('  7. 如需单独刷新 public 资源，可执行 php think plugin:publish ' . $rawName);

        return 0;
    }

    /**
     * 检查插件名是否合法
     * @param string $name
     * @return bool
     */
    private function isValidPluginName(string $name): bool
    {
        return (bool)preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*/[a-z0-9]+(?:-[a-z0-9]+)*$#', $name);
    }

    /**
     * 构建目标文件列表
     * @param string $pluginRoot
     * @return array<string, string>
     */
    private function buildTargetFiles(string $pluginRoot): array
    {
        return [
            'composer_json'      => $pluginRoot . DIRECTORY_SEPARATOR . 'composer.json',
            'plugin_json'        => $pluginRoot . DIRECTORY_SEPARATOR . 'plugin.json',
            'readme'             => $pluginRoot . DIRECTORY_SEPARATOR . 'README.md',
            'plugin_class'       => $pluginRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Plugin.php',
            'service_provider'   => $pluginRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ServiceProvider.php',
            'routes'             => $pluginRoot . DIRECTORY_SEPARATOR . 'routes.php',
            'permissions'        => $pluginRoot . DIRECTORY_SEPARATOR . 'permissions.php',
            'lang'               => $pluginRoot . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'zh-cn.php',
            'view'               => $pluginRoot . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'index.html',
            'config_gitkeep'     => $pluginRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '.gitkeep',
            'public_gitkeep'     => $pluginRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . '.gitkeep',
            'database_gitkeep'   => $pluginRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . '.gitkeep',
            'migrations_gitkeep' => $pluginRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '.gitkeep',
        ];
    }

    /**
     * 渲染模板
     * @param string $stubFile
     * @param array<string, string> $variables
     * @return string
     */
    private function renderStub(string $stubFile, array $variables): string
    {
        $content = (string)file_get_contents($stubFile);
        return strtr($content, $variables);
    }

    /**
     * 生成默认标题
     * @param string $plugin
     * @return string
     */
    private function makeDefaultTitle(string $plugin): string
    {
        $label = str_replace('-', ' ', $plugin);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        return ucwords(trim($label));
    }

    /**
     * 转换为 StudlyCase 片段
     * @param string $segment
     * @return string
     */
    private function studlySegment(string $segment): string
    {
        $segment = str_replace(['-', '_'], ' ', trim($segment));
        $segment = ucwords($segment);
        return str_replace(' ', '', $segment);
    }

    /**
     * 转换为相对路径
     * @param string $path
     * @return string
     */
    private function toRelativePath(string $path): string
    {
        $root = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }

        return $path;
    }
}
