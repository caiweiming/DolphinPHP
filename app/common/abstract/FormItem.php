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

namespace app\common\abstract;

use app\common\interface\FormItem as FormItemInterface;

/**
 * 表单项抽象类
 */
abstract class FormItem implements FormItemInterface
{
    // 表单项参数
    protected array $params = [];
    // 表单项值
    protected mixed $value;
    // 表单项模板
    protected string $template = '';

    /**
     * 构造函数
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = array_merge($this->getParams(), $params);
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'css'  => [],
            'js'   => [],
            'init' => []
        ];
    }

    /**
     * 获取模板路径
     * @return string
     */
    public function getTemplate(): string
    {
        return '';
    }

    /**
     * 获取表单项参数
     * @return array
     */
    protected function getParams(): array
    {
        return [];
    }
}
