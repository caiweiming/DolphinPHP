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

namespace app\common\render\form\items\icon;

use app\common\abstract\FormType;
use app\common\render\form\traits\Placeholder;

/**
 * 图标选择器组件
 */
class Icon extends FormType
{
    use Placeholder;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'icon';

    /**
     * 设置图标库配置文件
     * @param string|array $files 文件路径（支持相对项目根目录）
     * @return $this
     */
    public function libsFiles(string|array $files): static
    {
        $this->config['libs_files'] = $files;
        return $this;
    }

    /**
     * 设置图标库配置文件（单个）
     * @param string $file 文件路径（支持相对项目根目录）
     * @return $this
     */
    public function libsFile(string $file): static
    {
        $this->config['libs_files'] = $file;
        return $this;
    }
}
