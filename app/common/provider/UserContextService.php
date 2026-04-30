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
declare(strict_types=1);

namespace app\common\provider;

use app\common\service\UserContext;
use think\Service;

/**
 * UserContext 服务提供者
 *
 * @package app\common\provider
 */
class UserContextService extends Service
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册为单例服务
        $this->app->bind(UserContext::class, function () {
            return new UserContext();
        }, true);
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 服务启动逻辑（如果需要）
    }
}
