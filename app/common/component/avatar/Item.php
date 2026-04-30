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

namespace app\common\component\avatar;

use Exception;
use think\View;
use app\common\interface\Component;

/**
 * 头像组件
 */
class Item implements Component
{
    /**
     * 组件默认参数
     * @var array|string[]
     */
    private array $params = [
        'img_src' => '',
        'name'    => 'DolphinPHP',
        'desc'    => '',
        'size'    => 'xs',
        'class'   => '',
        'style'   => '',
        'avatar'  => '',
    ];

    /**
     * @var string|null 模板路径缓存
     */
    private static ?string $templatePath = null;

    /**
     * 构造函数
     * @param string $name 支付类型
     * @param string $desc 标签内容
     * @param string $img 图片地址
     * @param string $size 尺寸：xs、sm、md、lg、xl
     * @param string|array $class CSS类名
     */
    public function __construct(string $name = '', string $desc = '', string $img = '', string $size = '', string|array $class = '')
    {
        $this->name($name)
            ->desc($desc)
            ->img($img)
            ->size($size)
            ->class($class);
    }

    /**
     * 渲染组件
     * @param array $params
     * @return string 渲染后的HTML
     * @throws Exception
     */
    public function handle(array $params = []): string
    {
        if (!self::$templatePath) {
            self::$templatePath = dp_component_path() . 'avatar' . DIRECTORY_SEPARATOR . 'item.html';
        }

        // 合并传入的参数
        $this->params = array_merge($this->params, $params);

        // 处理头像
        if ($this->params['img_src'] != '') {
            if ($this->isValidUrl($this->params['img_src'])) {
                $this->params['style'] .= ';background-image:url(' . $this->params['img_src'] . ');';
            } else {
                $this->params['avatar'] = $this->params['img_src'];
            }
        }

        /** @var View $view */
        $view = app('view', [], true);
        return $view->fetch(self::$templatePath, $this->params);
    }

    /**
     * 设置名称
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        $this->params['name'] = $name ?: 'DolphinPHP';
        return $this;
    }

    /**
     * 设置图片地址
     * @param string $img
     * @return self
     */
    public function img(string $img): self
    {
        $this->params['img_src'] = $img;
        return $this;
    }

    /**
     * 设置支付图标尺寸
     * @param string $size 支付图标尺寸：xs、sm、md、lg、xl
     * @return self
     */
    public function size(string $size): self
    {
        $this->params['size'] = $size ?: 'xs';
        return $this;
    }

    /**
     * 设置描述
     * @param string $desc 描述内容
     * @return self
     */
    public function desc(string $desc): self
    {
        $this->params['desc'] = $desc;
        return $this;
    }

    /**
     * 设置组件的CSS类
     * @param string|array $class CSS类名
     * @return self
     */
    public function class(string|array $class): self
    {
        $this->params['class'] = is_array($class) ? implode(' ', array_filter($class)) : $class;
        return $this;
    }

    /**
     * 判断是否为图片地址
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url): bool {
        return str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, '/');
    }
}
