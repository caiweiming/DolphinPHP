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
 * 管理员模型门面
 * @package app\admin\facade
 * @method static mixed getInfo(string $username, bool $withPassword = false) 获取用户信息
 * @method static mixed getInfoById(int $id) 根据用户id获取用户信息
 * @method static mixed updateById(int $id, array $data) 根据用户id更新数据
 */
class UserModel extends Facade
{
    /**
     * getFacadeClass
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return 'app\common\model\User';
    }
}
