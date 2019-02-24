<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 广告模型
 * @package app\cms\model
 */
class Advert extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'cms_advert';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义修改器
    public function setStartTimeAttr($value)
    {
        return $value != '' ? strtotime($value) : 0;
    }
    public function setEndTimeAttr($value)
    {
        return $value != '' ? strtotime($value) : 0;
    }
    public function getStartTimeAttr($value)
    {
        return $value != 0 ? date('Y-m-d', $value) : '';
    }
    public function getEndTimeAttr($value)
    {
        return $value != 0 ? date('Y-m-d', $value) : '';
    }
}
