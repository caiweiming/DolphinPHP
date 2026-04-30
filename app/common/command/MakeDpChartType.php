<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 图表扩展类型脚手架命令
 */
class MakeDpChartType extends Command
{
    /**
     * 命令配置
     */
    protected function configure(): void
    {
        $this->setName('make:dp-chart-type')
            ->setDescription('生成 Chart 自定义图表类型扩展模板')
            ->addArgument('type', Argument::REQUIRED, '图表类型名，例如 bubble 或 stats/bubble')
            ->addOption('label', 'l', Option::VALUE_OPTIONAL, '图表显示名称，默认根据类型名自动生成')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件')
            ->setHelp(<<<'HELP'
使用示例：
  php think make:dp-chart-type bubble
  php think make:dp-chart-type stats/bubble --label="统计气泡图"
  php think make:dp-chart-type bubble --force
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
        $rawType = trim((string)$input->getArgument('type'));
        $force   = (bool)$input->getOption('force');

        if (!$this->isValidType($rawType)) {
            $output->writeln('<error>类型名无效。仅允许字母、数字、下划线，支持使用 . / \\ 作为分段。</error>');
            return 1;
        }

        $typePath  = dp_normalize_extension_path($rawType);
        $segments  = array_values(array_filter(explode('/', $typePath), static fn(string $segment): bool => $segment !== ''));
        $slug      = end($segments) ?: $typePath;
        $namespace = implode('\\', $segments);
        $label     = trim((string)$input->getOption('label'));
        $label     = $label !== '' ? $label : $this->makeDefaultLabel($slug);

        $targets  = $this->buildTargetFiles($typePath, $slug);
        $existing = array_filter($targets, static fn(string $path): bool => is_file($path));
        if ($existing !== [] && !$force) {
            $output->writeln('<error>以下文件已存在，请使用 --force 覆盖：</error>');
            foreach ($existing as $path) {
                $output->writeln('  - ' . $path);
            }
            return 1;
        }

        $stubDir   = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'chart-extension' . DIRECTORY_SEPARATOR;
        $stubFiles = [
            'type' => $stubDir . 'Type.php.stub',
            'js'   => $stubDir . 'chart-type.js.stub',
            'css'  => $stubDir . 'chart-type.css.stub',
        ];

        foreach ($stubFiles as $path) {
            if (!is_file($path)) {
                $output->writeln('<error>模板文件不存在：' . $path . '</error>');
                return 1;
            }
        }

        $variables = [
            '{{type}}'      => $typePath,
            '{{namespace}}' => $namespace,
            '{{dir}}'       => $typePath,
            '{{slug}}'      => $slug,
            '{{label}}'     => $label,
        ];

        foreach ($targets as $key => $target) {
            $directory = dirname($target);
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                $output->writeln('<error>无法创建目录：' . $directory . '</error>');
                return 1;
            }

            $content = $this->renderStub($stubFiles[$key], $variables);
            if (file_put_contents($target, $content) === false) {
                $output->writeln('<error>文件写入失败：' . $target . '</error>');
                return 1;
            }
        }

        $output->writeln('<info>Chart 扩展类型模板生成成功：</info>');
        foreach ($targets as $path) {
            $output->writeln('  - ' . $this->toRelativePath($path));
        }

        $output->writeln('');
        $output->writeln('<comment>下一步：</comment>');
        $output->writeln('  1. 根据业务修改 Type.php 中的 build()/assets()/payload()');
        $output->writeln('  2. 在 ' . $this->toRelativePath($targets['js']) . ' 中注册前端 hook');
        $output->writeln('  3. 在控制器中使用 type(\'' . $typePath . '\') 进行调用');

        return 0;
    }

    /**
     * 检查类型名是否合法
     * @param string $type
     * @return bool
     */
    private function isValidType(string $type): bool
    {
        return (bool)preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:[.\/\\\\][A-Za-z][A-Za-z0-9_]*)*$/', $type);
    }

    /**
     * 构建目标文件列表
     * @param string $typePath
     * @param string $slug
     * @return array<string, string>
     */
    private function buildTargetFiles(string $typePath, string $slug): array
    {
        $root = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return [
            'type' => $root . 'extend' . DIRECTORY_SEPARATOR . 'chart' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $typePath) . DIRECTORY_SEPARATOR . 'Type.php',
            'js'   => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'chart' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $typePath) . DIRECTORY_SEPARATOR . $slug . '.js',
            'css'  => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'chart' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $typePath) . DIRECTORY_SEPARATOR . $slug . '.css',
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
     * 生成默认标签
     * @param string $slug
     * @return string
     */
    private function makeDefaultLabel(string $slug): string
    {
        $label = str_replace('_', ' ', $slug);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        return ucwords(trim($label));
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
