<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 后台用户工作台配置模型
 */
class AdminUserWorkspace extends Base
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_user_workspace';

    /**
     * 类型转换
     * @var array<string, string>
     */
    protected $type = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
}
