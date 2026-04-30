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
 * 安装执行进度服务
 */
class InstallProgressService
{
    private const SESSION_KEY = 'install.progress';

    /**
     * 初始化或获取当前进度
     * @param array<int, array{key:string,label:string}> $definitions
     * @return array<string, mixed>
     */
    public function ensure(array $definitions): array
    {
        $state = $this->state();
        if ($state === [] || !$this->sameStepKeys($state, $definitions)) {
            $state = [
                'steps'            => array_map(static fn(array $item): array => [
                    'key'     => (string)$item['key'],
                    'label'   => (string)$item['label'],
                    'status'  => 'pending',
                    'message' => '',
                ], $definitions),
                'progress_percent' => 0,
                'finished'         => false,
                'has_error'        => false,
                'current_step_key' => null,
            ];
            $this->store($state);
        }

        return $state;
    }

    /**
     * 获取当前状态
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $state = session(self::SESSION_KEY);

        return is_array($state) ? $state : [];
    }

    /**
     * 获取下一个待执行步骤
     * @return string|null
     */
    public function nextStepKey(): ?string
    {
        $state = $this->state();
        foreach ((array)($state['steps'] ?? []) as $step) {
            if (($step['status'] ?? '') !== 'complete') {
                return (string)($step['key'] ?? '');
            }
        }

        return null;
    }

    /**
     * 标记步骤开始执行
     * @param string $key
     * @return array<string, mixed>
     */
    public function markRunning(string $key): array
    {
        $state = $this->state();
        foreach ((array)($state['steps'] ?? []) as $index => $step) {
            if (($step['key'] ?? '') !== $key) {
                continue;
            }

            $state['steps'][$index]['status']  = 'running';
            $state['steps'][$index]['message'] = '';
            $state['current_step_key']         = $key;
            $state['has_error']                = false;
            $state['finished']                 = false;
            break;
        }

        return $this->storeAndReturn($state);
    }

    /**
     * 标记步骤执行完成
     * @param string $key
     * @param string $message
     * @return array<string, mixed>
     */
    public function markComplete(string $key, string $message = ''): array
    {
        $state = $this->state();
        foreach ((array)($state['steps'] ?? []) as $index => $step) {
            if (($step['key'] ?? '') !== $key) {
                continue;
            }

            $state['steps'][$index]['status']  = 'complete';
            $state['steps'][$index]['message'] = $message;
            break;
        }

        $state['current_step_key'] = null;
        $state['has_error']        = false;

        return $this->storeAndReturn($state);
    }

    /**
     * 标记步骤执行失败
     * @param string $key
     * @param string $message
     * @return array<string, mixed>
     */
    public function markError(string $key, string $message): array
    {
        $state = $this->state();
        foreach ((array)($state['steps'] ?? []) as $index => $step) {
            if (($step['key'] ?? '') !== $key) {
                continue;
            }

            $state['steps'][$index]['status']  = 'error';
            $state['steps'][$index]['message'] = $message;
            break;
        }

        $state['current_step_key'] = null;
        $state['has_error']        = true;
        $state['finished']         = false;

        return $this->storeAndReturn($state);
    }

    /**
     * 标记整个安装流程已完成
     * @return array<string, mixed>
     */
    public function markFinished(): array
    {
        $state = $this->state();
        $state['finished']         = true;
        $state['has_error']        = false;
        $state['current_step_key'] = null;

        return $this->storeAndReturn($state);
    }

    /**
     * 清理进度状态
     * @return void
     */
    public function reset(): void
    {
        session(self::SESSION_KEY, null);
    }

    /**
     * @param array<string, mixed> $state
     * @param array<int, array{key:string,label:string}> $definitions
     * @return bool
     */
    private function sameStepKeys(array $state, array $definitions): bool
    {
        $currentKeys = array_map(static fn(array $item): string => (string)($item['key'] ?? ''), (array)($state['steps'] ?? []));
        $targetKeys  = array_map(static fn(array $item): string => (string)($item['key'] ?? ''), $definitions);

        return $currentKeys === $targetKeys;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function storeAndReturn(array $state): array
    {
        $this->recalculate($state);
        $this->store($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return void
     */
    private function recalculate(array &$state): void
    {
        $steps        = (array)($state['steps'] ?? []);
        $total        = count($steps);
        $completed    = count(array_filter($steps, static fn(array $step): bool => ($step['status'] ?? '') === 'complete'));
        $state['progress_percent'] = $total === 0 ? 0 : (int)round(($completed / $total) * 100);
    }

    /**
     * @param array<string, mixed> $state
     * @return void
     */
    private function store(array $state): void
    {
        session(self::SESSION_KEY, $state);
    }
}
