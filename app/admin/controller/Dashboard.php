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

namespace app\admin\controller;

use app\common\component\dropdown\Item as Dropdown;
use app\common\service\UploadDriverManager;
use think\facade\Log;
use think\facade\Route;
use think\Request;
use app\admin\controller\Common;

/**
 * 后台首页控制器
 */
class Dashboard extends Auth
{

    /**
     * index
     * @return string
     * @throws \Exception
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $post = $this->request->param();

//            $this->success('保存失败', '', ['name' => 1, 'id' => rand(111,222)]);
//            $file = $this->request->file('file');
//            $info = Filesystem::disk('public')->putFileAs( '', $file, $file->hashName().'.png');
            halt($post);
        }

        $this->form
            ->item([
                'type' => 'image',
                'name' => 'image',
                'label' => '配置管理',
                'tips' => '请选择日期'
            ])
            ->item([
                'type' => 'file',
                'name' => 'file',
                'label' => '文件',
                'tips' => '请选择日期'
            ])
        ;

        $this->page->row($this->form);
        return $this->fetch();
    }
}