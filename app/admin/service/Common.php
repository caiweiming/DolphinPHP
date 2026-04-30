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

namespace app\admin\service;

use app\common\Request;
use app\common\trait\Jump;
use think\App;

/**
 * 服务层公共类
 */
class Common
{
    use Jump;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * Request实例
     * @var \think\Request|Request
     */
    protected \think\Request|Request $request;

    /**
     * 构造方法
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
    }
}
