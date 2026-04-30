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


use Throwable;

class Logout extends Auth
{
    /**
     * 登出
     * @throws Throwable
     */
    public function index(): void
    {
        if ($user = dp_is_login()) {
            // 使用新的日志系统记录登出
            dp_log_user_action('用户登出', [
                'user_id'     => $user['id'] ?? 0,
                'username'    => $user['username'] ?? '',
                'ip'          => $this->request->ip(),
                'user_agent'  => $this->request->header('User-Agent'),
                'logout_time' => date('Y-m-d H:i:s')
            ]);
            dp_revoke_admin_remember_login((int)($user['id'] ?? 0));
            dp_clear_admin_session();
        }
        dp_clear_admin_auth_cookies();
        $this->redirect(url('login/index'));
    }
}
