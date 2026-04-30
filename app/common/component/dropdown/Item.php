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

namespace app\common\component\dropdown;

use Exception;
use think\View;
use app\common\interface\Component;

/**
 * 下拉菜单组件
 */
class Item implements Component
{
    /**
     * 组件默认参数
     * @var array{
     *     title: string,
     *     items: array<array{title: string, url: string, target: string}>,
     *     class: string
     * }
     */
    private array $params = [
        'title' => '按钮',
        'items' => [],
        'class' => '',
    ];

    /**
     * @var string|null 模板路径缓存
     */
    private static ?string $templatePath = null;

    /**
     * 构造函数
     * @param string $title 按钮标题
     * @param array $items 菜单项列表
     */
    public function __construct(string $title = '', array $items = [])
    {
        $this->title($title);
        $this->items($items);
    }

    /**
     * 渲染下拉菜单组件
     * @param array $params
     * @return string 渲染后的HTML
     * @throws Exception
     */
    public function handle(array $params = []): string
    {
        if (!self::$templatePath) {
            self::$templatePath = dp_component_path() . 'dropdown' . DIRECTORY_SEPARATOR . 'item.html';
        }

        // 合并传入的参数
        $this->params = array_merge($this->params, $params);

        /** @var View $view */
        $view = app('view', [], true);
        return $view->fetch(self::$templatePath, $this->params);
    }

    /**
     * 设置按钮标题
     * @param string $title 按钮标题
     * @return self
     */
    public function title(string $title): self
    {
        $this->params['title'] = $title ?: '按钮';
        return $this;
    }

    /**
     * 添加单个菜单项
     * @param string $title 菜单标题
     * @param string $url 链接地址
     * @param string $target 打开方式
     * @return self
     */
    public function item(string $title = '', string $url = '#', string $target = '_self'): self
    {
        $this->params['items'][] = [
            'title'  => $title,
            'url'    => $url,
            'target' => $target
        ];
        return $this;
    }

    /**
     * 批量设置菜单项
     * @param array<array{title: string, url: string, target?: string}> $items 菜单项数组
     * @return self
     */
    public function items(array $items = []): self
    {
        // 确保每个菜单项都有默认的target值
        array_walk($items, function(&$item) {
            $item['target'] = $item['target'] ?? '_self';
        });
        
        $this->params['items'] = $items;
        return $this;
    }

    /**
     * 设置组件的CSS类
     * @param string|array $class CSS类名
     * @return self
     */
    public function class(string|array $class): self
    {
        $this->params['class'] = is_array($class) ? implode(' ', $class) : $class;
        return $this;
    }
}
