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

namespace app\common\render\form\items\file;

use app\common\abstract\FormType;
use app\common\render\form\traits\FileUpload;
use app\common\render\form\traits\HasReadonly;

/**
 * 文件上传组件
 */
class File extends FormType
{
    use HasReadonly;
    use FileUpload;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'file';

    /**
     * 显示下载按钮
     * @param bool $download 是否显示
     * @return $this
     */
    public function download(bool $download = true): static
    {
        $this->config['download'] = $download;
        return $this;
    }
}
