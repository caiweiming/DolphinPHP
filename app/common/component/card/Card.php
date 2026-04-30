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

namespace app\common\component\card;

use app\common\interface\Component;

/**
 * 卡片组件
 * @package app\common\component\card
 */
class Card implements Component
{
    /**
     * 卡片标题
     * @var string
     */
    protected string $title = '';

    /**
     * 卡片内容
     * @var string
     */
    protected string $content = '';

    /**
     * 卡片选项
     * @var array
     */
    protected array $options = [];

    /**
     * 构造方法
     * @param string $title 卡片标题
     * @param string $content 卡片内容
     * @param array $options 选项
     */
    public function __construct(string $title = '', string $content = '', array $options = [])
    {
        $this->title = $title;
        $this->content = $content;
        $this->options = $options;
    }

    /**
     * 静态创建方法
     * @param string $title 卡片标题
     * @param string $content 卡片内容
     * @param array $options 选项
     * @return static
     */
    public static function make(string $title = '', string $content = '', array $options = []): static
    {
        return new static($title, $content, $options);
    }

    /**
     * 设置标题
     * @param string $title
     * @return $this
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
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
     * 设置选项
     * @param array $options
     * @return $this
     */
    public function options(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * 设置头部样式
     * @param string $class
     * @return $this
     */
    public function headerClass(string $class): static
    {
        $this->options['header_class'] = $class;
        return $this;
    }

    /**
     * 设置主体样式
     * @param string $class
     * @return $this
     */
    public function bodyClass(string $class): static
    {
        $this->options['body_class'] = $class;
        return $this;
    }

    /**
     * 设置卡片样式
     * @param string $class
     * @return $this
     */
    public function cardClass(string $class): static
    {
        $this->options['card_class'] = $class;
        return $this;
    }

    /**
     * 设置底部内容
     * @param string $footer
     * @return $this
     */
    public function footer(string $footer): static
    {
        $this->options['footer'] = $footer;
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
        $headerClass = $this->options['header_class'] ?? '';
        $bodyClass = $this->options['body_class'] ?? '';
        $cardClass = $this->options['card_class'] ?? 'card';
        $footer = $this->options['footer'] ?? '';
        
        $html = '<div class="' . $cardClass . '">';
        
        if ($this->title) {
            $html .= '<div class="card-header ' . $headerClass . '">' . $this->title . '</div>';
        }
        
        $html .= '<div class="card-body ' . $bodyClass . '">' . $this->content . '</div>';
        
        if ($footer) {
            $html .= '<div class="card-footer">' . $footer . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
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