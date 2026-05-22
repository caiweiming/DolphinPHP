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

// 应用公共文件
// 为方便系统核心升级，二次开发中需要用到的公共函数请写在function.php，不要去修改当前文件

use app\common\helper\SuperAdminProtection;
use app\common\interface\PermissionService;
use app\common\model\Role;
use app\common\model\User;
use app\common\service\AppService as AdminAppService;
use app\common\service\AdminShellContextBuilder;
use app\common\service\AdminSecurityPolicyService;
use app\common\service\AssetManager;
use app\common\service\ConfigService;
use app\common\service\UserContext;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;
use think\helper\Str;
use think\Model;
use think\Response;
use think\route\Url;
use think\facade\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;
use think\facade\Session;
use app\admin\facade\UserModel;
use app\admin\facade\FileService;
use app\common\helper\Logger;
use app\common\helper\TreeBuilder;

// 应用目录
$appPath = App::getAppPath();

// 加载自定义公共文件
if (is_file($appPath . 'function.php')) {
    include_once $appPath . 'function.php';
}

if (!function_exists('dp_is_login')) {
    /**
     * 判断后台用户是否登录
     * @return false|mixed 未登录返回 false，已登录返回用户信息数组
     * @throws Throwable
     */
    function dp_is_login(): mixed
    {
        // 1. 检查 Session
        $adminUser        = session(Config::get('system.admin_session'));
        $loginTime        = session('dp_admin_login_time');
        $lastActivityTime = (int)Session::get('dp_admin_last_activity_time', 0);

        if (!empty($adminUser)) {
            if ($lastActivityTime > 0) {
                return $adminUser;
            }

            // 兼容旧版登录超时配置（默认 7200 秒 = 2 小时）
            $sessionTimeout = Config::get('system.session_timeout', 7200);
            if ($loginTime && (time() - $loginTime > $sessionTimeout)) {
                dp_log_security('会话超时', [
                    'user_id'     => $adminUser['id'] ?? 0,
                    'login_time'  => $loginTime,
                    'timeout_sec' => $sessionTimeout
                ]);

                // 清除 Session
                dp_clear_admin_session();
            } else {
                return $adminUser;
            }
        }

        // 2. 尝试 remember-me 自动登录
        $rememberToken = dp_parse_admin_remember_cookie();
        if ($rememberToken === null) {
            if (cookie('?' . Config::get('system.admin_uid')) || cookie('?' . Config::get('system.admin_token'))) {
                dp_clear_admin_auth_cookies();
            }
            return false;
        }

        [$selector, $validator] = $rememberToken;
        $userInfo = UserModel::getInfoByRememberSelector($selector);

        if (!$userInfo) {
            dp_clear_admin_auth_cookies();
            return false;
        }

        if ((int)$userInfo['remember_expires_at'] <= time()) {
            dp_log_security('Remember-me 令牌已过期', [
                'user_id'  => $userInfo['id'] ?? 0,
                'username' => $userInfo['username'] ?? '',
            ]);
            dp_revoke_admin_remember_login((int)$userInfo['id']);
            return false;
        }

        if ((int)$userInfo['status'] !== 1) {
            dp_log_security('Remember-me 用户状态异常', [
                'user_id'  => $userInfo['id'] ?? 0,
                'username' => $userInfo['username'] ?? '',
                'status'   => $userInfo['status'] ?? null,
            ]);
            dp_revoke_admin_remember_login((int)$userInfo['id']);
            return false;
        }

        $storedHash     = (string)($userInfo['remember_token_hash'] ?? '');
        $calculatedHash = hash('sha256', $validator);
        if ($storedHash === '' || !hash_equals($storedHash, $calculatedHash)) {
            dp_log_security('Remember-me 令牌校验失败', [
                'user_id'  => $userInfo['id'] ?? 0,
                'username' => $userInfo['username'] ?? '',
                'ip'       => request()->ip(),
            ], 'error');
            dp_revoke_admin_remember_login((int)$userInfo['id']);
            return false;
        }

        $sessionUser = dp_store_admin_session($userInfo);
        dp_issue_admin_remember_login($userInfo);

        dp_log_security('Cookie 自动登录成功', [
            'user_id'  => $sessionUser['id'] ?? 0,
            'username' => $sessionUser['username'] ?? '',
        ], 'info');

        return $sessionUser;
    }
}

if (!function_exists('dp_admin_remember_cookie_name')) {
    /**
     * 获取后台 remember-me cookie 名称
     * @return string
     */
    function dp_admin_remember_cookie_name(): string
    {
        return (string)Config::get('system.admin_remember', 'dp_admin_remember');
    }
}

if (!function_exists('dp_admin_remember_cookie_options')) {
    /**
     * 获取后台 remember-me cookie 选项
     * @param int $expireSeconds
     * @return array
     */
    function dp_admin_remember_cookie_options(int $expireSeconds = 0): array
    {
        return array_merge((array)Config::get('cookie', []), [
            'expire'   => $expireSeconds,
            'httponly' => true,
            'samesite' => 'lax',
            'secure'   => Request::isSsl(),
        ]);
    }
}

if (!function_exists('dp_clear_admin_session')) {
    /**
     * 清理后台认证 Session
     * @return void
     */
    function dp_clear_admin_session(): void
    {
        session(Config::get('system.admin_session'), null);
        session('dp_admin_login_time', null);
        session('dp_admin_last_activity_time', null);
    }
}

if (!function_exists('dp_clear_admin_auth_cookies')) {
    /**
     * 清理后台认证 Cookie
     * @return void
     */
    function dp_clear_admin_auth_cookies(): void
    {
        cookie(dp_admin_remember_cookie_name(), null, dp_admin_remember_cookie_options());
        cookie((string)Config::get('system.admin_uid'), null);
        cookie((string)Config::get('system.admin_token'), null);
    }
}

if (!function_exists('dp_normalize_admin_session_user')) {
    /**
     * 规范化后台 Session 用户信息
     * @param mixed $userInfo
     * @return array
     */
    function dp_normalize_admin_session_user(mixed $userInfo): array
    {
        if ($userInfo instanceof Model) {
            $userInfo = $userInfo->toArray();
        }

        $sessionUser = is_array($userInfo) ? $userInfo : [];
        unset(
            $sessionUser['password'],
            $sessionUser['remember_selector'],
            $sessionUser['remember_token_hash'],
            $sessionUser['remember_expires_at']
        );

        return $sessionUser;
    }
}

if (!function_exists('dp_store_admin_session')) {
    /**
     * 建立后台认证 Session
     * @param mixed $userInfo
     * @return array
     */
    function dp_store_admin_session(mixed $userInfo): array
    {
        Session::regenerate(true);

        $sessionUser = dp_normalize_admin_session_user($userInfo);
        session(Config::get('system.admin_session'), $sessionUser);
        session('dp_admin_login_time', time());
        session('dp_admin_last_activity_time', time());

        return $sessionUser;
    }
}

if (!function_exists('dp_is_admin_password_expired')) {
    /**
     * 判断后台用户密码是否过期
     * @param mixed $userInfo
     * @return bool
     */
    function dp_is_admin_password_expired(mixed $userInfo): bool
    {
        if ($userInfo instanceof Model) {
            $userInfo = $userInfo->toArray();
        }

        if (!is_array($userInfo) || empty($userInfo['id'])) {
            return false;
        }

        $userId = (int)$userInfo['id'];
        $user   = UserModel::find($userId);
        if (!$user) {
            return false;
        }

        $days = app(AdminSecurityPolicyService::class)->getPasswordExpireDays();
        if ($days <= 0) {
            return false;
        }

        $updatedAt = (int)$user->getAttr('password_updated_time');
        if ($updatedAt <= 0) {
            $updatedAt = (int)$user->getAttr('update_time');
        }
        if ($updatedAt <= 0) {
            $updatedAt = (int)$user->getAttr('create_time');
        }
        if ($updatedAt <= 0) {
            return true;
        }

        return (time() - $updatedAt) > ($days * 86400);
    }
}

if (!function_exists('dp_mark_admin_password_expiry_required')) {
    /**
     * 标记当前后台会话需要修改密码
     * @return void
     */
    function dp_mark_admin_password_expiry_required(): void
    {
        session('dp_admin_password_expired', 1);
    }
}

