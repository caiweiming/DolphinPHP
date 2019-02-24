<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\controller\Common;

/**
 * ie提示页面控制器
 * @package app\admin\controller
 */
class Ie extends Common
{
    /**
     * 显示ie提示
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function index(){
        // ie浏览器判断
        if (get_browser_type() == 'ie') {
            return $this->fetch();
        } else {
            $this->redirect('admin/index/index');
        }
    }
}