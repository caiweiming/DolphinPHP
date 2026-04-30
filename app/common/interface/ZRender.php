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

namespace app\common\interface;

/**
 * ZRender接口
 * @package app\common\interface
 */
interface ZRender
{
    /**
     * 初始化渲染器
     * @param string $id 渲染器标识
     * @return static
     */
    public function init(string $id): static;

    /**
     * 获取渲染器实例
     * @param string $id 渲染器标识
     * @return static
     */
    public static function make(string $id): static;

    /**
     * 模板变量赋值
     * @param string $name 模板变量
     * @param mixed|null $value 变量值
     * @return ZRender
     */
    public function assign(string $name, mixed $value = null): ZRender;

    /**
     * 渲染模板文件
     * @param string $template 模板文件
     * @param array $vars 模板变量
     * @return string
     */
    public function fetch(string $template = '', array $vars = []): string;

    /**
     * 设置静态资源，css或js文件
     * @return array
     */
    public function assets(): array;
}
