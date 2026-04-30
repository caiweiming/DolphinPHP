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
 * 登录检查 Attribute
 * 用于标记控制器或方法是否需要登录验证
 *
 * 使用示例：
 * ```
 * // 类级别：整个控制器需要登录
 * #[LoginCheck]
 * class User extends Auth {}
 *
 * // 类级别：整个控制器不需要登录
 * #[LoginCheck(false)]
 * class Login extends Common {}
 *
 * // 方法级别覆盖类级别
 * #[LoginCheck]
 * class Api extends Common {
 *     #[LoginCheck(false)]  // 此方法无需登录
 *     public function publicApi() {}
 * }
 * ```
 *
 * @package app\common\attribute
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class LoginCheck
{
    /**
     * 构造函数
     *
     * @param bool $required 是否需要登录（默认 true）
     * @param string $message 未登录时的错误提示
     * @param string|null $redirect 未登录时的重定向URL（null 则根据请求类型处理）
     */
    public function __construct(
        public bool    $required = true,
        public string  $message = '请先登录',
        public ?string $redirect = null
    )
    {
    }
}
