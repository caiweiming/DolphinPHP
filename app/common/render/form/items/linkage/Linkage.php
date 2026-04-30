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

namespace app\common\render\form\items\linkage;

use app\common\abstract\FormType;
use app\common\render\form\traits\Multiple;

/**
 * 多级联动组件
 */
class Linkage extends FormType
{
    use Multiple;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'linkage';

    /**
     * 设置远程数据请求地址
     * @param string $url
     * @return $this
     */
    public function url(string $url): static
    {
        $this->config['url'] = $url;
        return $this;
    }

    /**
     * 设置级别配置
     * @param array $levels
     * @return $this
     */
    public function levels(array $levels = []): static
    {
        $this->config['levels'] = $levels;
        return $this;
    }

    /**
     * 提交所有级别值
     * @param bool $submitAll
     * @return $this
     */
    public function submitAll(bool $submitAll = true): static
    {
        $this->config['submit_all'] = $submitAll;
        return $this;
    }
}
