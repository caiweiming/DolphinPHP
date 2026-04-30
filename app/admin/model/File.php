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

namespace app\admin\model;

use app\common\model\Base;

/**
 * 附件模型
 * @method static mixed where($field, $op = null, $condition = null) 指定AND查询条件
 * @method static mixed find($data = null) 查找单条记录
 * @package app\admin\model
 */
class File extends Base
{
    /**
     * 用户表名
     * @var string
     */
    protected $name = 'admin_file';
}
