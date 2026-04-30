<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 图表地图专项扩展脚手架命令
 */
class MakeDpChartMap extends Command
{
    /**
     * 命令配置
     */
    protected function configure(): void
    {
        $this->setName('make:dp-chart-map')
            ->setDescription('生成 Chart 地图专项扩展模板')
            ->addArgument('map', Argument::REQUIRED, '地图扩展名，例如 china 或 stats/china')
            ->addOption('label', 'l', Option::VALUE_OPTIONAL, '地图扩展显示名称，默认根据扩展名自动生成')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件')
            ->setHelp(<<<'HELP'
使用示例：
  php think make:dp-chart-map bubble
  php think make:dp-chart-map region/china --label="中国区域地图"
  php think make:dp-chart-map bubble --force
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
        $rawMap = trim((string)$input->getArgument('map'));
        $force  = (bool)$input->getOption('force');

        if (!$this->isValidMapKey($rawMap)) {
            $output->writeln('<error>地图扩展名无效。仅允许字母、数字、下划线，支持使用 . / \\ 作为分段。</error>');
            return 1;
        }

        $mapPath   = dp_normalize_extension_path($rawMap);
        $segments  = array_values(array_filter(explode('/', $mapPath), static fn(string $segment): bool => $segment !== ''));
        $slug      = end($segments) ?: $mapPath;
        $namespace = implode('\\', $segments);
        $label     = trim((string)$input->getOption('label'));
        $label     = $label !== '' ? $label : $this->makeDefaultLabel($slug);
        $targets   = $this->buildTargetFiles($mapPath, $slug);
        $existing  = array_filter($targets, static fn(string $path): bool => is_file($path));

        if ($existing !== [] && !$force) {
            $output->writeln('<error>以下文件已存在，请使用 --force 覆盖：</error>');
            foreach ($existing as $path) {
                $output->writeln('  - ' . $path);
            }
            return 1;
        }

        $stubDir   = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'chart_map_extension' . DIRECTORY_SEPARATOR;
        $stubFiles = [
            'provider' => $stubDir . 'Provider.php.stub',
            'js'       => $stubDir . 'chart_map.js.stub',
            'css'      => $stubDir . 'chart_map.css.stub',
            'geojson'  => $stubDir . 'chart_map.geo.json.stub',
        ];

        foreach ($stubFiles as $path) {
            if (!is_file($path)) {
                $output->writeln('<error>模板文件不存在：' . $path . '</error>');
                return 1;
            }
        }

        $variables = [
            '{{map}}'       => $mapPath,
            '{{dir}}'       => $mapPath,
            '{{slug}}'      => $slug,
            '{{namespace}}' => $namespace,
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

        $output->writeln('<info>Chart 地图专项扩展模板生成成功：</info>');
        foreach ($targets as $path) {
            $output->writeln('  - ' . $this->toRelativePath($path));
        }

        $output->writeln('');
        $output->writeln('<comment>下一步：</comment>');
        $output->writeln('  1. 替换 ' . $this->toRelativePath($targets['geojson']) . ' 为真实 GeoJSON');
        $output->writeln('  2. 根据业务修改 Provider.php 中的 definition()/normalize()');
        $output->writeln('  3. 在 ' . $this->toRelativePath($targets['js']) . ' 中补充地图 hook');
        $output->writeln("  4. 在控制器中使用 map('$mapPath') 进行调用");

        return 0;
    }

    /**
     * 检查地图扩展名是否合法
     * @param string $map
     * @return bool
     */
    private function isValidMapKey(string $map): bool
    {
        return (bool)preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:[.\/\\\\][A-Za-z][A-Za-z0-9_]*)*$/', $map);
    }

    /**
     * 构建目标文件列表
     * @param string $mapPath
     * @param string $slug
     * @return array<string, string>
     */
    private function buildTargetFiles(string $mapPath, string $slug): array
    {
        $root         = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $mapPath);

        return [
            'provider' => $root . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . 'Provider.php',
            'js'       => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $slug . '.js',
            'css'      => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $slug . '.css',
            'geojson'  => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $slug . '.geo.json',
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
