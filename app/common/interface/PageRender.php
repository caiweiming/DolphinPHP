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
declare(strict_types=1);

namespace app\common\interface;

/**
 * 页面渲染器接口
 */
interface PageRender extends ZRender
{
    /**
     * 初始化页面
     * @param string $id 页面标识
     * @return static
     */
    public function init(string $id): static;

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array $vars 模板变量
     * @return string
     */
    public function display(string $content, array $vars = []): string;

    /**
     * 解析和获取模板内容
     * @param string $template 模板文件名或内容
     * @param array $vars 模板变量
     * @return string
     */
    public function fetch(string $template = '', array $vars = []): string;

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return static
     */
    public function assign(string|array $name, mixed $value = null): static;

    /**
     * 添加行内容
     * @param mixed $content 行内容
     * @return static
     */
    public function row(mixed $content): static;

    /**
     * 渲染页面级标签容器
     * @param array $tabs 标签配置
     * @param array $options 容器配置
     * @return static
     */
    public function tabs(array $tabs, array $options = []): static;
} 