if (!function_exists('dp_clear_admin_password_expiry_required')) {
    /**
     * 清除后台密码过期标记
     * @return void
     */
    function dp_clear_admin_password_expiry_required(): void
    {
        session('dp_admin_password_expired', null);
    }
}

if (!function_exists('dp_admin_password_expiry_required')) {
    /**
     * 判断当前后台会话是否需要修改密码
     * @return bool
     */
    function dp_admin_password_expiry_required(): bool
    {
        return (bool)Session::get('dp_admin_password_expired', 0);
    }
}

if (!function_exists('dp_mark_admin_password_expiry_shell_target')) {
    /**
     * 标记密码过期后壳层首次打开目标
     * @param string $target
     * @return void
     */
    function dp_mark_admin_password_expiry_shell_target(string $target): void
    {
        Session::set('dp_admin_password_expiry_shell_target', trim($target));
    }
}

if (!function_exists('dp_clear_admin_password_expiry_shell_target')) {
    /**
     * 清除密码过期壳层打开目标
     * @return void
     */
    function dp_clear_admin_password_expiry_shell_target(): void
    {
        Session::delete('dp_admin_password_expiry_shell_target');
    }
}

if (!function_exists('dp_admin_password_expiry_shell_target')) {
    /**
     * 获取密码过期壳层首次打开目标
     * @return string
     */
    function dp_admin_password_expiry_shell_target(): string
    {
        return trim((string)Session::get('dp_admin_password_expiry_shell_target', ''));
    }
}

if (!function_exists('dp_touch_admin_session_activity')) {
    /**
     * 刷新后台最近活动时间
     * @return void
     */
    function dp_touch_admin_session_activity(): void
    {
        session('dp_admin_last_activity_time', time());
    }
}

if (!function_exists('dp_admin_session_is_idle_expired')) {
    /**
     * 判断后台会话是否已空闲超时
     * @param int $idleMinutes
     * @return bool
     */
    function dp_admin_session_is_idle_expired(int $idleMinutes): bool
    {
        if ($idleMinutes <= 0) {
            return false;
        }

        $lastActivityTime = (int)Session::get('dp_admin_last_activity_time', 0);
        if ($lastActivityTime <= 0) {
            return false;
        }

        return (time() - $lastActivityTime) > ($idleMinutes * 60);
    }
}

