<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 表单构建器方法目录
 */
final class ShowcaseFormBuilderMethodCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'identity',
                'title' => '基础标识与外观',
                'summary' => '控制表单标题、DOM 标识、根节点 class 与属性。',
                'methods' => ['id', 'title', 'class', 'prop'],
            ],
            [
                'key' => 'data_items',
                'title' => '数据与内容组织',
                'summary' => '控制回填数据、单个表单项、批量表单项与模板映射。',
                'methods' => ['data', 'item', 'items', 'item_template'],
            ],
            [
                'key' => 'layout',
                'title' => '页面结构',
                'summary' => '控制头部、尾部、按钮区和结构性操作。',
                'methods' => ['header', 'header_action', 'footer', 'footer_action', 'btn_submit', 'btn_back'],
            ],
            [
                'key' => 'request',
                'title' => '提交与请求',
                'summary' => '控制提交地址、请求方法、AJAX 方式与确认弹窗。',
                'methods' => ['action', 'method', 'ajax', 'confirm'],
            ],
            [
                'key' => 'notice_assets',
                'title' => '提示与说明',
                'summary' => '控制表单提示区、附加 HTML / 资源 / 吸附行为。',
                'methods' => ['alert', 'extra_html', 'extra_html_file', 'extra_js', 'extra_css', 'js', 'css', 'sticky'],
            ],
            [
                'key' => 'advanced',
                'title' => '进阶扩展',
                'summary' => '控制布局模板、处理映射和模板变量等进阶能力。',
                'methods' => ['template', 'handle', 'assign'],
            ],
        ];
    }
}
