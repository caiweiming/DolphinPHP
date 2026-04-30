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

namespace app\common\render\form;

use think\facade\Config as SystemConfig;

/**
 * 表单配置管理类
 * @package app\common\render\form
 */
class Config
{
    /**
     * 获取表单默认配置
     * @return array
     */
    public static function getDefaults(): array
    {
        // 获取基础配置
        $defaults = [
            // 表单id
            'dp_form_id'            => 'dp-form',
            // 表单标题
            'dp_form_title'         => '',
            // 表单class
            'dp_form_class'         => '',
            // 表单属性
            'dp_form_prop'          => '',
            // 提交url
            'dp_form_action'        => '',
            // 提交方式
            'dp_form_method'        => 'post',
            // 是否ajax方式提交
            'dp_form_ajax'          => true,
            // 提交按钮类型
            'dp_form_submit_type'   => '',
            // 提交按钮
            'dp_form_button_submit' => SystemConfig::get('form.button.submit', []),
            // 返回按钮
            'dp_form_button_back'   => SystemConfig::get('form.button.back', []),
            // 表单头部工具栏
            'dp_form_header_action' => [],
            // 表单头部
            'dp_form_header'        => true,
            // 表单底部
            'dp_form_footer'        => true,
            // 表单底部右侧区域
            'dp_form_action_right'  => [],
            // 表单底部左侧区域
            'dp_form_action_left'   => [],
            // 表单主体内容
            'dp_form_rows'          => [],
            // 表单项
            'dp_form_items'         => [],
            // 字段联动规则（base64编码JSON）
            'dp_form_when_rules'    => '',
            // 额外js代码
            'dp_form_extra_js'      => [],
            // 额外css代码
            'dp_form_extra_css'     => [],
            // 额外html代码
            'dp_form_extra_html'    => [
                'top'          => [], // 表单顶部
                'bottom'       => [], // 表单底部
                'inner_top'    => [], // 表单内顶部
                'inner_bottom' => [], // 表单内底部
            ],
            // 顶部提示
            'dp_form_alert_top'     => [],
            // 底部提示
            'dp_form_alert_bottom'  => [],
            // 换行
            'dp_form_newline'       => SystemConfig::get('form.newline', ''),
            // 表单吸附
            'dp_form_sticky'        => SystemConfig::get('form.sticky', []),
            // 上传驱动配置
            'dp_form_upload_config' => [],
            // 表单提交确认提示
            'dp_form_confirm'       => [],
        ];

        // 合并资源容器配置
        return array_merge($defaults, static::getAssetContainers());
    }

    /**
     * 获取资源容器配置
     * @return array
     */
    public static function getAssetContainers(): array
    {
        return [
            'dp_file_css'  => [],
            'dp_file_js'   => [],
            'dp_extra_css' => [],
            'dp_extra_js'  => [],
            'dp_init_js'   => [],
        ];
    }
}
