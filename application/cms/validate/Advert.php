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

namespace app\cms\validate;

use think\Validate;

/**
 * 广告验证器
 * @package app\cms\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Advert extends Validate
{
    // 定义验证规则
    protected $rule = [
        'typeid|广告分类'   => 'require',
        'tagname|广告位标识' => 'require|regex:^[a-z]+[a-z0-9_]{0,20}$|unique:cms_advert',
        'name|广告位名称'    => 'require|unique:cms_advert',
        'start_time'    => 'requireIf:timeset,1',
        'end_time'      => 'requireIf:timeset,1',
        'title'         => 'requireIf:ad_type,1',
        'code'          => 'requireIf:ad_type,0',
        'size'          => 'integer',
        'width'         => 'integer',
        'height'        => 'integer',
        'src'           => 'requireIf:ad_type,2',
    ];

    // 定义验证提示
    protected $message = [
        'tagname.regex' => '广告位标识由小写字母、数字或下划线组成，不能以数字开头',
        'code'          => '代码不能为空',
        'src'           => '请上传图片',
        'title'         => '文字内容不能为空',
        'start_time'    => '开始时间不能为空',
        'end_time'      => '结束时间不能为空',
        'size'          => '文字大小只能填写数字',
        'width'         => '宽度只能填写数字',
        'height'        => '高度只能填写数字',
    ];

    // 定义验证场景
    protected $scene = [
        'name' => ['name']
    ];
}
