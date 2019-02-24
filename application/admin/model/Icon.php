<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;

/**
 * 图标模型
 * @package app\admin\model
 */
class Icon extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_icon';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 图标列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\model\relation\HasMany
     */
    public function icons()
    {
        return $this->hasMany('IconList', 'icon_id')->field('title,class,code');
    }

    /**
     * 获取图标css链接
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUrls()
    {
        $list = self::where('status', 1)->select();
        if ($list) {
            foreach ($list as $key => $item) {
                if ($item['icons']) {
                    $html = '<ul class="js-icon-list items-push-2x text-center">';
                    foreach ($item['icons'] as $icon) {
                        $html .= '<li title="'.$icon['title'].'"><i class="'.$icon['class'].'"></i> <code>'.$icon['code'].'</code></li>';
                    }
                    $html .= '</ul>';
                } else {
                    $html = '<p class="text-center text-muted">暂无图标</p>';
                }
                $list[$key]['html'] = $html;
            }
        }
        return $list;
    }
}
