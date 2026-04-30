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

namespace app\common\render\form\items\bmap;

use app\common\abstract\FormType;
use app\common\render\form\traits\Placeholder;

/**
 * 百度地图组件
 */
class Bmap extends FormType
{
    use Placeholder;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'bmap';

    /**
     * 设置百度地图 AK
     * @param string $ak
     * @return $this
     */
    public function ak(string $ak): static
    {
        $this->config['options']['ak'] = $ak;
        return $this;
    }

    /**
     * 设置地图中心点（lng,lat）
     * @param string $center
     * @return $this
     */
    public function center(string $center): static
    {
        $this->config['options']['center'] = $center;
        return $this;
    }

    /**
     * 设置缩放级别
     * @param int $zoom
     * @return $this
     */
    public function zoom(int $zoom): static
    {
        $this->config['options']['zoom'] = $zoom;
        return $this;
    }

    /**
     * 设置地图高度
     * @param int $height
     * @return $this
     */
    public function height(int $height): static
    {
        $this->config['options']['height'] = $height;
        return $this;
    }

    /**
     * 设置地址字段名
     * @param string $field
     * @return $this
     */
    public function addressField(string $field): static
    {
        $this->config['address_field'] = $field;
        return $this;
    }

    /**
     * 设置地址（用于初始化定位）
     * @param string $address
     * @return $this
     */
    public function address(string $address): static
    {
        $this->config['options']['address'] = $address;
        return $this;
    }


    /**
     * 设置只读模式
     * @param bool $readonly
     * @return $this
     */
    public function readonly(bool $readonly = true): static
    {
        $this->config['readonly'] = $readonly;
        return $this;
    }
}

