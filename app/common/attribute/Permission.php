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

namespace app\common\attribute;

use Attribute;

/**
 * 权限注解
 *
 * 用于标记需要生成权限的控制器和方法
 *
 * 使用示例：
 * ```
 * // 类注解（生成菜单权限）
 * #[Permission('用户管理', parent: 'system', icon: 'ti ti-users')]
 *
 * // 方法注解（生成按钮权限）
 * #[Permission('新增')]
 *
 * // API 方法（生成 API 权限）
 * #[Permission('获取用户列表', type: 'api')]
 * ```
 *
 * @package app\common\attribute
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Permission
{
    /**
     * 构造函数
     *
     * @param string $name 权限名称（必填）
     * @param string|null $type 权限类型（可选，不填自动推断：类=menu，方法=button）
     *                          可选值：'menu'（菜单）、'button'（按钮）、'api'（接口）
     * @param string|null $code 权限代码（可选，不填自动生成）
     * @param string|null $parent 父级权限代码（可选，如 'system'表示归属到系统管理菜单下）
     * @param string|null $icon 图标（可选，如 'ti ti-users'）
     * @param int $sort 排序值（可选，默认 0）
     * @param string|null $remark 备注说明（可选）
     * @param bool $generate 是否生成权限（可选，默认 true）
     */
    public function __construct(
        public string  $name,
        public ?string $type = null,
        public ?string $code = null,
        public ?string $parent = null,
        public ?string $icon = null,
        public int     $sort = 0,
        public ?string $remark = null,
        public bool    $generate = true
    )
    {
    }
}
