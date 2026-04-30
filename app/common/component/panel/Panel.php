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

namespace app\common\component\panel;

use app\common\interface\Component;

/**
 * 面板组件
 * @package app\common\component\panel
 */
class Panel implements Component
{
    /**
     * 面板内容
     * @var string
     */
    protected string $content = '';

    /**
     * 选项
     * @var array
     */
    protected array $options = [];

    /**
     * 构造方法
     * @param string $content 面板内容
     * @param array $options 选项
     */
    public function __construct(string $content = '', array $options = [])
    {
        $this->content = $content;
        $this->options = $options;
    }

    /**
     * 静态创建方法
     * @param string $content 面板内容
     * @param array $options 选项
     * @return static
     */
    public static function make(string $content = '', array $options = []): static
    {
        return new static($content, $options);
    }

    /**
     * 设置内容
     * @param string $content
     * @return $this
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置样式类
     * @param string $class
     * @return $this
     */
    public function class(string $class): static
    {
        $this->options['class'] = $class;
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon
     * @return $this
     */
    public function icon(string $icon): static
    {
        $this->options['icon'] = $icon;
        return $this;
    }

    /**
     * 设置为可关闭
     * @param bool $dismissible
     * @return $this
     */
    public function dismissible(bool $dismissible = true): static
    {
        $this->options['dismissible'] = $dismissible;
        return $this;
    }

    /**
     * 设置为信息类型
     * @return $this
     */
    public function info(): static
    {
        $this->options['class'] = 'alert alert-info';
        $this->options['icon'] = 'ti ti-info-circle';
        return $this;
    }

    /**
     * 设置为成功类型
     * @return $this
     */
    public function success(): static
    {
        $this->options['class'] = 'alert alert-success';
        $this->options['icon'] = 'ti ti-check-circle';
        return $this;
    }

    /**
     * 设置为警告类型
     * @return $this
     */
    public function warning(): static
    {
        $this->options['class'] = 'alert alert-warning';
        $this->options['icon'] = 'ti ti-alert-triangle';
        return $this;
    }

    /**
     * 设置为错误类型
     * @return $this
     */
    public function danger(): static
    {
        $this->options['class'] = 'alert alert-danger';
        $this->options['icon'] = 'ti ti-alert-circle';
        return $this;
    }

    /**
     * 处理方法 - 实现Component接口
     * @param $params
     * @return string
     */
    public function handle($params = []): string
    {
        return $this->render();
    }

    /**
     * 渲染组件
     * @return string
     */
    public function render(): string
    {
        $class = $this->options['class'] ?? 'alert alert-info';
        $dismissible = $this->options['dismissible'] ?? false;
        $icon = $this->options['icon'] ?? '';
        
        $iconHtml = $icon ? '<i class="' . $icon . ' me-2"></i>' : '';
        $dismissBtn = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
        
        return '<div class="' . $class . '">' . $iconHtml . $this->content . $dismissBtn . '</div>';
    }

    /**
     * 转换为字符串
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
} 