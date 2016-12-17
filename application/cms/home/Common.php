<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
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
    protected function _initialize()
    {
        parent::_initialize();

        // 获取菜单
        $this->assign('main_nav', $this->getNav('顶部导航'));
        $this->assign('about_nav', $this->getNav('关于'));
        $this->assign('support_nav', $this->getNav('服务与支持'));
        $this->assign('slider', $this->getSlider());
        $this->assign('support', $this->getSupport());
    }

    /**
     * 获取导航
     * @param string $title 导航标题
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    private function getNav($title = '')
    {
        if ($title == '') return [];

        $data_list = Db::view('cms_menu', true)
            ->view('cms_nav', ['title' => 'nav_title'], 'cms_menu.nid=cms_nav.id', 'left')
            ->view('cms_column', ['name' => 'column_name'], 'cms_menu.column=cms_column.id', 'left')
            ->view('cms_page', ['title' => 'page_title'], 'cms_menu.page=cms_page.id', 'left')
            ->where('cms_nav.title', $title)
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

        return Tree::toLayer($data_list);
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