<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\install\service;

/**
 * 安装向导步骤状态服务
 */
class InstallWizardStateService
{
    private const SESSION_KEY = 'install.wizard';

    /**
     * 保存步骤数据
     * @param string $step
     * @param array<string, mixed> $payload
     * @return void
     */
    public function saveStep(string $step, array $payload): void
    {
        $state = session(self::SESSION_KEY);
        $state = is_array($state) ? $state : [];
        $state[$step] = $payload;

        session(self::SESSION_KEY, $state);
    }

    /**
     * 获取步骤数据
     * @param string $step
     * @return array<string, mixed>
     */
    public function step(string $step): array
    {
        $state = session(self::SESSION_KEY);
        $state = is_array($state) ? $state : [];

        return (array)($state[$step] ?? []);
    }

    /**
     * 判断步骤是否已完成
     * @param string $step
     * @return bool
     */
    public function hasStep(string $step): bool
    {
        return $this->step($step) !== [];
    }

    /**
     * 清理安装状态
     * @return void
     */
    public function clear(): void
    {
        session(self::SESSION_KEY, null);
    }
}
