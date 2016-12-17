<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace plugins\HelloWorld\model;

use app\common\model\Plugin;

/**
 * 后台插件模型
 * @package plugins\HelloWorld\model
 */
class HelloWorld extends Plugin
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__PLUGIN_HELLO__';

    public function test()
    {
        // 获取插件的设置信息
        halt('test');
    }
}