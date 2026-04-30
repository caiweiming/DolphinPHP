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

use app\common\controller\Common as BaseController;
use app\common\trait\CrudActions;
use Throwable;

/**
 * 后台应用公共控制器
 */
class Common extends BaseController
{
    // 引入通用CRUD操作（子类可直接重写方法自定义行为）
    use CrudActions;

    /**
     * 当前登录用户信息
     * @var mixed
     */
    protected mixed $adminUser = null;

    /**
     * 初始化
     */
    protected function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 判断是否登录
     * @return int 返回当前登录用户ID，未登录返回 0
     * @throws Throwable
     */
    public final function isLogin(): int
    {
        $this->adminUser = dp_is_login();

        // 已登录，返回当前用户id
        if ($this->adminUser) {
            return $this->adminUser['id'];
        }

        return 0;
    }
}
