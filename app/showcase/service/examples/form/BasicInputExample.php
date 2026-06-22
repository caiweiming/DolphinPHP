<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;

/**
 * Showcase 基础输入示例
 */
final class BasicInputExample
{
    public function render(): string
    {
        return Form::make('showcase_basic_text', '基础输入组合')
            ->action((string) dp_url('showcase/admin.demo_api/submit'))
            ->header(false)
            ->data([
                'demo_title' => '基础输入演示',
                'summary' => '用于演示文本、摘要、排序与状态字段的组合。',
                'sort' => 10,
                'status' => 1,
                'example_key' => 'basic.text',
            ])
            ->items([
                ['text:*', 'demo_title', '演示标题', '请输入演示标题'],
                ['textarea', 'summary', '摘要', '请输入摘要'],
                ['number', 'sort', '排序', '数字越小越靠前'],
                ['switch', 'status', '启用状态', '是否启用该示例'],
                ['hidden', 'example_key', '', '', 'basic.text'],
            ])
            ->fetch();
    }
}
