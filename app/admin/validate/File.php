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

namespace app\admin\validate;

use think\Validate;

/**
 * 文件验证器
 * @package app\admin\validate
 */
class File extends Validate
{
    /**
     * 验证规则
     * @var string[]
     */
    protected $rule = [
        'name'   => 'require',
        'url'    => 'require',
        'sha1'   => 'require',
        'driver' => 'require',
        'mime'   => 'require',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'name'   => '缺少参数:name',
        'url'    => '缺少参数:url',
        'sha1'   => '缺少参数:sha1',
        'driver' => '缺少参数:driver',
        'mime'   => '缺少参数:mime',
    ];
}
