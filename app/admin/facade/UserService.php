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

namespace app\admin\facade;

use think\Facade;

/**
 * 管理员服务门面
 * @package app\admin\facade
 * @method static mixed parseParam(array $param) 解析登录参数
 * @method static mixed login(array $param) 账户登录
 * @method static mixed getUserInfo(string $username) 获取用户信息
 */
class UserService extends Facade
{
    /**
     * getFacadeClass
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return 'app\admin\service\User';
    }
}
