<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\admin;

use app\admin\controller\Admin;
use think\Db;

/**
 * 仪表盘控制器
 * @package app\cms\admin
 */
class Index extends Admin
{
    /**
     * 首页
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function index()
    {
        $this->assign('document', Db::name('cms_document')->where('trash', 0)->count());
        $this->assign('column', Db::name('cms_column')->count());
        $this->assign('page', Db::name('cms_page')->count());
        $this->assign('model', Db::name('cms_model')->count());
        $this->assign('page_title', '仪表盘');
        return $this->fetch(); // 渲染模板
    }
}
