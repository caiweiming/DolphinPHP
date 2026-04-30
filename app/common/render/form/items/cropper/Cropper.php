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

namespace app\common\render\form\items\cropper;

use app\common\abstract\FormType;

/**
 * 图片裁剪器组件
 */
class Cropper extends FormType
{
    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'cropper';

    /**
     * 设置按钮组
     * @param mixed $buttons 按钮列表，可选值：false(不显示所有按钮)、upload(上传图片)、url(网络图片)、select(在线图片)、delete(删除图片)
     * @return $this
     */
    public function buttons(mixed $buttons = ['upload', 'url', 'select', 'delete']): static
    {
        $this->config['buttons'] = $buttons;
        return $this;
    }

    /**
     * 设置裁剪操作按钮
     * @param array|bool $actions 操作按钮列表，可选值：zoom-in(放大)、zoom-out(缩小)、rotate-left(逆时针旋转)、rotate-right(顺时针旋转)、scale-x(水平翻转)、scale-y(垂直翻转)
     * @return $this
     */
    public function actions(array|bool $actions = ['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right', 'scale-x', 'scale-y']): static
    {
        $this->config['actions'] = $actions;
        return $this;
    }

    /**
     * 设置上传驱动
     * @param string $driver 上传驱动，可选值：local(本地)、oss(阿里云OSS)、qiniu(七牛云)
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
