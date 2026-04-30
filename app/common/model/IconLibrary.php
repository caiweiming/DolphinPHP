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

namespace app\common\model;

/**
 * 在线图标库模型
 */
class IconLibrary extends Base
{
    /**
     * 阿里 Iconfont 在线图标库
     */
    public const SOURCE_TYPE_ICONFONT = 'iconfont';

    /**
     * @var mixed
     */
    public mixed $id = null;

    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_icon';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id'             => 'integer',
        'icon_count'     => 'integer',
        'autoload'       => 'integer',
        'sort'           => 'integer',
        'status'         => 'integer',
        'sync_status'    => 'integer',
        'last_sync_time' => 'integer',
        'create_time'    => 'integer',
        'update_time'    => 'integer',
    ];

    /**
     * 获取来源类型列表
     * @return array
     */
    public static function getSourceTypeList(): array
    {
        return [
            self::SOURCE_TYPE_ICONFONT => '阿里 Iconfont',
        ];
    }

    /**
     * 获取同步状态列表
     * @return array
     */
    public static function getSyncStatusList(): array
    {
        return [
            1 => '成功',
            0 => '失败',
        ];
    }
}
