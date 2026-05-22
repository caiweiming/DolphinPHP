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

use app\admin\facade\UserModel;
use app\common\service\AdminLoginThrottleService;
use app\common\service\AdminSecurityPolicyService;
use Exception;
use Throwable;

/**
 * 用户服务类
 */
class User extends Common
{
    /**
     * 用户登录
     * @param array $param
     * @return mixed
     * @throws Throwable
     */
    public function login(array $param = []): mixed
    {
        $username      = trim((string)($param['username'] ?? ''));
        $policyService = app(AdminSecurityPolicyService::class);
        $throttle      = app(AdminLoginThrottleService::class);
        $userInfo = UserModel::getInfo($param['username'], true);

        if (null === $userInfo) {
            dp_log_security('用户名无效', [
                'username' => $param['username']
            ], 'error');
            $throttle->recordFailure($username, $policyService->getLoginMaxRetries(), $policyService->getLoginLockMinutes());
            throw new Exception('dp#username or password incorrect');
        }

        // 验证密码
        if (!dp_password_check($param['password'], $userInfo['password'])) {
            dp_log_security('密码验证失败', [
                'username' => $param['username'],
                'user_id'  => $userInfo['id']
            ], 'error');
            $throttle->recordFailure($username, $policyService->getLoginMaxRetries(), $policyService->getLoginLockMinutes());
            throw new Exception('dp#username or password incorrect');
        }

        if ($userInfo['status'] == 0) {
            dp_log_security('用户已被禁用', [
                'username' => $param['username']
            ], 'error');
            throw new Exception('dp#user disabled');
        }

        // 更新登录信息
        UserModel::updateById($userInfo['id'], [
            'last_login_time' => $this->request->time(),
            'last_login_ip'   => $this->request->ip()
        ]);
        $userInfo['last_login_time'] = $this->request->time();
        $userInfo['last_login_ip']   = $this->request->ip();

        $throttle->clear($username);

        // 保存session
        dp_store_admin_session($userInfo);

        if (dp_is_admin_password_expired($userInfo)) {
            dp_mark_admin_password_expiry_required();
            dp_mark_admin_password_expiry_shell_target('admin/profile/index?tab=security');
        } else {
            dp_clear_admin_password_expiry_required();
            dp_clear_admin_password_expiry_shell_target();
        }

        // 免登录，默认7天
        if (isset($param['auto-login'])) {
            dp_issue_admin_remember_login($userInfo);
        } else {
            dp_revoke_admin_remember_login((int)$userInfo['id']);
        }

        dp_log_security('用户登录成功', [
            'username'         => $param['username'],
            'user_id'          => $userInfo['id'],
            'auto_login'       => isset($param['auto-login']),
            'last_login_ip'    => $userInfo['last_login_ip'],
            'current_login_ip' => $this->request->ip(),
            'login_time'       => date('Y-m-d H:i:s'),
            'user_status'      => $userInfo['status'],
            'source'           => 'web_admin'
        ], 'info');
        return $userInfo;
    }

    /**
     * 获取用户信息
     * @param string $username
     * @return mixed
     */
    public function getUserInfo(string $username): mixed
    {
        return UserModel::getInfo($username);
    }

    /**
     * 解析登录参数
     * @param array $param
     * @return array
     * @throws Throwable
     */
    public function parseParam(array $param = []): array
    {
        $encrypt_key = session('encrypt_key');
        $encrypt_iv  = session('encrypt_iv');

        if ($encrypt_key == '' || $encrypt_iv == '') {
            dp_log_security('加密参数缺失', [
                'encrypt_key_empty' => empty($encrypt_key),
                'encrypt_iv_empty'  => empty($encrypt_iv),
                'session_id'        => session_id(),
                'security_risk'     => 'encryption_failure'
            ], 'error');
            $this->error(lang('dp#login failed'));
        }

        // 解密
        $param['username'] = $this->decrypt($param['username'], $encrypt_key, $encrypt_iv);
        $param['password'] = $this->decrypt($param['password'], $encrypt_key, $encrypt_iv);
        if (isset($param['captcha'])) {
            $param['captcha'] = $this->decrypt($param['captcha'], $encrypt_key, $encrypt_iv);
        }

        return $param;
    }

    /**
     * AES解密
     * @param $data
     * @param string $key
     * @param string $iv
     * @return false|string
     */
    private function decrypt($data, string $key = '', string $iv = ''): bool|string
    {
        $encrypted = base64_decode($data);
        $decrypted = openssl_decrypt($encrypted, 'aes-128-cbc', $key, OPENSSL_ZERO_PADDING, $iv);
        return base64_decode($decrypted);
    }
}
