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

namespace app\common\service;

/**
 * 后台安全策略服务
 */
final class AdminSecurityPolicyService
{
    public function getLoginMaxRetries(): int
    {
        return $this->normalizeNonNegativeInt(dp_setting('security.login_max_retries', 5), 5);
    }

    public function getLoginLockMinutes(): int
    {
        return $this->normalizeNonNegativeInt(dp_setting('security.login_lock_minutes', 30), 30);
    }

    public function getIdleLogoutMinutes(): int
    {
        return $this->normalizeNonNegativeInt(dp_setting('security.idle_logout_minutes', 15), 15);
    }

    public function getPasswordExpireDays(): int
    {
        return $this->normalizeNonNegativeInt(dp_setting('security.password_expire_days', 90), 90);
    }

    private function normalizeNonNegativeInt(mixed $value, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        $normalized = (int)$value;
        return $normalized >= 0 ? $normalized : $default;
    }
}