if (!function_exists('dp_issue_admin_remember_login')) {
    /**
     * 下发后台 remember-me 令牌
     * @param mixed $userInfo
     * @return void
     * @throws Throwable
     */
    function dp_issue_admin_remember_login(mixed $userInfo): void
    {
        $sessionUser = dp_normalize_admin_session_user($userInfo);
        $userId      = (int)($sessionUser['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $selector  = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = time() + (86400 * (int)Config::get('system.login_days', 7));

        UserModel::updateRememberTokenById($userId, $selector, hash('sha256', $validator), $expiresAt);
        cookie(
            dp_admin_remember_cookie_name(),
            $selector . ':' . $validator,
            dp_admin_remember_cookie_options($expiresAt - time())
        );
        cookie((string)Config::get('system.admin_uid'), null);
        cookie((string)Config::get('system.admin_token'), null);
    }
}

if (!function_exists('dp_parse_admin_remember_cookie')) {
    /**
     * 解析后台 remember-me cookie
     * @return array<string>|null
     */
    function dp_parse_admin_remember_cookie(): ?array
    {
        $payload = cookie(dp_admin_remember_cookie_name());
        if (!is_string($payload) || $payload === '' || !str_contains($payload, ':')) {
            return null;
        }

        [$selector, $validator] = explode(':', $payload, 2);
        if (!preg_match('/^[a-f0-9]{32}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            dp_clear_admin_auth_cookies();
            return null;
        }

        return [$selector, $validator];
    }
}

if (!function_exists('dp_revoke_admin_remember_login')) {
    /**
     * 撤销后台 remember-me 令牌
     * @param int $userId
     * @return void
     */
    function dp_revoke_admin_remember_login(int $userId): void
    {
        if ($userId > 0) {
            UserModel::clearRememberTokenById($userId);
        }

        dp_clear_admin_auth_cookies();
    }
}

if (!function_exists('dp_auth_sign')) {
    /**
     * 数据签名认证（HMAC-SHA256）
     * @param array $data 被认证的数据
     * @return string
     * @throws Throwable
     */
    function dp_auth_sign(array $data = []): string
    {
        // 按键排序确保一致性
        ksort($data);

        // 生成签名字符串
        $signString = http_build_query($data);

        // 获取应用密钥（从配置文件）
        $secretKey = Config::get('system.auth_secret_key', 'DolphinPHP-Secret-' . md5(__DIR__));

        // 使用 HMAC-SHA256 生成签名
        $signature = hash_hmac('sha256', $signString, $secretKey);

        // 记录签名生成日志（仅调试模式）
        if (Config::get('app.debug')) {
            dp_log('生成认证签名')->context([
                'data_keys'        => array_keys($data),
                'signature_prefix' => substr($signature, 0, 16) . '...'
            ])->debug();
        }

        return $signature;
    }
}

if (!function_exists('dp_token')) {
    /**
     * 获取Token令牌
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function dp_token(string $name = '', string $type = ''): string
    {
        $name = $name ?: Config::get('csrf.token_name');
        $type = $type ?: Config::get('csrf.token_type');
        return Request::buildToken($name, $type);
    }
}

if (!function_exists('dp_token_meta')) {
    /**
     * 生成令牌meta
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function dp_token_meta(string $name = '', string $type = ''): string
    {
        return '<meta name="csrf-token" content="' . dp_token($name, $type) . '">';
    }
}

if (!function_exists('dp_token_field')) {
    /**
     * 生成令牌隐藏表单
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function dp_token_field(string $name = '', string $type = ''): string
    {
        $name = $name ?: Config::get('csrf.token_name');
        return '<input type="hidden" name="' . $name . '" value="' . dp_token($name, $type) . '">';
    }
}

if (!function_exists('dp_rand_str')) {
    /**
     * 生成随机字符串
     * @param int $length 生成长度
     * @param int $type 生成类型：0-小写字母+数字，1-小写字母，2-大写字母，3-数字，4-小写+大写字母，5-小写+大写+数字
     * @return string
     */
    function dp_rand_str(int $length = 8, int $type = 0): string
    {
        $a = 'abcdefghijklmnopqrstuvwxyz';
        $A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $n = '0123456789';

        $chars = match ($type) {
            1 => $a,
            2 => $A,
            3 => $n,
            4 => $a . $A,
            5 => $a . $A . $n,
            default => $a . $n,
        };

        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}

if (!function_exists('dp_password_hash')) {
    /**
     * 加密密码
     * @param string $value 要加密的密码明文
     * @return string
     */
    function dp_password_hash(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
}

if (!function_exists('dp_password_check')) {
    /**
     * 验证密码
     * @param string $value 要对比的原始字符串
     * @param string $hashedValue 加密后的字符串
     * @return bool
     */
    function dp_password_check(string $value, string $hashedValue): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }
        return password_verify($value, $hashedValue);
    }
}

if (!function_exists('dp_parse_actions')) {
    /**
     * 将actions转为小写数组形式返回
     * @param $actions
     * @return array
     */
    function dp_parse_actions($actions): array
    {
        return array_map(function ($item) {
            return strtolower($item);
        }, is_string($actions) ? explode(",", $actions) : $actions);
    }
}

if (!function_exists('dp_url')) {
    /**
     * Url生成,支持应用映射
     * @param string $url 路由地址
     * @param array $vars 变量
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return Url
     */
    function dp_url(string $url = '', array $vars = [], bool|string $suffix = true, bool|string $domain = false): Url
    {
        if (str_contains($url, '\\') || str_starts_with($url, '@') || '' === $url) {
            return url($url, $vars, $suffix, $domain);
        }

        if (str_starts_with($url, '/')) {
            // 直接作为路由地址解析
            $url = substr($url, 1);
        }

        $urls = explode('/', $url);
        if (count($urls) >= 3) {
            $app_map = Config::get('app.app_map');
            foreach ($app_map as $map => $app) {
                if ($app == $urls[0] && $map != '*') {
                    $urls[0] = $map;
                    break;
                }
            }

            $url = implode('/', $urls);
        }

        return url($url, $vars, $suffix, $domain);
    }
}


if (!function_exists('dp_base_dir')) {
    /**
     * 获取入口文件所在URL根目录
     * @return string
     */
    function dp_base_dir(): string
    {
        $base_file = request()->baseFile();
        return rtrim(substr($base_file, 0, strripos($base_file, '/') + 1), '/') . '/';
    }
}

if (!function_exists('dp_theme_layout')) {
    /**
     * 获取主题布局文件
     * @return string
     */
    function dp_theme_layout(): string
    {
        return base_path() . Config::get('system.layout', 'admin/view/layout/default.html');
    }
}

if (!function_exists('dp_page_layout')) {
    /**
     * 获取页面布局文件
     * @return string
     */
    function dp_page_layout(): string
    {
        return dp_page_path() . 'layout.html';
    }
}

if (!function_exists('dp_render_path')) {
    /**
     * 渲染器资源目录
     * @return string
     */
    function dp_render_path(): string
    {
        return base_path() . 'common' . DIRECTORY_SEPARATOR . 'render' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_component_path')) {
    /**
     * 渲染器资源目录
     * @return string
     */
    function dp_component_path(): string
    {
        return base_path() . 'common' . DIRECTORY_SEPARATOR . 'component' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_form_path')) {
    /**
     * 表单渲染器资源目录
     * @return string
     */
    function dp_form_path(): string
    {
        return dp_render_path() . 'form' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_table_path')) {
    /**
     * 表格渲染器资源目录
     * @return string
     */
    function dp_table_path(): string
    {
        return dp_render_path() . 'table' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_extend_table_path')) {
    /**
     * 表格扩展目录
     * @return string
     */
    function dp_extend_table_path(): string
    {
        return root_path() . 'extend' . DIRECTORY_SEPARATOR . 'table' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_extend_chart_path')) {
    /**
     * 图表扩展目录
     * @return string
     */
    function dp_extend_chart_path(): string
    {
        return root_path() . 'extend' . DIRECTORY_SEPARATOR . 'chart' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_extend_chart_map_path')) {
    /**
     * 图表地图专项扩展目录
     * @return string
     */
    function dp_extend_chart_map_path(): string
    {
        return root_path() . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_page_path')) {
    /**
     * 页面渲染器资源目录
     * @return string
     */
    function dp_page_path(): string
    {
        return dp_render_path() . 'page' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_chart_path')) {
    /**
     * 图表渲染器资源目录
     * @return string
     */
    function dp_chart_path(): string
    {
        return dp_render_path() . 'chart' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_static_path')) {
    /**
     * 静态资源目录
     * @return string
     */
    function dp_static_path(): string
    {
        return dp_base_dir() . 'static' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_static_render_path')) {
    /**
     * 表单渲染器资源目录
     * @return string
     */
    function dp_static_render_path(): string
    {
        return dp_static_path() . 'render' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_static_libs_path')) {
    /**
     * 第三方资源目录
     * @return string
     */
    function dp_static_libs_path(): string
    {
        return dp_static_path() . 'libs' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_static_extend_chart_path')) {
    /**
     * 图表扩展静态资源目录
     * @return string
     */
    function dp_static_extend_chart_path(): string
    {
        return dp_base_dir() . 'extend' . DIRECTORY_SEPARATOR . 'chart' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_static_extend_chart_map_path')) {
    /**
     * 图表地图专项扩展静态资源目录
     * @return string
     */
    function dp_static_extend_chart_map_path(): string
    {
        return dp_base_dir() . 'extend' . DIRECTORY_SEPARATOR . 'chart_map' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('dp_arr2str')) {
    /**
     * 将一维数组转为字符串
     * @param array $attr
     * @return string
     */
    function dp_arr2str(array $attr = []): string
    {
        $result = [];
        foreach ($attr as $key => $value) {
            // 处理布尔值属性
            if (is_bool($value)) {
                if ($value) {
                    $result[] = is_numeric($key) ? '' : $key;
                }
                continue;
            }

            // 处理数字键
            if (is_numeric($key)) {
                if (!empty($value)) {
                    $result[] = $value;
                }
                continue;
            }

            // 处理 null 值
            if (is_null($value)) {
                continue;
            }

            // 处理数组值
            if (is_array($value)) {
                if (!empty($value)) {
                    $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($jsonValue === false) {
                        // JSON 编码失败时的处理
                        continue;
                    }
                    $result[] = $key . '=\'' . htmlspecialchars($jsonValue, ENT_QUOTES, 'UTF-8') . '\'';
                }
                continue;
            }

            // 处理普通字符串值
            $result[] = $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return implode(' ', $result);
    }
}

if (!function_exists('dp_arr2kv')) {
    /**
     * 将一维数组转为键和值相同的关联数组
     * @param array $items
     * @return array
     */
    function dp_arr2kv(array $items = []): array
    {
        if ($items === []) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_scalar($item) && $item !== null) {
                continue;
            }

            $value          = (string)$item;
            $result[$value] = $value;
        }

        return $result;
    }
}

if (!function_exists('dp_setting')) {
    /**
     * 读取动态配置值
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function dp_setting(string $key, mixed $default = null): mixed
    {
        return app(ConfigService::class)->get($key, $default);
    }
}

if (!function_exists('dp_admin_logo_path')) {
    /**
     * 获取后台品牌 Logo 地址
     * @param string $default
     * @return string
     */
    function dp_admin_logo_path(string $default = '/static/img/logo-white.png'): string
    {
        $default = trim($default) !== '' ? trim($default) : '/static/img/logo-white.png';
        $logo    = dp_setting('site.logo', '');

        if (is_array($logo)) {
            $logo = reset($logo) ?: '';
        }

        $logo = trim((string)$logo);
        if ($logo === '') {
            return $default;
        }

        if (is_numeric($logo)) {
            $path = trim(dp_get_file_path($logo));
            return $path !== '' ? $path : $default;
        }

        return $logo;
    }
}

if (!function_exists('dp_app_setting')) {
    /**
     * 读取应用声明型设置值
     * @param string $app
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws Throwable
     */
    function dp_app_setting(string $app, string $key, mixed $default = null): mixed
    {
        return app(AdminAppService::class)->getSetting($app, $key, $default);
    }
}

if (!function_exists('dp_to_snake_case')) {
    /**
     * 将标识符转换为 snake_case
     * @param string $value
     * @return string
     */
    function dp_to_snake_case(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace('-', '_', $value);
        $value = preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $value) ?? $value;
        $value = preg_replace('/(?<=[A-Z])([A-Z][a-z])/', '_$1', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return strtolower($value);
    }
}

if (!function_exists('dp_normalize_extension_path')) {
    /**
     * 将扩展标识路径统一为 slash + snake_case 形式
     * @param string $value
     * @return string
     */
    function dp_normalize_extension_path(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $segments = preg_split('/[.\/\\\\]+/', $value) ?: [];
        $segments = array_values(array_filter($segments, static fn(string $segment): bool => $segment !== ''));
        $segments = array_map(static fn(string $segment): string => dp_to_snake_case($segment), $segments);

        return implode('/', $segments);
    }
}

if (!function_exists('dp_normalize_extension_name')) {
    /**
     * 将扩展名称统一为 snake_case 形式
     * @param string $value
     * @return string
     */
    function dp_normalize_extension_name(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[.\/\\\\-]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return dp_to_snake_case($value);
    }
}

if (!function_exists('dp_plugin_permission_prefix')) {
    /**
     * 生成插件权限前缀
     * @param string $pluginName
     * @return string
     */
    function dp_plugin_permission_prefix(string $pluginName): string
    {
        $pluginName = trim($pluginName);
        if ($pluginName === '') {
            return '';
        }

        $normalized = str_replace(['\\', '/'], '.', $pluginName);
        $normalized = preg_replace('/\.+/', '.', $normalized) ?? $normalized;
        $normalized = trim($normalized, '.');

        if ($normalized === '') {
            return '';
        }

        $segments = array_map(
            static fn(string $segment): string => dp_normalize_extension_name($segment),
            explode('.', $normalized)
        );
        $segments = array_values(array_filter($segments, static fn(string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return '';
        }

        return 'plugin.' . implode('.', $segments);
    }
}

if (!function_exists('dp_plugin_view_path')) {
    /**
     * 解析插件视图文件路径
     * @param string $pluginName
     * @param string $template
     * @return string
     */
    function dp_plugin_view_path(string $pluginName, string $template = ''): string
    {
        $pluginName = dp_normalize_extension_path($pluginName);
        if ($pluginName === '') {
            return '';
        }

        $viewPaths = (array)config('plugin.view_paths', []);
        $basePath  = (string)($viewPaths[$pluginName] ?? '');
        if ($basePath === '' || !is_dir($basePath)) {
            return '';
        }

        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        if ($template === '') {
            return $basePath;
        }

        $template = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($template));
        $template = ltrim($template, DIRECTORY_SEPARATOR);
        if ($template === '' || str_contains($template, '..')) {
            return '';
        }

        return $basePath . DIRECTORY_SEPARATOR . $template;
    }
}

if (!function_exists('dp_register_page_assets')) {
    /**
     * 注册页面资源到 AssetManager
     * @param array<string, mixed> $assets
     * @return void
     */
    function dp_register_page_assets(array $assets = []): void
    {
        $assetManager = AssetManager::instance();

        foreach ((array)($assets['css'] ?? []) as $css) {
            if (is_string($css) && trim($css) !== '') {
                $assetManager->addCss(trim($css));
            }
        }

        foreach ((array)($assets['js'] ?? []) as $js) {
            if (is_string($js) && trim($js) !== '') {
                $assetManager->addJs(trim($js));
            }
        }

        foreach ((array)($assets['init'] ?? []) as $initJs) {
            if (is_string($initJs) && trim($initJs) !== '') {
                $assetManager->addInlineJs(
                    '<script>' . PHP_EOL . trim($initJs) . PHP_EOL . '</script>',
                    '__plugin_init_' . md5(trim($initJs))
                );
            }
        }
    }
}

if (!function_exists('dp_collect_view_asset_vars')) {
    /**
     * 将 AssetManager 中的资源收集到视图变量
     * @param array<string, mixed> $vars
     * @return array<string, mixed>
     */
    function dp_collect_view_asset_vars(array $vars = []): array
    {
        $assetData            = AssetManager::instance()->getAssets();
        $vars['dp_file_css']  = array_values(array_unique(array_merge((array)($vars['dp_file_css'] ?? []), (array)($assetData['css'] ?? []))));
        $vars['dp_file_js']   = array_values(array_unique(array_merge((array)($vars['dp_file_js'] ?? []), (array)($assetData['js'] ?? []))));
        $vars['dp_extra_css'] = array_values(array_unique(array_merge((array)($vars['dp_extra_css'] ?? []), (array)($assetData['extra_css'] ?? []))));
        $vars['dp_extra_js']  = array_values(array_unique(array_merge((array)($vars['dp_extra_js'] ?? []), (array)($assetData['extra_js'] ?? []))));

        $initJs = (array)($vars['dp_init_js'] ?? []);
        foreach ((array)($assetData['init_js']['init'] ?? []) as $app => $items) {
            $existing     = (array)($initJs[$app] ?? []);
            $initJs[$app] = array_values(array_unique(array_merge($existing, (array)$items)));
        }
        $vars['dp_init_js'] = $initJs;

        return $vars;
    }
}

if (!function_exists('dp_resolve_admin_slot_items')) {
    /**
     * 解析后台槽位入口
     * @param array<int, array<string, mixed>> $items
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    function dp_resolve_admin_slot_items(array $items, int $userId): array
    {
        return app(AdminShellContextBuilder::class)->resolveSlotItems($items, $userId);
    }
}

if (!function_exists('dp_build_admin_current_user_summary')) {
    /**
     * 构建后台当前登录用户摘要
     * @param array $adminUser
     * @return array
     */
    function dp_build_admin_current_user_summary(array $adminUser): array
    {
        return app(AdminShellContextBuilder::class)->buildCurrentUserSummary($adminUser);
    }
}

if (!function_exists('dp_build_admin_shell_view_context')) {
    /**
     * 构建后台壳层视图上下文
     * @return array[]
     * @throws Throwable
     */
    function dp_build_admin_shell_view_context(): array
    {
        return app(AdminShellContextBuilder::class)->buildContext();
    }
}

if (!function_exists('dp_plugin_view')) {
    /**
     * 渲染插件视图
     * @param string $pluginName
     * @param string $template
     * @param array $vars
     * @param array $assets
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    function dp_plugin_view(
        string $pluginName,
        string $template,
        array  $vars = [],
        array  $assets = []
    ): Response
    {
        $viewFile = dp_plugin_view_path($pluginName, $template);
        if ($viewFile === '' || !is_file($viewFile)) {
            throw new RuntimeException('插件视图不存在: ' . $pluginName . '/' . ltrim($template, '/\\'));
        }

        dp_register_page_assets($assets);
        $vars['dp_theme_layout']                         = (string)($vars['dp_theme_layout'] ?? dp_theme_layout());
        $vars['dp_page_layout']                          = (string)($vars['dp_page_layout'] ?? dp_page_layout());
        $vars['dp_admin_topbar_slots_template']          = (string)($vars['dp_admin_topbar_slots_template'] ?? root_path() . 'app/admin/view/layout/_plugin_topbar_slots.html');
        $vars['dp_admin_current_user_dropdown_template'] = (string)($vars['dp_admin_current_user_dropdown_template'] ?? root_path() . 'app/admin/view/layout/_current_user_dropdown.html');
        $vars['dp_admin_user_menu_slots_template']       = (string)($vars['dp_admin_user_menu_slots_template'] ?? root_path() . 'app/admin/view/layout/_plugin_user_menu_slots.html');
        $adminShellContext                               = dp_build_admin_shell_view_context();
        $vars['dp_admin_topbar_slots']                   = (array)($vars['dp_admin_topbar_slots'] ?? $adminShellContext['topbar_slots']);
        $vars['dp_admin_user_menu_slots']                = (array)($vars['dp_admin_user_menu_slots'] ?? $adminShellContext['user_menu_slots']);
        $vars['dp_admin_current_user']                   = (array)($vars['dp_admin_current_user'] ?? $adminShellContext['current_user']);
        dp_register_page_assets($adminShellContext['assets'] ?? []);
        AssetManager::instance()->addJs(dp_static_render_path() . 'page/page.js');
        $vars = dp_collect_view_asset_vars($vars);

        $page    = app('page.render', [], true);
        $content = $page->display((string)file_get_contents($viewFile), $vars);

        return response($content);
    }
}

if (!function_exists('dp_normalize_search_fields')) {
    /**
     * 规范化搜索字段列表（支持数组、逗号/竖线字符串）
     * @param mixed $fields
     * @return array
     */
    function dp_normalize_search_fields(mixed $fields): array
    {
        if (is_string($fields) || is_numeric($fields)) {
            $text   = str_replace(['，', '|'], ',', trim((string)$fields));
            $text   = trim($text, ',');
            $fields = $text === '' ? [] : explode(',', $text);
        }

        if (!is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            if (!is_string($field) && !is_numeric($field)) {
                continue;
            }

            $field = trim((string)$field);
            if ($field === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $field)) {
                continue;
            }

            $result[] = $field;
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists('dp_data_token')) {
    /**
     * 创建数据TOKEN
     * @param array $params
     * @return string
     */
    function dp_data_token(array $params = []): string
    {
        ksort($params);
        $code  = http_build_query($params);
        $token = 'DATA' . substr(sha1($code), 0, 8);
        session($token, $params);
        return $token;
    }
}

if (!function_exists('dp_clean')) {
    /**
     * html安全过滤
     * @param array|string $dirty_html 要过滤的html内容
     * @param array|string|null $config 配置，可以是config/purifier.php内定义的settings，也可以是数组
     * @param array $extra 额外配置
     * @return array|array[]|string|string[]
     */
    function dp_clean(array|string $dirty_html, array|string $config = null, array $extra = []): array|string
    {
        if (is_array($dirty_html)) {
            return array_map(function ($item) use ($config, $extra) {
                return dp_clean($item, $config, $extra);
            }, $dirty_html);
        }

        // 配置
        $configObject               = HTMLPurifier_Config::createDefault();
        $configObject->autoFinalize = Config::get('purifier.finalize', true);

        // 默认配置
        $defaultConfig = Config::get('purifier.default');

        if (null !== $config) {
            if (is_string($config) && !empty($config)) {
                $config = Config::get('purifier.settings.' . $config, []);
            }
            $config = array_merge($defaultConfig, $config);
        } else {
            $config = $defaultConfig;
        }

        if (!is_array($config)) {
            $config = [];
        }

        if (!empty($extra)) {
            $config = array_merge($config, $extra);
        }

        // 加载配置
        $configObject->loadArray($config);

        // 返回过滤后的html
        $purifier = new HTMLPurifier($configObject);
        return $purifier->purify($dirty_html);
    }
}

if (!function_exists('dp_parse_size')) {
    /**
     * 将带单位的体积大小转为字节数
     * @param mixed $size
     * @return float|int
     */
    function dp_parse_size(mixed $size = ''): float|int
    {
        if ($size == '') {
            return 0;
        }

        if (is_numeric($size)) {
            return $size;
        }

        $units     = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $size      = strtoupper(trim($size));
        $unitIndex = array_search(substr($size, -2), $units);

        if ($unitIndex === false) {
            $unitIndex = 0;
        }

        $sizeNum = (float)substr($size, 0, -2);
        return $sizeNum * pow(1024, $unitIndex);
    }
}

if (!function_exists('dp_format_size')) {
    /**
     * 将字节数转为带单位的字符串
     * @param mixed $bytes
     * @return string
     */
    function dp_format_size(mixed $bytes = ''): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $k     = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];

        // 计算单位索引
        $i = floor(log($bytes, $k));
        // 确保索引不超过单位数组范围
        $i = min($i, count($sizes) - 1);

        // 计算转换后的值并保留两位小数（自动去除末尾0）
        $value = round($bytes / pow($k, $i), 2);
        return $value . ' ' . $sizes[$i];
    }
}

if (!function_exists('dp_parse_options')) {
    /**
     * 将数组转为html属性格式
     * @param array $options
     * @return string
     */
    function dp_parse_options(array $options = []): string
    {
        return htmlspecialchars(json_encode($options, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dp_get_file')) {
    /**
     * 获取文件信息
     * @param int|string $id 文件id
     * @param bool $domain 文件链接是否添加域名, 仅对local驱动有效
     * @return array|mixed
     */
    function dp_get_file(int|string $id = 0, bool $domain = false): mixed
    {
        // 读取缓存文件信息
        $file = Cache::get('dp_file:' . $id) ?? FileService::getFileById($id);

        if ($file) {
            if ($domain && $file['driver'] == 'local') {
                $file['url'] = request()->domain() . $file['url'];
            }
            Cache::set('dp_file:' . $id, $file);
            return $file;
        } else {
            return [
                'id'   => '',
                'name' => '',
                'size' => 0,
                'type' => '',
                'url'  => ($domain ? request()->domain() : '') . '/static/img/none.png'
            ];
        }
    }
}

if (!function_exists('dp_get_file_path')) {
    /**
     * 获取附件地址
     * @param mixed $id
     * @return string
     */
    function dp_get_file_path(mixed $id = 0): string
    {
        if ($id === '') {
            return '';
        }
        $file = dp_get_file($id);
        return $file['url'] ?? '';
    }
}

if (!function_exists('dp_get_list_rows')) {
    /**
     * 获取每页显示条数
     * @return int
     */
    function dp_get_list_rows(): int
    {
        return intval(request()->param('limit', Config::get('table.page.limit', 20)));
    }
}

if (!function_exists('dp_format_time')) {
    /**
     * 格式化时间戳或日期字符串
     * @param mixed|null $time 时间戳或日期字符串（如 "2023-01-01"）
     * @param string $format 输出格式（默认：Y-m-d H:i）
     * @return string 格式化后的日期，无效输入返回空字符串
     */
    function dp_format_time(mixed $time = null, string $format = 'Y-m-d H:i'): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $timestamp = is_numeric($time) ? (int)$time : strtotime($time);
        return $timestamp > 0 ? date($format == '' ? 'Y-m-d H:i' : $format, $timestamp) : '';
    }
}

if (!function_exists('dp_get_driver_config')) {
    /**
     * 获取上传驱动配置信息（支持层级获取）
     * @param string $key 配置键名，支持点号分隔的层级获取，如 'local.config.domain'
     * @param mixed|null $default 默认值，当配置不存在时返回此值
     * @return mixed
     */
    function dp_get_driver_config(string $key, mixed $default = null): mixed
    {
        static $configCache = [];

        // 解析配置路径
        $keyParts   = explode('.', $key);
        $driverName = array_shift($keyParts);

        $cacheKey = 'driver_config_' . $driverName;

        // 检查缓存
        if (!isset($configCache[$cacheKey])) {
            // 确定驱动路径
            $driverPath = root_path() . 'extend/upload/' . $driverName;
            $configFile = $driverPath . '/config.php';

            // 检查配置文件是否存在
            if (!file_exists($configFile) || !is_readable($configFile)) {
                $configCache[$cacheKey] = [];
            } else {
                try {
                    // 使用输出缓冲防止意外输出
                    ob_start();
                    $config = require $configFile;
                    $output = ob_get_clean();

                    // 检查是否有意外输出
                    if (!empty($output)) {
                        trace("驱动配置文件存在意外输出: $configFile", 'warning');
                    }

                    if (!is_array($config)) {
                        throw new Exception("配置文件必须返回数组");
                    }

                    $configCache[$cacheKey] = $config;

                } catch (Exception $e) {
                    trace("加载驱动配置失败: $driverName - " . $e->getMessage(), 'error');
                    $configCache[$cacheKey] = [];
                }
            }
        }

        $result = $configCache[$cacheKey];

        // 如果没有子层级，返回整个驱动配置
        if (empty($keyParts)) {
            return empty($result) ? $default : $result;
        }

        // 逐级获取配置值
        foreach ($keyParts as $part) {
            if (!is_array($result) || !array_key_exists($part, $result)) {
                return $default;
            }
            $result = $result[$part];
        }

        return $result;
    }
}

if (!function_exists('dp_get_driver_meta')) {
    /**
     * 获取驱动元信息（从配置文件读取）
     * @param string $driverName 驱动名称
     * @param array $runtimeInfo 运行时信息（可选）
     * @return array
     */
    function dp_get_driver_meta(string $driverName, array $runtimeInfo = []): array
    {
        $config = dp_get_driver_config($driverName);
        $meta   = $config['meta'] ?? [];

        // 添加运行时信息
        if (!empty($runtimeInfo)) {
            $meta = array_merge($meta, $runtimeInfo);
        }

        return $meta;
    }
}

// +----------------------------------------------------------------------
// | 轻量级日志助手函数
// +----------------------------------------------------------------------

if (!function_exists('dp_log')) {
    /**
     * 创建日志记录器实例
     *
     * @param string $title 日志标题
     * @return Logger
     */
    function dp_log(string $title = ''): Logger
    {
        return Logger::make($title);
    }
}

if (!function_exists('dp_log_quick')) {
    /**
     * 快速记录日志
     *
     * @param string $title 日志标题
     * @param string $level 日志级别
     * @param array $context 上下文数据
     * @return void
     * @throws Throwable
     */
    function dp_log_quick(string $title, string $level = 'info', array $context = []): void
    {
        dp_log($title)->context($context)->level($level)->log();
    }
}

if (!function_exists('dp_log_start')) {
    /**
     * 开始记录操作日志
     *
     * @param string $operation 操作名称
     * @param array $context 初始上下文数据
     * @return string 操作ID
     * @throws Throwable
     */
    function dp_log_start(string $operation, array $context = []): string
    {
        $operationId = uniqid('op_', true);
        $startTime   = microtime(true);

        // 将操作信息存储到缓存中
        Cache::set("log_operation_$operationId", [
            'operation'  => $operation,
            'start_time' => $startTime,
            'context'    => $context,
        ], 300); // 5分钟过期

        dp_log("{$operation}开始")
            ->context(array_merge($context, [
                'operation_id' => $operationId,
                'start_time'   => date('Y-m-d H:i:s', (int)$startTime),
            ]))
            ->info();

        return $operationId;
    }
}

if (!function_exists('dp_log_end')) {
    /**
     * 结束操作日志记录
     *
     * @param string $operationId 操作ID
     * @param string $status 操作状态: success|error|warning
     * @param array $context 结束时的上下文数据
     * @return void
     * @throws Throwable
     */
    function dp_log_end(string $operationId, string $status = 'success', array $context = []): void
    {
        $operationInfo = Cache::get("log_operation_$operationId");

        if (!$operationInfo) {
            dp_log("未知操作结束")->context(['operation_id' => $operationId, 'status' => $status])->warning();
            return;
        }

        $endTime  = microtime(true);
        $duration = round(($endTime - $operationInfo['start_time']) * 1000, 2); // 毫秒

        $logContext = array_merge($operationInfo['context'], $context, [
            'operation_id' => $operationId,
            'status'       => $status,
            'duration_ms'  => $duration,
            'end_time'     => date('Y-m-d H:i:s', (int)$endTime),
        ]);

        $statusText = match ($status) {
            'success' => '成功',
            'error' => '失败',
            'warning' => '警告',
            default => $status
        };

        dp_log("{$operationInfo['operation']}$statusText")
            ->context($logContext)
            ->level($status === 'success' ? 'info' : $status)
            ->$status();

        // 清除缓存
        Cache::delete("log_operation_$operationId");
    }
}

if (!function_exists('dp_log_exception')) {
    /**
     * 记录异常日志
     *
     * @param Throwable $exception 异常对象
     * @param string $operation 操作描述
     * @param array $context 额外上下文
     * @return void
     * @throws Throwable
     */
    function dp_log_exception(Throwable $exception, string $operation = '', array $context = []): void
    {
        $title = $operation ? "{$operation}发生异常" : "系统异常";

        $exceptionContext = [
            'exception_class'   => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code'    => $exception->getCode(),
            'exception_file'    => $exception->getFile(),
            'exception_line'    => $exception->getLine(),
        ];

        // 在开发环境下包含堆栈信息
        if (config('app.app_debug', false)) {
            $exceptionContext['exception_trace'] = $exception->getTraceAsString();
        }

        dp_log($title)
            ->type(Logger::TYPE_EXCEPTION)
            ->context(array_merge($context, $exceptionContext))
            ->error();
    }
}

if (!function_exists('dp_log_user_action')) {
    /**
     * 记录用户操作日志
     *
     * @param string $action 操作名称
     * @param array $data 操作数据
     * @param string $level 日志级别
     * @return void
     * @throws Throwable
     */
    function dp_log_user_action(string $action, array $data = [], string $level = 'info'): void
    {
        $context = [];

        // 添加操作数据
        if (!empty($data)) {
            $context['action_data'] = $data;
        }

        dp_log($action)
            ->type(Logger::TYPE_USER_ACTION)
            ->context($context)
            ->level($level)
            ->log();
    }
}

if (!function_exists('dp_log_security')) {
    /**
     * 记录安全相关日志
     *
     * @param string $event 安全事件
     * @param array $context 上下文信息
     * @param string $level 日志级别(默认warning)
     * @return void
     * @throws Throwable
     */
    function dp_log_security(string $event, array $context = [], string $level = 'warning'): void
    {
        $securityContext = [
            'security_event' => $event,
            'timestamp'      => time(),
            'request_method' => Request::method(),
            'request_url'    => Request::url(),
            'user_agent'     => Request::header('user-agent', ''),
            'ip_address'     => Request::ip(),
        ];

        // 添加会话信息
        if (session_id()) {
            $securityContext['session_id'] = session_id();
        }

        dp_log($event)
            ->type(Logger::TYPE_SECURITY)
            ->context(array_merge($securityContext, $context))
            ->level($level)
            ->log();
    }
}

if (!function_exists('dp_build_tree')) {
    /**
     * 构建树形结构(快捷方法)
     * @param array $data 原始数据
     * @param string $idField 主键字段名
     * @param string $parentField 父级ID字段名
     * @param string $childrenField 子级数据字段名
     * @param mixed $rootId 根节点的父级ID值
     * @return array
     */
    function dp_build_tree(
        array  $data,
        string $idField = 'id',
        string $parentField = 'parent_id',
        string $childrenField = 'children',
        mixed  $rootId = 0
    ): array
    {
        return TreeBuilder::make($data)
            ->setIdField($idField)
            ->setParentField($parentField)
            ->setChildrenField($childrenField)
            ->setRootId($rootId)
            ->build();
    }
}

if (!function_exists('dp_tree_builder')) {
    /**
     * 创建树形结构构建器实例
     * @param array $data 原始数据
     * @return TreeBuilder
     */
    function dp_tree_builder(array $data = []): TreeBuilder
    {
        return TreeBuilder::make($data);
    }
}

if (!function_exists('dp_tree_to_options')) {
    /**
     * 将树形结构转换为下拉选项格式
     * @param array $tree 树形结构数据
     * @param string $rootLabel 根节点标签(ID为0的选项文本)
     * @param array $excludeIds 需要排除的ID列表
     * @param string $idField ID字段名
     * @param string $nameField 名称字段名
     * @param string $childrenField 子节点字段名
     * @param int $level 当前层级(内部参数,递归时使用)
     * @return array 格式: [id => '缩进+名称', ...]
     */
    function dp_tree_to_options(
        array  $tree,
        string $rootLabel = '顶级',
        array  $excludeIds = [],
        string $idField = 'id',
        string $nameField = 'name',
        string $childrenField = 'children',
        int    $level = 0
    ): array
    {
        // 第一层级时添加根节点选项
        $options = $level === 0 ? [0 => $rootLabel] : [];

        foreach ($tree as $item) {
            // 排除指定的ID
            if (in_array($item[$idField] ?? 0, $excludeIds)) {
                continue;
            }

            // 生成缩进前缀
            $prefix                   = str_repeat('　', $level);
            $options[$item[$idField]] = $prefix . ($level > 0 ? '├─ ' : '') . $item[$nameField];

            // 递归处理子节点
            if (!empty($item[$childrenField])) {
                $childOptions = dp_tree_to_options(
                    $item[$childrenField],
                    $rootLabel,
                    $excludeIds,
                    $idField,
                    $nameField,
                    $childrenField,
                    $level + 1
                );
                // 移除子级递归中的根节点选项
                unset($childOptions[0]);
                $options = $options + $childOptions;
            }
        }

        return $options;
    }
}

// ========== 超级管理员保护相关函数 ==========

if (!function_exists('dp_is_super_admin')) {
    /**
     * 判断用户是否超级管理员
     * @param User|int|null $user 用户模型实例、用户ID或null(null表示当前登录用户)
     * @return bool
     */
    function dp_is_super_admin(User|int|null $user = null): bool
    {
        if ($user === null) {
            $currentUser = session(config('system.admin_session'));
            $user        = $currentUser['id'] ?? 0;
        }

        return SuperAdminProtection::isSuperAdmin($user);
    }
}

if (!function_exists('dp_is_super_role')) {
    /**
     * 判断角色是否超级管理员角色
     * @param Role|int $role 角色模型实例或角色ID
     * @return bool
     */
    function dp_is_super_role(Role|int $role): bool
    {
        return SuperAdminProtection::isSuperRole($role);
    }
}

if (!function_exists('dp_can_delete_user')) {
    /**
     * 检查用户是否可删除
     * @param User|int $user 用户模型实例或用户ID
     * @return bool
     */
    function dp_can_delete_user(User|int $user): bool
    {
        return SuperAdminProtection::canDeleteUser($user);
    }
}

if (!function_exists('dp_can_delete_role')) {
    /**
     * 检查角色是否可删除
     * @param Role|int $role 角色模型实例或角色ID
     * @return bool
     */
    function dp_can_delete_role(Role|int $role): bool
    {
        return SuperAdminProtection::canDeleteRole($role);
    }
}

if (!function_exists('dp_can_assign_super_role')) {
    /**
     * 检查当前用户是否有权分配超级管理员角色
     * @return bool
     */
    function dp_can_assign_super_role(): bool
    {
        return SuperAdminProtection::canAssignSuperRole();
    }
}

if (!function_exists('dp_join_array_column')) {
    /**
     * 将数组中的指定列合并为字符串
     * @param array $data
     * @param string $column
     * @param string $separator
     * @return string
     */
    function dp_join_array_column(array $data, string $column, string $separator = '、'): string
    {
        return implode($separator, array_column($data, $column));
    }
}

if (!function_exists('dp_compare_data_changes')) {
    /**
     * 比对数据变化，返回有差异的字段
     *
     * @param array $oldData 原始数据
     * @param array $newData 新数据
     * @param array $fieldLabels 字段标签映射 ['field' => '字段标题']
     * @param array $sensitiveFields 敏感字段列表（这些字段值会被脱敏为 ***）
     * @return array 变化的字段列表
     */
    function dp_compare_data_changes(
        array $oldData,
        array $newData,
        array $fieldLabels = [],
        array $sensitiveFields = ['password']
    ): array
    {
        $changes = [];

        foreach ($newData as $field => $newValue) {
            // 跳过不存在于旧数据中的字段，或者值没有变化的字段
            if (!array_key_exists($field, $oldData) || $oldData[$field] == $newValue) {
                continue;
            }

            $oldValue = $oldData[$field];

            // 处理敏感字段
            if (in_array($field, $sensitiveFields, true)) {
                $changes[$field] = [
                    'label' => $fieldLabels[$field] ?? $field,
                    'old'   => '***',
                    'new'   => '***'
                ];
            } else {
                $changes[$field] = [
                    'label' => $fieldLabels[$field] ?? $field,
                    'old'   => $oldValue,
                    'new'   => $newValue
                ];
            }
        }

        return $changes;
    }
}

if (!function_exists('dp_mask')) {
    /**
     * 数据脱敏函数
     *
     * @param string $data 要脱敏的数据
     * @param string $type 数据类型：phone(手机号)、idCard(身份证)、email(邮箱)、name(姓名)、默认为通用脱敏
     * @param string $maskChar 掩码字符，默认为 *
     * @param int $startMask 起始保留字符数（默认2）
     * @param int $endMask 结尾保留字符数（默认4）
     * @param string $encoding 字符编码，默认 UTF-8
     * @return string 脱敏后的字符串
     */
    function dp_mask(
        string $data,
        string $type = '',
        string $maskChar = '*',
        int    $startMask = 2,
        int    $endMask = 4,
        string $encoding = 'UTF-8'
    ): string
    {
        // 空数据直接返回
        if ($data === '') {
            return $data;
        }

        // 参数安全处理
        if ($startMask < 0 || $endMask < 0) {
            $startMask = max(0, $startMask);
            $endMask   = max(0, $endMask);
        }

        // 确保掩码字符是单字符
        if (mb_strlen($maskChar, $encoding) > 1) {
            $maskChar = mb_substr($maskChar, 0, 1, $encoding);
        }

        // 自动检测类型（按严格程度排序）
        if ($type === '') {
            if (preg_match('/^1[3-9]\d{9}$/', $data)) {
                $type = 'phone';
            } elseif (preg_match('/^\d{17}[\dXx]$/', $data)) {
                $type = 'idCard';
            } elseif (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $type = 'email';
            }
        }

        $length = mb_strlen($data, $encoding);

        switch ($type) {
            case 'phone':
                // 手机号：138****8000
                if ($length !== 11) {
                    return $data;
                }
                return mb_substr($data, 0, 3, $encoding)
                    . str_repeat($maskChar, 4)
                    . mb_substr($data, 7, 4, $encoding);

            case 'idCard':
                // 身份证：440524********1234
                if ($length !== 18) {
                    return $data;
                }
                return mb_substr($data, 0, 6, $encoding)
                    . str_repeat($maskChar, 8)
                    . mb_substr($data, 14, 4, $encoding);

            case 'email':
                // 邮箱：只脱敏用户名部分（性能优化：使用 strpos 替代 explode）
                $atPos = strpos($data, '@');
                if ($atPos === false || $atPos === 0) {
                    return $data;
                }

                $username       = substr($data, 0, $atPos);
                $domain         = substr($data, $atPos);
                $usernameLength = mb_strlen($username, $encoding);

                // 用户名太短，不脱敏
                if ($usernameLength <= 2) {
                    return $data;
                }

                // 短用户名（3-4字符）：只保留首字符，更安全 (abc -> a**)
                if ($usernameLength <= 4) {
                    $maskedUsername = mb_substr($username, 0, 1, $encoding)
                        . str_repeat($maskChar, $usernameLength - 1);
                } else {
                    // 长用户名（5+字符）：保留首尾 (abcdef -> a****f)
                    $maskedUsername = mb_substr($username, 0, 1, $encoding)
                        . str_repeat($maskChar, $usernameLength - 2)
                        . mb_substr($username, -1, null, $encoding);
                }

                return $maskedUsername . $domain;

            case 'name':
                // 姓名：保留首字符
                if ($length <= 1) {
                    return $data;
                }
                return mb_substr($data, 0, 1, $encoding)
                    . str_repeat($maskChar, $length - 1);

            default:
                // 通用脱敏：保留首尾字符
                if ($length <= $startMask + $endMask) {
                    return $data;
                }

                $maskedLength = $length - $startMask - $endMask;
                return mb_substr($data, 0, $startMask, $encoding)
                    . str_repeat($maskChar, $maskedLength)
                    . mb_substr($data, -$endMask, null, $encoding);
        }
    }
}

if (!function_exists('dp_crud_token_encode')) {
    /**
     * 生成 CRUD 操作的表名 Token
     * 用于安全地在 URL 中传递表名信息，防止参数篡改
     *
     * @param string $tableName 表名（不含前缀）或完整表名
     * @param string $type 类型：name(不含前缀) 或 table(完整表名)
     * @param int $expire Token 有效期（秒），默认 3600（1小时）
     * @param array $meta 附加元数据
     * @return string 加密后的 token 字符串
     */
    function dp_crud_token_encode(string $tableName, string $type = 'name', int $expire = 3600, array $meta = []): string
    {
        $config = [
            'type' => $type,
            $type  => $tableName,
            'time' => time(),
        ];
        if (!empty($meta)) {
            $config['meta'] = $meta;
        }

        // 生成 token（混合 app_key 增加安全性）
        $token = md5(json_encode($config) . Config::get('app.app_key', 'dolphinphp'));

        // 缓存配置
        Cache::set('crud_table_token:' . $token, $config, $expire);

        return $token;
    }
}

if (!function_exists('dp_crud_token_decode')) {
    /**
     * 解密 CRUD Token，获取表名配置
     *
     * @param string $token Token 字符串
     * @return array|null 返回表名配置数组，格式：['type' => 'name|table', 'name|table' => '表名', 'time' => 时间戳]
     *                    Token 无效或过期返回 null
     */
    function dp_crud_token_decode(string $token): ?array
    {
        $config = Cache::get('crud_table_token:' . $token);

        if (!$config) {
            return null;
        }

        // 检查是否过期（1小时）
        if ((time() - $config['time']) > 3600) {
            Cache::delete('crud_table_token:' . $token);
            return null;
        }

        return $config;
    }
}

// +----------------------------------------------------------------------
// | 用户上下文助手函数
// +----------------------------------------------------------------------

if (!function_exists('dp_current_user_id')) {
    /**
     * 获取当前登录用户ID
     *
     * @return int 用户ID，未登录返回 0
     */
    function dp_current_user_id(): int
    {
        $userContext = app(UserContext::class);
        return $userContext->getUserId();
    }
}

if (!function_exists('dp_current_user')) {
    /**
     * 获取当前登录用户模型
     *
     * @return User|null
     */
    function dp_current_user(): ?User
    {
        $userContext = app(UserContext::class);
        return $userContext->getUser();
    }
}

if (!function_exists('dp_is_logged_in')) {
    /**
     * 判断用户是否已登录
     *
     * @return bool
     */
    function dp_is_logged_in(): bool
    {
        $userContext = app(UserContext::class);
        return $userContext->isLoggedIn();
    }
}

// +----------------------------------------------------------------------
// | 权限判断助手函数
// +----------------------------------------------------------------------

if (!function_exists('dp_has_permission')) {
    /**
     * 判断当前用户是否拥有指定权限
     *
     * @param string|array $code 权限标识（支持字符串或数组）
     * @param string $logic 多权限逻辑关系：or（任一）、and（全部）
     * @return bool
     */
    function dp_has_permission(string|array $code, string $logic = 'or'): bool
    {
        $userId = dp_current_user_id();

        // 未登录用户无权限
        if ($userId == 0) {
            return false;
        }

        // 超级管理员拥有所有权限
        if (dp_is_super_admin($userId)) {
            return true;
        }

        try {
            $permissionService = app(PermissionService::class);
            return $permissionService->hasPermission($userId, $code, $logic);
        } catch (Throwable $e) {
            // 发生异常时，记录日志并返回false（安全第一）
            Log::error('权限判断异常: ' . $e->getMessage(), [
                'user_id' => $userId,
                'code'    => $code,
                'logic'   => $logic,
            ]);
            return false;
        }
    }
}

if (!function_exists('dp_user_permissions')) {
    /**
     * 获取当前用户的权限列表
     *
     * @return array 权限标识数组
     */
    function dp_user_permissions(): array
    {
        $userId = dp_current_user_id();

        // 未登录用户无权限
        if ($userId == 0) {
            return [];
        }

        try {
            $permissionService = app(PermissionService::class);
            return $permissionService->getUserPermissionCodes($userId);
        } catch (Throwable $e) {
            Log::error('获取用户权限异常: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
            return [];
        }
    }
}

if (!function_exists('dp_check_auth')) {
    /**
     * 快速权限检查（用于模板）
     *
     * 用法：{:dp_check_auth('user.edit') ? '显示' : '隐藏'}
     *
     * @param string|array $code 权限标识
     * @param string $logic 逻辑关系
     * @return bool
     */
    function dp_check_auth(string|array $code, string $logic = 'or'): bool
    {
        return dp_has_permission($code, $logic);
    }
}

if (!function_exists('dp_generate_auth_code')) {
    /**
     * 生成权限标识
     *
     * 根据应用名、控制器名和操作名生成标准权限标识
     * 格式：{应用名}.{控制器名}.{操作名}
     *
     * @param string $action 操作名（如：add、edit、delete）
     * @param string|null $controller 控制器名（为null时自动获取当前控制器）
     * @param string|null $app 应用名（为null时自动获取当前应用）
     * @return string|null 权限标识（如：admin.user.add），失败返回null
     *
     * @example
     * dp_generate_auth_code('add') // 当前控制器和应用：admin.user.add
     * dp_generate_auth_code('edit', 'User') // 指定控制器：admin.user.edit
     * dp_generate_auth_code('delete', 'Role', 'admin') // 完全指定：admin.role.delete
     */
    function dp_generate_auth_code(string $action, ?string $controller = null, ?string $app = null): ?string
    {
        try {
            // 获取应用名（自动或指定）
            if ($app === null) {
                $app = strtolower(app('http')->getName());
            } else {
                $app = strtolower($app);
            }

            // 获取控制器名（自动或指定）
            if ($controller === null) {
                $controller = dp_normalize_auth_controller(request()->controller());
            } else {
                $controller = dp_normalize_auth_controller($controller);
            }

            // 操作名（驼峰转下划线）
            $action = parse_name($action);

            // 返回格式：应用名.控制器名.操作名
            return implode('.', [$app, $controller, $action]);
        } catch (Throwable) {
            // 发生异常时返回null
            return null;
        }
    }
}

if (!function_exists('dp_normalize_auth_controller')) {
    /**
     * 归一化权限用控制器标识
     *
     * 示例：
     * - Product => product
     * - admin.Product => admin.product
     * - admin\Product => admin.product
     * - admin/product => admin.product
     *
     * @param string $controller
     * @return string
     */
    function dp_normalize_auth_controller(string $controller): string
    {
        $controller = trim($controller);
        if ($controller === '') {
            return '';
        }

        $controller = str_replace(['\\', '/'], '.', $controller);
        $segments   = array_values(array_filter(explode('.', $controller), static fn(string $item): bool => $item !== ''));

        $segments = array_map(static fn(string $segment): string => parse_name($segment), $segments);

        return implode('.', $segments);
    }
}

if (!function_exists('dp_resolve_controller_class')) {
    /**
     * 解析控制器完整类名
     *
     * 示例：
     * - product => app\store\controller\Product
     * - admin.Product => app\store\controller\admin\Product
     * - admin/product => app\store\controller\admin\Product
     *
     * @param string $controller
     * @param string|null $appName
     * @param string|null $layer
     * @return string
     */
    function dp_resolve_controller_class(string $controller, ?string $appName = null, ?string $layer = null): string
    {
        $controller = trim($controller);
        $appName    = $appName ?: (string)(app('http')->getName() ?: Config::get('app.default_app', 'index'));
        $layer      = $layer ?: (string)Config::get('route.controller_layer', 'controller');

        $controller = str_replace(['/', '.'], '\\', trim($controller, '\\/'));
        $segments   = array_values(array_filter(explode('\\', $controller), static fn(string $item): bool => $item !== ''));

        if ($segments === []) {
            $segments = [(string)Config::get('route.default_controller', 'Index')];
        }

        $class = Str::studly(array_pop($segments));
        $path  = $segments ? implode('\\', $segments) . '\\' : '';

        return 'app\\' . trim($appName, '\\/') . '\\' . trim($layer, '\\/') . '\\' . $path . $class;
    }
}

if (!function_exists('dp_resolve_crud_table_name')) {
    /**
     * 根据控制器标识解析 CRUD 表名
     *
     * 规则：
     * - 仅使用控制器最后一段作为表名主体
     * - 自动转为下划线命名
     * - 自动拼接应用名，不包含后台子目录
     *
     * 示例：
     * - Product => store_product
     * - admin.ProductVersion => store_product_version
     * - admin/product_version => store_product_version
     *
     * @param string $controller
     * @param string|null $appName
     * @return string
     */
    function dp_resolve_crud_table_name(string $controller, ?string $appName = null): string
    {
        $appName        = $appName ?: (string)(app('http')->getName() ?: Config::get('app.default_app', 'index'));
        $normalized     = dp_normalize_auth_controller($controller);
        $segments       = array_values(array_filter(explode('.', $normalized), static fn(string $item): bool => $item !== ''));
        $controllerName = array_pop($segments) ?: (string)Config::get('route.default_controller', 'index');

        return trim($appName, '\\/') . '_' . $controllerName;
    }
}

if (!function_exists('dp_filter_buttons_by_auth')) {
    /**
     * 根据权限过滤按钮配置
     *
     * 用于表格工具栏、右侧操作按钮等场景的权限过滤
     *
     * @param array $buttons 按钮配置数组
     * @return array 过滤后的按钮配置
     *
     * @example
     * // 基本用法
     * $buttons = [
     *     ['title' => '编辑', 'auth' => 'admin.user.edit'],
     *     ['title' => '删除', 'auth' => 'admin.user.delete'],
     * ];
     * $filtered = dp_filter_buttons_by_auth($buttons);
     *
     * // 多权限判断
     * $button = [
     *     'title' => '审核',
     *     'auth' => ['admin.user.edit', 'admin.user.view'],
     *     'auth_logic' => 'and'  // 需要同时拥有两个权限
     * ];
     */
    function dp_filter_buttons_by_auth(array $buttons): array
    {
        $filtered = [];

        foreach ($buttons as $key => $button) {
            // 如果配置中有auth字段，检查权限
            if (isset($button['auth'])) {
                $auth  = $button['auth'];
                $logic = $button['auth_logic'] ?? 'or';

                // auth为null或空字符串表示无权限限制
                if ($auth !== '' && $auth !== null && !dp_has_permission($auth, $logic)) {
                    continue;
                }

                // 移除auth配置（前端不需要）
                unset($button['auth']);
                unset($button['auth_logic']);
            }

            $filtered[$key] = $button;
        }

        return array_values($filtered);
    }
}

if (!function_exists('dp_is_url')) {
    /**
     * 判断字符串是否为 URL
     * @param string $url 字符串
     * @return bool
     */
    function dp_is_url(string $url): bool
    {
        return stripos($url, 'http://') === 0 ||
            stripos($url, 'https://') === 0 ||
            str_starts_with($url, '//');
    }
}
