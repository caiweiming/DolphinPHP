<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 表单扩展项脚手架命令
 */
class MakeDpFormItem extends Command
{
    /**
     * 命令配置
     */
    protected function configure(): void
    {
        $this->setName('make:dp-form-item')
            ->setDescription('生成 Form 自定义扩展项骨架模板')
            ->addArgument('type', Argument::REQUIRED, '表单扩展项类型名，例如 transfer 或 stats/selector')
            ->addOption('label', 'l', Option::VALUE_OPTIONAL, '表单项显示名称，默认根据类型名自动生成')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件')
            ->setHelp(<<<'HELP'
使用示例：
  php think make:dp-form-item transfer
  php think make:dp-form-item stats/selector --label="统计选择器"
  php think make:dp-form-item transfer --force
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

        $typePath       = dp_normalize_extension_path($rawType);
        $segments       = array_values(array_filter(explode('/', $typePath), static fn(string $segment): bool => $segment !== ''));
        $slug           = end($segments) ?: $typePath;
        $namespace      = implode('\\', $segments);
        $typeIdentifier = implode('-', $segments);
        $label          = trim((string)$input->getOption('label'));
        $label          = $label !== '' ? $label : $this->makeDefaultLabel($slug);

        $targets  = $this->buildTargetFiles($typePath, $slug);
        $existing = array_filter($targets, static fn(string $path): bool => is_file($path));
        if ($existing !== [] && !$force) {
            $output->writeln('<error>以下文件已存在，请使用 --force 覆盖：</error>');
            foreach ($existing as $path) {
                $output->writeln('  - ' . $path);
            }
            return 1;
        }

        $stubDir   = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'form-item' . DIRECTORY_SEPARATOR;
        $stubFiles = [
            'item'     => $stubDir . 'Item.php.stub',
            'template' => $stubDir . 'item.html.stub',
            'js'       => $stubDir . 'form-item.js.stub',
            'css'      => $stubDir . 'form-item.css.stub',
        ];

        foreach ($stubFiles as $path) {
            if (!is_file($path)) {
                $output->writeln('<error>模板文件不存在：' . $path . '</error>');
                return 1;
            }
        }

        $variables = [
            '{{type}}'            => $typePath,
            '{{dir}}'             => $typePath,
            '{{slug}}'            => $slug,
            '{{namespace}}'       => $namespace,
            '{{type_identifier}}' => $typeIdentifier,
            '{{label}}'           => $label,
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

        $output->writeln('<info>Form 扩展项模板生成成功：</info>');
        foreach ($targets as $path) {
            $output->writeln('  - ' . $this->toRelativePath($path));
        }

        $output->writeln('');
        $output->writeln('<comment>下一步：</comment>');
        $output->writeln('  1. 根据业务修改 Item.php 中的 handle()/getAssets()');
        $output->writeln('  2. 完善 ' . $this->toRelativePath($targets['template']) . ' 模板结构');
        $output->writeln('  3. 在 ' . $this->toRelativePath($targets['js']) . ' 中编写前端初始化逻辑');
        $output->writeln("  4. 在控制器中直接使用 type('$typePath') 进行调用，无需注册到 config/form.php");

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
        $root         = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $typePath);

        return [
            'item'     => $root . 'extend' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . 'Item.php',
            'template' => $root . 'extend' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . 'item.html',
            'js'       => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $slug . '.js',
            'css'      => $root . 'public' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $relativePath . DIRECTORY_SEPARATOR . $slug . '.css',
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
        $label = preg_replace('/(?<!^)([A-Z])/', ' $1', $slug) ?? $slug;
        $label = str_replace('_', ' ', $label);
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
