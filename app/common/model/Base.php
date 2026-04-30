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

use think\Model;

/**
 * 模型基础类
 */
abstract class Base extends Model
{
    /**
     * 错误信息
     * @var string
     */
    protected string $error = '';

    /**
     * 获取错误信息
     * @return string
     */
    public function getError(): string
    {
        return lang($this->error);
    }
}
