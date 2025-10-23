<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\user\model;

use Exception;
use think\Model;
use think\helper\Hash;
use app\user\model\Role as RoleModel;
use think\Db;

/**
 * 后台用户模型
 * @package app\admin\model
 */
class User extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 对密码进行加密
    public function setPasswordAttr($value)
    {
        return Hash::make((string)$value);
    }

    // 获取注册ip
    public function setSignupIpAttr()
    {
        return get_client_ip(1);
    }

    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param bool $rememberme 记住登录
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|mixed
     */
    public function login($username = '', $password = '', $rememberme = false)
    {
        $username = trim($username);
        $password = trim($password);

        // 匹配登录方式
        if (preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $username)) {
            // 邮箱登录
            $map['email'] = $username;
        } elseif (preg_match("/^1\d{10}$/", $username)) {
            // 手机号登录
            $map['mobile'] = $username;
        } else {
            // 用户名登录
            $map['username'] = $username;
        }

        $map['status'] = 1;

        // 查找用户
        $user = $this::get($map);
        if (!$user) {
            $this->error = '账号或者密码错误！';
        } else {
            // 检查是否分配用户组
            if ($user['role'] == 0) {
                $this->error = '禁止访问，原因：未分配角色！';
                return false;
            }
            // 检查是可登录后台
            if (!RoleModel::where(['id' => $user['role'], 'status' => 1])->value('access')) {
                $this->error = '禁止访问，用户所在角色未启用或禁止访问后台！';
                return false;
            }
            if (!Hash::check((string)$password, $user['password'])) {
                $this->error = '账号或者密码错误！';
            } else {
                $uid = $user['id'];

                // 更新登录信息
                $user['last_login_time'] = request()->time();
                $user['last_login_ip']   = request()->ip(1);
                if ($user->save()) {
                    // 自动登录
                    return $this->autoLogin($this::get($uid), $rememberme);
                } else {
                    // 更新登录信息失败
                    $this->error = '登录信息更新失败，请重新登录！';
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * 自动登录
     * @param object $user 用户对象
     * @param bool $rememberme 是否记住登录，默认7天
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool|int
     */
    public function autoLogin($user, $rememberme = false)
    {
        // 记录登录SESSION和COOKIES
        $auth = array(
            'uid'             => $user->id,
            'group'           => $user->group,
            'role'            => $user->role,
            'role_name'       => Db::name('admin_role')->where('id', $user->role)->value('name'),
            'avatar'          => $user->avatar,
            'username'        => $user->username,
            'nickname'        => $user->nickname,
            'last_login_time' => $user->last_login_time,
            'last_login_ip'   => get_client_ip(1),
        );
        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));

        // 保存用户节点权限
        if ($user->role != 1) {
            $menu_auth = Db::name('admin_role')->where('id', session('user_auth.role'))->value('menu_auth');
            $menu_auth = json_decode($menu_auth, true);
            if (!$menu_auth) {
                session('user_auth', null);
                session('user_auth_sign', null);
                $this->error = '未分配任何节点权限！';
                return false;
            }
        }

        // 记住登录
        if ($rememberme) {
            // 生成安全的signin_token
            $signin_token = $this->generateSecureSigninToken($user);
            cookie('uid', $user['id'], 24 * 3600 * 7);
            cookie('signin_token', $signin_token, 24 * 3600 * 7);
            cookie('signin_ip', get_client_ip(1), 24 * 3600 * 7);
            cookie('signin_expire', time() + (24 * 3600 * 7), 24 * 3600 * 7);

            // 将token存储到数据库以供验证
            Db::name('admin_user')->where('id', $user['id'])->setField('signin_token', $signin_token);
        }

        return $user->id;
    }

    /**
     * 生成安全的登录token
     * @param object $user 用户对象
     * @return string
     * @throws RandomException
     * @throws Exception
     */
    private function generateSecureSigninToken($user)
    {
        // 使用更安全的随机盐值和用户信息
        $salt = bin2hex(random_bytes(16)); // 生成32位随机盐值
        $client_ip = get_client_ip(1);
        $user_agent = request()->server('HTTP_USER_AGENT', '');
        $timestamp = time();

        // 构建token数据
        $token_data = [
            'uid'             => $user['id'],
            'username'        => $user['username'],
            'salt'            => $salt,
            'ip'              => $client_ip,
            'timestamp'       => $timestamp,
            'user_agent_hash' => hash('sha256', $user_agent)
        ];

        // 使用HMAC-SHA256进行签名
        $secret_key = config('data_auth_key') ?: 'default_secret_key_change_me';
        $signature = hash_hmac('sha256', json_encode($token_data), $secret_key);

        // 组合最终token
        return base64_encode(json_encode([
            'data'      => $token_data,
            'signature' => $signature
        ]));
    }

    /**
     * 验证安全登录token
     * @param string $token
     * @param int $uid
     * @return bool
     */
    public function verifySecureSigninToken($token, $uid): bool
    {
        try {
            $decoded = json_decode(base64_decode($token), true);
            if (!$decoded || !isset($decoded['data']) || !isset($decoded['signature'])) {
                return false;
            }

            $data = $decoded['data'];
            $signature = $decoded['signature'];

            // 验证token基本信息
            if ($data['uid'] != $uid) {
                return false;
            }

            // 验证token是否过期（7天）
            if (time() - $data['timestamp'] > 7 * 24 * 3600) {
                return false;
            }

            // 验证IP地址（可选配置）
            if (config('check_signin_ip') && $data['ip'] != get_client_ip(1)) {
                return false;
            }

            // 验证User-Agent（可选配置）
            if (config('check_signin_user_agent')) {
                $current_user_agent_hash = hash('sha256', request()->server('HTTP_USER_AGENT', ''));
                if ($data['user_agent_hash'] != $current_user_agent_hash) {
                    return false;
                }
            }

            // 验证签名
            $secret_key = config('data_auth_key') ?: 'default_secret_key_change_me';
            $expected_signature = hash_hmac('sha256', json_encode($data), $secret_key);

            if (!hash_equals($signature, $expected_signature)) {
                return false;
            }

            // 验证数据库中的token是否一致（防止并发登录）
            $db_token = Db::name('admin_user')->where('id', $uid)->value('signin_token');
            if ($db_token !== $token) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
