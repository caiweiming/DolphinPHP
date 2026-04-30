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

namespace app\common\render\form\traits;

/**
 * 上传配置
 * @package app\common\render\form\traits
 */
trait FileUpload
{
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

    /**
     * 显示上传按钮
     * @param bool $upload 是否显示
     * @return $this
     */
    public function upload(bool $upload = true): static
    {
        $this->config['upload'] = $upload;
        return $this;
    }

    /**
     * 显示浏览按钮
     * @param bool $browser 是否显示
     * @return $this
     */
    public function browser(bool $browser = true): static
    {
        $this->config['browser'] = $browser;
        return $this;
    }

    /**
     * 显示删除按钮
     * @param bool $delete 是否显示
     * @return $this
     */
    public function delete(bool $delete = true): static
    {
        $this->config['delete'] = $delete;
        return $this;
    }

    /**
     * 显示清空按钮
     * @param bool $clear 是否显示
     * @return $this
     */
    public function clear(bool $clear = true): static
    {
        $this->config['clear'] = $clear;
        return $this;
    }

    /**
     * 设置多文件上传
     * @param mixed $multiple true-多选，false-单选，或者填写具体的数量
     * @return $this
     */
    public function multiple(mixed $multiple = true): static
    {
        $this->config['options']['multiple'] = $multiple;
        return $this;
    }
}
