<?php
declare(strict_types=1);

namespace app\common\plugin;

use Throwable;

/**
 * Hook / Filter 管理器
 */
class HookManager
{
    /**
     * @var array<string, array<int, array{priority:int, listener:callable}>>
     */
    private array $actions = [];

    /**
     * @var array<string, array<int, array{priority:int, listener:callable}>>
     */
    private array $filters = [];

    /**
     * 注册 Action Hook
     * @param string $hook
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function registerAction(string $hook, callable $listener, int $priority = 50): void
    {
        $this->actions[$hook][] = [
            'priority' => $priority,
            'listener' => $listener,
        ];
    }

    /**
     * 注册 Filter Hook
     * @param string $hook
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function registerFilter(string $hook, callable $listener, int $priority = 50): void
    {
        $this->filters[$hook][] = [
            'priority' => $priority,
            'listener' => $listener,
        ];
    }

    /**
     * 触发 Action
     * @param string $hook
     * @param array<string, mixed> $context
     * @return void
     */
    public function doAction(string $hook, array $context = []): void
    {
        foreach ($this->sorted($this->actions[$hook] ?? []) as $entry) {
            try {
                ($entry['listener'])($context);
            } catch (Throwable) {
                // Hook 失败时不影响主流程。
            }
        }
    }

    /**
     * 应用 Filter
     * @param string $hook
     * @param mixed $value
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function applyFilters(string $hook, mixed $value, array $context = []): mixed
    {
        foreach ($this->sorted($this->filters[$hook] ?? []) as $entry) {
            try {
                $value = ($entry['listener'])($value, $context);
            } catch (Throwable) {
                continue;
            }
        }

        return $value;
    }

    /**
     * 排序监听器
     * @param array<int, array{priority:int, listener:callable}> $entries
     * @return array<int, array{priority:int, listener:callable}>
     */
    private function sorted(array $entries): array
    {
        usort($entries, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
        return $entries;
    }
}
