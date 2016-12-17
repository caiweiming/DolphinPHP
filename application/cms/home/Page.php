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

use app\cms\model\Page as PageModel;

/**
 * 前台单页控制器
 * @package app\cms\admin
 */
class Page extends Common
{
    /**
     * 单页详情
     * @param null $id 单页id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function detail($id = null)
    {
        $info = PageModel::where('status', 1)->find($id);
        $info['url']  = url('cms/page/detail', ['id' => $info['id']]);
        $info['tags'] = explode(',', $info['keywords']);

        // 更新阅读量
        PageModel::where('id', $id)->setInc('view');

        $this->assign('page_info', $info);
        return $this->fetch(); // 渲染模板
    }
}