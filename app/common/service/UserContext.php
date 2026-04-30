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

use app\common\model\User as UserModel;
use think\facade\Session;

/**
 * 用户上下文服务
 *
 * 提供当前登录用户信息的统一访问接口，替代全局 UID 常量
 *
 * @package app\common\service
 */
class UserContext
{
    /**
     * 当前用户ID（缓存）
     * @var int|null
     */
    private ?int $userId = null;

    /**
     * 当前用户模型（缓存）
     * @var UserModel|null
     */
    private ?UserModel $user = null;

    /**
     * 是否已初始化
     * @var bool
     */
    private bool $initialized = false;

    /**
     * 获取当前用户ID
     *
     * @return int 用户ID，未登录返回 0
     */
    public function getUserId(): int
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->userId ?? 0;
    }

    /**
     * 获取当前用户模型
     *
     * ⚠️ 注意：此方法会缓存用户模型，如果需要最新状态，请使用 refreshUser()
     *
     * @return UserModel|null
     */
    public function getUser(): ?UserModel
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($this->user === null && $this->userId > 0) {
            $this->user = UserModel::find($this->userId);

            // 安全检查：验证用户状态
            if ($this->user && $this->user['status'] == 0) {
                // 用户已被禁用，清除上下文
                dp_log_security('尝试获取已禁用用户的模型', [
                    'user_id' => $this->userId,
                    'username' => $this->user['username'],
                    'action' => '自动清除用户上下文'
                ], 'warning');

                $this->clearInternal();
                return null;
            }
        }

        return $this->user;
    }

    /**
     * 刷新用户模型（从数据库重新加载）
     *
     * @return UserModel|null
     */
    public function refreshUser(): ?UserModel
    {
        $this->user = null; // 清除缓存
        return $this->getUser();
    }

    /**
     * 判断是否已登录
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->getUserId() > 0;
    }

    /**
     * 判断是否超级管理员
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return dp_is_super_admin($this->getUserId());
    }

    /**
     * 设置当前用户ID（仅供框架内部使用）
     *
     * ⚠️ 安全警告：此方法仅应在用户登录验证通过后调用
     * 不要在业务代码中直接调用此方法，否则可能导致越权漏洞
     *
     * @internal 仅供 Auth 控制器和测试代码使用
     * @param int $userId
     * @return void
     * @throws \RuntimeException 如果调用者不合法
     */
    public function setUserId(int $userId): void
    {
        // 安全检查：验证调用者
        $this->validateCaller();

        $this->userId = $userId;
        $this->user = null; // 清除用户模型缓存
        $this->initialized = true;
    }

    /**
     * 验证调用者是否合法
     * 只允许 Auth 控制器和测试代码调用 setUserId
     *
     * ⚠️ 重要：只检查直接调用者，不检查整个调用栈
     *
     * @return void
     * @throws \RuntimeException
     */
    private function validateCaller(): void
    {
        // 获取调用栈（只需要前2层）
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // 允许的调用者类
        $allowedCallers = [
            'app\\admin\\controller\\Auth',  // Auth 控制器
            'tests\\',                        // 测试代码（所有 tests 命名空间下的类）
        ];

        // 检查直接调用者（trace[2] 是调用 setUserId 的类）
        if (isset($trace[2]['class'])) {
            $callerClass = $trace[2]['class'];

            foreach ($allowedCallers as $allowed) {
                if (str_starts_with($callerClass, $allowed)) {
                    return; // 合法调用者
                }
            }
        }

        // 如果没有找到合法调用者，记录安全日志并抛出异常
        $callerInfo = $trace[2] ?? [];
        dp_log_security('非法调用 UserContext::setUserId()', [
            'caller_class' => $callerInfo['class'] ?? 'unknown',
            'caller_method' => $callerInfo['function'] ?? 'unknown',
            'file' => $trace[0]['file'] ?? 'unknown',
            'line' => $trace[0]['line'] ?? 0,
        ], 'error');

        throw new \RuntimeException(
            '安全错误：UserContext::setUserId() 只能由 Auth 控制器调用。' .
            '如需切换用户，请使用正确的登录流程。'
        );
    }

    /**
     * 清除用户上下文（用于退出登录）
     *
     * ⚠️ 安全警告：此方法会清除当前用户上下文
     * 只应在退出登录或安全检查失败时调用
     *
     * @return void
     */
    public function clear(): void
    {
        // 安全检查：验证调用者（防止恶意清除）
        $this->validateClearCaller();

        $this->clearInternal();
    }

    /**
     * 内部清除方法（不进行安全检查）
     * 供 initialize() 和 getUser() 等内部方法使用
     *
     * @return void
     */
    private function clearInternal(): void
    {
        $this->userId = null;
        $this->user = null;
        $this->initialized = false;
    }

    /**
     * 验证 clear() 方法的调用者
     * 只允许特定的调用者清除用户上下文
     *
     * @return void
     * @throws \RuntimeException
     */
    private function validateClearCaller(): void
    {
        // 获取调用栈（只需要前2层）
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // 允许的调用者类和方法
        $allowedCallers = [
            'app\\admin\\controller\\Auth',      // Auth 控制器
            'app\\admin\\controller\\Login',     // Login 控制器
            'tests\\',                            // 测试代码
        ];

        // 检查直接调用者（trace[2] 是调用 clear 的类）
        if (isset($trace[2]['class'])) {
            $callerClass = $trace[2]['class'];

            foreach ($allowedCallers as $allowed) {
                if (str_starts_with($callerClass, $allowed)) {
                    return; // 合法调用者
                }
            }
        }

        // 如果没有找到合法调用者，记录安全日志并抛出异常
        $callerInfo = $trace[2] ?? [];
        dp_log_security('非法调用 UserContext::clear()', [
            'caller_class' => $callerInfo['class'] ?? 'unknown',
            'caller_method' => $callerInfo['function'] ?? 'unknown',
            'file' => $trace[0]['file'] ?? 'unknown',
            'line' => $trace[0]['line'] ?? 0,
        ], 'error');

        throw new \RuntimeException(
            '安全错误：UserContext::clear() 只能由授权的代码调用。' .
            '不要在业务代码中随意清除用户上下文。'
        );
    }

    /**
     * 初始化用户上下文
     * 从 Session 中读取用户信息并验证有效性
     *
     * @return void
     */
    private function initialize(): void
    {
        $sessionData = Session::get(config('system.admin_session'));
        $userId = $sessionData['id'] ?? 0;

        // 安全检查：验证用户 ID 的有效性
        if ($userId > 0) {
            // 验证用户是否存在且状态正常
            $user = UserModel::find($userId);

            if (!$user) {
                // 用户不存在，清除 Session（可能是被删除的用户）
                Session::delete(config('system.admin_session'));
                dp_log_security('Session 中的用户不存在', [
                    'user_id' => $userId,
                    'action' => '自动清除 Session'
                ], 'warning');
                $userId = 0;
            } elseif ($user['status'] == 0) {
                // 用户被禁用，清除 Session
                Session::delete(config('system.admin_session'));
                dp_log_security('Session 中的用户已被禁用', [
                    'user_id' => $userId,
                    'username' => $user['username'],
                    'action' => '自动清除 Session'
                ], 'warning');
                $userId = 0;
            }
        }

        $this->userId = $userId;
        $this->initialized = true;
    }

    /**
     * 手动设置用户模型（仅供测试使用）
     *
     * ⚠️ 安全警告：此方法仅应在测试代码中使用
     * 不要在业务代码中直接调用此方法，否则可能导致越权漏洞
     *
     * @internal 仅供测试代码使用
     * @param UserModel|null $user
     * @return void
     * @throws \RuntimeException 如果调用者不合法
     */
    public function setUser(?UserModel $user): void
    {
        // 安全检查：只允许测试代码调用
        $this->validateTestCaller();

        $this->user = $user;
        $this->userId = $user?->id ?? 0;
        $this->initialized = true;
    }

    /**
     * 验证调用者是否为测试代码
     *
     * @return void
     * @throws \RuntimeException
     */
    private function validateTestCaller(): void
    {
        // 获取调用栈（只需要前2层）
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // 检查直接调用者（trace[2] 是调用 setUser 的类）
        if (isset($trace[2]['class'])) {
            $callerClass = $trace[2]['class'];

            // 只允许测试代码调用
            if (str_starts_with($callerClass, 'tests\\')) {
                return; // 合法调用者
            }
        }

        // 如果没有找到测试代码，记录安全日志并抛出异常
        $callerInfo = $trace[2] ?? [];
        dp_log_security('非法调用 UserContext::setUser()', [
            'caller_class' => $callerInfo['class'] ?? 'unknown',
            'caller_method' => $callerInfo['function'] ?? 'unknown',
            'file' => $trace[0]['file'] ?? 'unknown',
            'line' => $trace[0]['line'] ?? 0,
        ], 'error');

        throw new \RuntimeException(
            '安全错误：UserContext::setUser() 只能在测试代码中调用。' .
            '业务代码不应直接设置用户上下文。'
        );
    }

    /**
     * 重置初始化状态（用于测试）
     *
     * @return void
     */
    public function reset(): void
    {
        $this->clearInternal();
    }
}
