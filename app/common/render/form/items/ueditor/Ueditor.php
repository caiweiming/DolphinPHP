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

namespace app\common\render\form\items\ueditor;

use app\common\abstract\FormType;

/**
 * ueditor编辑器组件
 */
class Ueditor extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'ueditor';

    /**
     * 设置上传驱动
     * @param string $driver 驱动名称（local/aliyun/qiniu）
     * @return $this
     */
    public function driver(string $driver = 'local'): static
    {
        $this->config['driver'] = $driver;
        return $this;
    }

    /**
     * 设置上传目录
     * @param string $dir 上传目录路径
     * @return $this
     */
    public function dir(string $dir = ''): static
    {
        $this->config['dir'] = $dir;
        return $this;
    }
}
