<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\command;

use app\common\service\IconLibraryService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Exception;

/**
 * 图标库配置生成命令
 */
class IconLib extends Command
{
    /**
     * 命令配置
     */
    protected function configure(): void
    {
        $this->setName('icon:lib')
            ->setDescription('根据图标 CSS 生成图标库配置文件')
            ->addArgument('css', Argument::REQUIRED, 'CSS 文件路径（相对项目根目录、绝对路径或 URL）')
            ->addOption('id', null, Option::VALUE_REQUIRED, '图标库 ID（必填）')
            ->addOption('label', null, Option::VALUE_OPTIONAL, '图标库名称（默认同 ID）')
            ->addOption('output', 'o', Option::VALUE_OPTIONAL, '输出文件路径（默认 extend/icon/{id}.php）')
            ->addOption('prefix', 'p', Option::VALUE_OPTIONAL, '过滤前缀（如 ti- 或 fa-）')
            ->addOption('base', 'b', Option::VALUE_OPTIONAL, '基础类名（如 ti 或 fa）')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件')
            ->setHelp(<<<'HELP'
使用示例：
  php think icon:lib public/static/libs/tabler-icons/tabler-icons.css --id=tabler --label="Tabler Icons" --prefix=ti- --base=ti
  php think icon:lib public/static/libs/fontawesome/css/fontawesome.css --id=fa --label="Font Awesome" --prefix=fa- --base=fa
  php think icon:lib /abs/path/icons.css --id=custom --output=extend/icon-libs/custom.php
HELP
            );
    }

    protected function execute(Input $input, Output $output): int
    {
        $css        = (string)$input->getArgument('css');
        $id         = (string)$input->getOption('id');
        $label      = (string)$input->getOption('label');
        $outputPath = (string)$input->getOption('output');
        $prefix     = (string)$input->getOption('prefix');
        $base       = (string)$input->getOption('base');
        $force      = (bool)$input->getOption('force');
        $service    = app(IconLibraryService::class);

        if ($id === '') {
            $output->writeln('<error>必须指定 --id</error>');
            return 1;
        }

        $root = rtrim(root_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($outputPath === '') {
            $outputPath = $root . 'extend/icon/' . $id . '.php';
        } else {
            $outputPath = $this->resolvePath($outputPath, $root) ?: $outputPath;
        }

        if (is_file($outputPath) && !$force) {
            $output->writeln('<error>输出文件已存在，请使用 -f 覆盖：' . $outputPath . '</error>');
            return 1;
        }

        try {
            $definition = $service->buildLibraryDefinition($css, $id, $label, $prefix, $base);
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        $content = $this->buildPhpContent(
            $definition['id'],
            $definition['label'],
            $definition['html']
        );

        $dir = dirname($outputPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $output->writeln('<error>无法创建目录：' . $dir . '</error>');
            return 1;
        }

        if (file_put_contents($outputPath, $content) === false) {
            $output->writeln('<error>写入失败：' . $outputPath . '</error>');
            return 1;
        }

        $output->writeln('<info>生成成功：</info>' . $outputPath);
        $output->writeln('<comment>图标数量：</comment>' . $definition['icon_count']);
        return 0;
    }

    /**
     * 生成配置文件内容
     * @param string $id
     * @param string $label
     * @param string $html
     * @return string
     */
    private function buildPhpContent(string $id, string $label, string $html): string
    {
        $label = str_replace(["\\r\\n", "\\n", "\\r"], ' ', $label);
        $label = str_replace(["\r\n", "\n", "\r"], ' ', $label);
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;
        $label = trim($label);

        return "<?php\n" .
            "// +----------------------------------------------------------------------\n" .
            "// | 海豚PHP框架 [ DolphinPHP ]\n" .
            "// +----------------------------------------------------------------------\n" .
            "// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]\n" .
            "// +----------------------------------------------------------------------\n" .
            "// | 官方网站: http://www.dolphinphp.com\n" .
            "// +----------------------------------------------------------------------\n" .
            "// | 作者: 蔡伟明 <314013107@qq.com>\n" .
            "// +----------------------------------------------------------------------\n" .
            "return [\n" .
            "    'id' => '" . addslashes($id) . "',\n" .
            "    'label' => '" . addslashes($label) . "',\n" .
            "    'html' => <<<'HTML'\n" .
            $html . "\nHTML,\n" .
            "];\n";
    }

    /**
     * 解析路径
     * @param string $path
     * @param string $root
     * @return string|null
     */
    private function resolvePath(string $path, string $root): ?string
    {
        if ($path === '') {
            return null;
        }
        if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = $root . ltrim($path, DIRECTORY_SEPARATOR);
        }
        return $path;
    }
}
