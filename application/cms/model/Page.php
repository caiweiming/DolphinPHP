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
 * 单页模型
 * @package app\cms\model
 */
class Page extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'cms_page';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取单页标题列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getTitleList()
    {
        $result = cache('cms_page_title_list');
        if (!$result) {
            $result = self::where('status', 1)->column('id,title');
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('cms_page_title_list', $result);
            }
        }
        return $result;
    }
}
