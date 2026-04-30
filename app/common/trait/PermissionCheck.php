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

namespace app\common\trait;

use app\common\interface\PermissionService;
use app\common\service\UserContext;
use Exception;
use think\facade\Log;
use Throwable;

/**
 * 权限检查 Trait
 * 提供便捷的权限检查方法，简化控制器中的权限判断代码
 *
 * @package app\common\trait
 */
trait PermissionCheck
{
    /**
     * 权限服务实例
     * @var PermissionService|null
     */
    private ?PermissionService $permissionService = null;

    /**
     * 用户上下文服务实例
     * @var UserContext|null
     */
    private ?UserContext $userContext = null;

    /**
     * 获取用户上下文服务实例
     *
     * @return UserContext
     */
    protected function getUserContext(): UserContext
    {
        if (!isset($this->userContext)) {
            $this->userContext = app(UserContext::class);
        }
        return $this->userContext;
    }

    /**
     * 获取当前用户ID
     *
     * @return int
     */
    protected function getCurrentUserId(): int
    {
        return $this->getUserContext()->getUserId();
    }

    /**
     * 检查权限，无权限时自动调用 error() 返回错误
     *
     * 这是最推荐使用的权限检查方法。
     * 注意：此方法会调用 $this->error()，该方法会抛出 HttpResponseException，
     * 因此不能用 try-catch 包裹此方法。
     *
     * @param string|array $code 权限标识（支持字符串或数组）
     * @param string $logic 多权限逻辑关系：or（任一）、and（全部）
     * @param string $message 自定义错误消息
     * @return void
     *
     * @example
     * // 单权限检查
     * $this->checkPermission('user.edit');
     *
     * // 多权限检查（任一满足）
     * $this->checkPermission(['user.edit', 'user.view'], 'or');
     *
     * // 多权限检查（必须全部拥有）
     * $this->checkPermission(['user.edit', 'user.delete'], 'and', '需要编辑和删除权限');
     */
    protected function checkPermission(
        string|array $code,
        string       $logic = 'or',
        string       $message = '您没有权限执行此操作'
    ): void
    {
        if (!$this->hasPermission($code, $logic)) {
            $this->error($message);
        }
    }

    /**
     * 要求权限，无权限时抛出异常
     *
     * 与 checkPermission() 不同，此方法抛出普通异常，可以被 try-catch 捕获。
     * 适用于需要自定义异常处理逻辑的场景。
     *
     * @param string|array $code 权限标识
     * @param string $logic 多权限逻辑关系
     * @param string $message 异常消息
     * @return void
     * @throws Exception
     *
     * @example
     * try {
     *     $this->requirePermission('admin.dangerous');
     *     // 执行危险操作...
     * } catch (\Exception $e) {
     *     dp_log_security('危险操作被拒绝', ['reason' => $e->getMessage()]);
     *     $this->error($e->getMessage());
     * }
     */
    protected function requirePermission(
        string|array $code,
        string       $logic = 'or',
        string       $message = '权限不足'
    ): void
    {
        if (!$this->hasPermission($code, $logic)) {
            throw new Exception($message);
        }
    }

    /**
     * 判断当前用户是否拥有指定权限
     *
     * 返回布尔值，适用于条件判断场景。
     *
     * @param string|array $code 权限标识（支持字符串或数组）
     * @param string $logic 多权限逻辑关系：or（任一）、and（全部）
     * @return bool
     *
     * @example
     * // 根据权限显示不同内容
     * if ($this->hasPermission('user.export')) {
     *     $this->table->toolbar(['add', 'delete', 'export']);
     * } else {
     *     $this->table->toolbar(['add', 'delete']);
     * }
     */
    protected function hasPermission(string|array $code, string $logic = 'or'): bool
    {
        $userId = $this->getCurrentUserId();

        // 未登录用户无权限
        if ($userId == 0) {
            return false;
        }

        // 超级管理员拥有所有权限
        if (dp_is_super_admin($userId)) {
            return true;
        }

        try {
            return $this->getPermissionService()->hasPermission($userId, $code, $logic);
        } catch (Throwable $e) {
            Log::error('权限判断异常: ' . $e->getMessage(), [
                'user_id' => $userId,
                'code'    => $code,
                'logic'   => $logic,
            ]);
            return false;
        }
    }

    /**
     * 要求超级管理员权限
     *
     * 适用于系统级别的敏感操作，仅超级管理员可访问。
     *
     * @param string $message 错误消息
     * @return void
     *
     * @example
     * $this->requireSuperAdmin('系统配置仅超级管理员可访问');
     */
    protected function requireSuperAdmin(string $message = '此操作仅限超级管理员'): void
    {
        if (!dp_is_super_admin($this->getCurrentUserId())) {
            $this->error($message);
        }
    }

    /**
     * 获取当前用户的权限列表
     *
     * 返回当前用户拥有的所有权限标识数组。
     *
     * @return array 权限标识数组
     *
     * @example
     * $permissions = $this->getCurrentPermissions();
     * // 返回：['user.index', 'user.edit', 'role.index', ...]
     */
    protected function getCurrentPermissions(): array
    {
        $userId = $this->getCurrentUserId();

        if ($userId == 0) {
            return [];
        }

        try {
            return $this->getPermissionService()->getUserPermissionCodes($userId);
        } catch (Throwable $e) {
            Log::error('获取用户权限异常: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
            return [];
        }
    }

    /**
     * 获取权限服务实例
     *
     * 使用单例模式，避免重复获取服务实例。
     *
     * @return PermissionService
     */
    protected function getPermissionService(): PermissionService
    {
        if (!isset($this->permissionService)) {
            $this->permissionService = app(PermissionService::class);
        }

        return $this->permissionService;
    }
}
