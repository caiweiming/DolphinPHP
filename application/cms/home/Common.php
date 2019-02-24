<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\home;

use app\index\controller\Home;
use think\Db;
use util\Tree;

/**
 * 前台公共控制器
 * @package app\cms\admin
 */
class Common extends Home
{
    /**
     * 初始化方法
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function initialize()
    {
        parent::initialize();

        // 获取菜单
        $this->getNav();
        // 获取滚动图片
        $this->assign('slider', $this->getSlider());
        // 获取客服
        $this->assign('support', $this->getSupport());
    }

    /**
     * 获取导航
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function getNav()
    {
        $list_nav = Db::name('cms_nav')->where('status', 1)->column('id,tag');

        foreach ($list_nav as $id => $tag) {
            $data_list = Db::view('cms_menu', true)
                ->view('cms_column', ['name' => 'column_name'], 'cms_menu.column=cms_column.id', 'left')
                ->view('cms_page', ['title' => 'page_title'], 'cms_menu.page=cms_page.id', 'left')
                ->where('cms_menu.nid', $id)
                ->where('cms_menu.status', 1)
                ->order('cms_menu.sort,cms_menu.pid,cms_menu.id')
                ->select();

            foreach ($data_list as &$item) {
                if ($item['type'] == 0) { // 栏目链接
                    $item['title'] = $item['column_name'];
                    $item['url'] = url('cms/column/index', ['id' => $item['column']]);
                } elseif ($item['type'] == 1) { // 单页链接
                    $item['title'] = $item['page_title'];
                    $item['url'] = url('cms/page/detail', ['id' => $item['page']]);
                } else {
                    if ($item['url'] != '#' && substr($item['url'], 0, 4) != 'http') {
                        $item['url'] = url($item['url']);
                    }
                }
            }

            $this->assign($tag, Tree::toLayer($data_list));
        }
    }

    /**
     * 获取滚动图片
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function getSlider()
    {
        return Db::name('cms_slider')->where('status', 1)->select();
    }

    /**
     * 获取在线客服
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function getSupport()
    {
        return Db::name('cms_support')->where('status', 1)->order('sort')->select();
    }
}
