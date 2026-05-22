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

use app\common\service\AdminLoginThrottleService;
use app\common\service\AdminSecurityPolicyService;
use Exception;
use think\facade\View;
use app\admin\facade\UserService;
use Throwable;
use app\common\attribute\LoginCheck;

/**
 * 登录控制器
 * @package app\admin\controller
 */
#[LoginCheck(false)]
class Login extends Common
{
    /**
     * 初始化
     * @throws Throwable
     */
    protected function initialize(): void
    {
        $redirect = $this->getSafeRedirect($this->request->param('redirect/s', ''));

        // 判断是否登录
        if (dp_is_login()) {
            if ($redirect !== '') {
                $this->redirect($redirect);
            }
            $this->redirect(url('index/index')->build());
        }
    }

    /**
     * 登录页
     * @return string
     * @throws Throwable
     */
    public function index(): string
    {
        if ($this->request->isPost()) {
            $captchaEnabled = (bool)dp_setting('login.enable_captcha', 1);
            $post           = $this->request->only(['username', 'password', 'captcha', 'auto-login', 'redirect', config('csrf.token_name')]);
            $post           = UserService::parseParam($post);
            $policyService  = app(AdminSecurityPolicyService::class);
            $throttle       = app(AdminLoginThrottleService::class);
            $username       = trim((string)($post['username'] ?? ''));

            // 验证表单
            $this->autoValidate('User.login', $post);

            if ($captchaEnabled) {
                $captcha = trim((string)($post['captcha'] ?? ''));
                if ($captcha === '' || !captcha_check($captcha)) {
                    $this->error('dp#captcha failed');
                }
            }

            if ($throttle->isLocked($username)
                && $policyService->getLoginMaxRetries() > 0
                && $policyService->getLoginLockMinutes() > 0) {
                $this->error('当前账号已被限制登录，请稍后再试');
            }

            try {
                UserService::login($post);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

            if (dp_admin_password_expiry_required()) {
                $this->success('登录成功', '/admin/security/passwordExpired.html');
            }

            $redirect = $this->getSafeRedirect((string)($post['redirect'] ?? ''));

            // 返回信息
            $this->success('登录成功', $redirect !== '' ? $redirect : 'index/index');
        }

        // 随机产生登录密钥和偏向量
        $key = dp_rand_str(16, 3);
        $iv  = dp_rand_str(16, 3);
        session('encrypt_key', $key);
        session('encrypt_iv', $iv);

        View::assign('encrypt_key', $key);
        View::assign('encrypt_iv', $iv);
        View::assign('redirect_url', $this->getSafeRedirect($this->request->param('redirect/s', '')));
        View::assign('login_captcha_enabled', (bool)dp_setting('login.enable_captcha', 1));

        return View::fetch('index');
    }

    /**
     * 获取安全的回跳地址（仅允许站内相对地址）
     * @param string $redirect
     * @return string
     */
    private function getSafeRedirect(string $redirect): string
    {
        $redirect = trim($redirect);
        if ($redirect === '') {
            return '';
        }

        // 禁止协议与协议相对 URL，避免开放重定向
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//', $redirect) || str_starts_with($redirect, '//')) {
            return '';
        }

        // 禁止 javascript: 等伪协议
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:/', $redirect)) {
            return '';
        }

        // 必须是相对路径
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/' . ltrim($redirect, '/');
        }

        // 禁止回跳到登录/退出，避免循环
        if (preg_match('#^/admin/login/(index|logout)$#i', parse_url($redirect, PHP_URL_PATH) ?: '')) {
            return '';
        }

        return $redirect;
    }
}
