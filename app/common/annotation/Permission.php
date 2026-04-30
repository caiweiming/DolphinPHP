<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\annotation;

use Attribute;
use InvalidArgumentException;

/**
 * 权限验证注解
 * 用于在控制器方法上声明权限需求
 *
 * @package app\common\annotation
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Permission
{
    /**
     * 构造函数
     *
     * @param string|array $code 权限标识（必填），支持字符串或数组
     * @param string $name 权限名称（用于错误提示），为空时使用权限标识
     * @param string $logic 多权限逻辑关系：or（满足任一）、and（必须全部拥有）
     * @param string $mode 验证模式：check（仅检查权限）、data（同时应用数据权限）
     * @param bool $dataScope 是否启用数据权限过滤
     * @param string $resource 资源类型（数据权限使用）
     * @param string $userIdField 用户ID字段名（数据权限使用）
     * @param string $departmentIdField 部门ID字段名（数据权限使用）
     */
    public function __construct(
        public string|array $code,
        public string       $name = '',
        public string       $logic = 'or',
        public string       $mode = 'check',
        public bool         $dataScope = false,
        public string       $resource = '',
        public string       $userIdField = 'user_id',
        public string       $departmentIdField = 'department_id'
    )
    {
        // 验证权限标识
        if (empty($this->code)) {
            throw new InvalidArgumentException('权限标识不能为空');
        }

        // 标准化权限标识为数组
        if (is_string($this->code)) {
            $this->code = [$this->code];
        }

        // 验证逻辑关系
        $validLogic = ['or', 'and'];
        if (!in_array($this->logic, $validLogic)) {
            $this->logic = 'or';
        }

        // 验证模式
        $validMode = ['check', 'data'];
        if (!in_array($this->mode, $validMode)) {
            $this->mode = 'check';
        }

        // 如果mode为data，自动启用dataScope
        if ($this->mode === 'data') {
            $this->dataScope = true;
        }

        // 如果没有指定名称，使用第一个权限标识
        if (empty($this->name)) {
            $this->name = is_array($this->code) ? $this->code[0] : $this->code;
        }

        // 如果启用了数据权限但没有指定资源类型，抛出异常
        if ($this->dataScope && empty($this->resource)) {
            throw new InvalidArgumentException('启用数据权限时必须指定resource参数');
        }
    }

    /**
     * 获取权限标识数组
     *
     * @return array
     */
    public function getCodeArray(): array
    {
        return is_array($this->code) ? $this->code : [$this->code];
    }

    /**
     * 获取第一个权限标识（用于单权限场景）
     *
     * @return string
     */
    public function getFirstCode(): string
    {
        $codes = $this->getCodeArray();
        return $codes[0] ?? '';
    }

    /**
     * 获取权限名称（用于错误提示）
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取错误提示信息
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if (count($this->code) > 1) {
            if ($this->logic === 'or') {
                return "您没有权限执行此操作（需要以下任一权限：{$this->formatCodes()}）";
            } else {
                return "您没有权限执行此操作（需要以下所有权限：{$this->formatCodes()}）";
            }
        }

        $permissionName = $this->name ?: $this->getFirstCode();
        return "您没有权限执行此操作（{$permissionName}）";
    }

    /**
     * 格式化权限标识列表为字符串
     *
     * @return string
     */
    private function formatCodes(): string
    {
        return implode('、', $this->code);
    }

    /**
     * 是否需要多权限验证
     *
     * @return bool
     */
    public function isMultiplePermissions(): bool
    {
        return count($this->code) > 1;
    }

    /**
     * 获取数据权限配置
     *
     * @return array|null
     */
    public function getDataScopeConfig(): ?array
    {
        if (!$this->dataScope) {
            return null;
        }

        return [
            'resource'          => $this->resource,
            'userIdField'       => $this->userIdField,
            'departmentIdField' => $this->departmentIdField,
        ];
    }

    /**
     * 生成缓存键
     *
     * @param string $class
     * @param string $method
     * @return string
     */
    public static function getCacheKey(string $class, string $method): string
    {
        return 'permission_annotation:' . md5($class . '@' . $method);
    }
}
