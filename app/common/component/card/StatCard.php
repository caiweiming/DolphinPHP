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
 * 统计卡片组件
 * @package app\common\component\card
 */
class StatCard implements Component
{
    /**
     * 标题
     * @var string
     */
    protected string $title = '';

    /**
     * 数值
     * @var string|int
     */
    protected string|int $value = '';

    /**
     * 选项
     * @var array
     */
    protected array $options = [];

    /**
     * 构造方法
     * @param string $title 标题
     * @param string|int $value 数值
     * @param array $options 选项
     */
    public function __construct(string $title = '', string|int $value = '', array $options = [])
    {
        $this->title = $title;
        $this->value = $value;
        $this->options = $options;
    }

    /**
     * 静态创建方法
     * @param string $title 标题
     * @param string|int $value 数值
     * @param array $options 选项
     * @return static
     */
    public static function make(string $title = '', string|int $value = '', array $options = []): static
    {
        return new static($title, $value, $options);
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
     * 设置数值
     * @param string|int $value
     * @return $this
     */
    public function value(string|int $value): static
    {
        $this->value = $value;
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
     * 设置颜色
     * @param string $color
     * @return $this
     */
    public function color(string $color): static
    {
        $this->options['color'] = $color;
        return $this;
    }

    /**
     * 设置副标题
     * @param string $subtitle
     * @return $this
     */
    public function subtitle(string $subtitle): static
    {
        $this->options['subtitle'] = $subtitle;
        return $this;
    }

    /**
     * 设置趋势
     * @param string $trend
     * @return $this
     */
    public function trend(string $trend): static
    {
        $this->options['trend'] = $trend;
        return $this;
    }

    /**
     * 处理方法 - 实现Component接口
     * @return string
     */
    public function handle(): string
    {
        return $this->render();
    }

    /**
     * 渲染组件
     * @return string
     */
    public function render(): string
    {
        $icon = $this->options['icon'] ?? '';
        $color = $this->options['color'] ?? 'primary';
        $subtitle = $this->options['subtitle'] ?? '';
        $trend = $this->options['trend'] ?? '';
        
        $iconHtml = $icon ? '<div class="stat-icon text-' . $color . '"><i class="' . $icon . '"></i></div>' : '';
        $subtitleHtml = $subtitle ? '<div class="stat-subtitle text-muted">' . $subtitle . '</div>' : '';
        $trendHtml = $trend ? '<div class="stat-trend">' . $trend . '</div>' : '';
        
        return '<div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    ' . $iconHtml . '
                    <div class="flex-fill">
                        <div class="stat-title">' . $this->title . '</div>
                        <div class="stat-value h2 mb-0 text-' . $color . '">' . $this->value . '</div>
                        ' . $subtitleHtml . '
                        ' . $trendHtml . '
                    </div>
                </div>
            </div>
        </div>';
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