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

namespace app\common\component\flag;

use Exception;
use think\View;
use app\common\interface\Component;

/**
 * 国旗组件
 */
class Item implements Component
{
    /**
     * 组件默认参数
     * @var array|string[]
     */
    private array $params = [
        'country' => 'cn',
        'size'    => 'xs',
        'label'   => '',
        'class'   => '',
    ];

    /**
     * @var string|null 模板路径缓存
     */
    private static ?string $templatePath = null;

    /**
     * 构造函数
     * @param string $country 国家标识，参考：https://tabler.io/docs/ui/plugins/flags
     * @param string $label 标签内容
     * @param string $size 图标大小
     * @param string|array $class CSS类名
     */
    public function __construct(string $country = '', string $label = '', string $size = '', string|array $class = '')
    {
        $this->country($country)
            ->label($label)
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
            self::$templatePath = dp_component_path() . 'flag' . DIRECTORY_SEPARATOR . 'item.html';
        }

        // 合并传入的参数
        $this->params = array_merge($this->params, $params);

        /** @var View $view */
        $view = app('view', [], true);
        return $view->fetch(self::$templatePath, $this->params);
    }

    /**
     * 设置国家
     * @param string $country 国家代号
     * @return self
     */
    public function country(string $country): self
    {
        $this->params['country'] = $country ?: 'cn';
        return $this;
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：xs、sm、md、lg、xl
     * @return self
     */
    public function size(string $size): self
    {
        $this->params['size'] = $size ?: 'xs';
        return $this;
    }

    /**
     * 设置支付图标标签
     * @param string $label 标签内容
     * @return self
     */
    public function label(string $label): self
    {
        $this->params['label'] = $label;
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
}
