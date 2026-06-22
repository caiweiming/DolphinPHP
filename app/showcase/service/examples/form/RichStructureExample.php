<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;

/**
 * Showcase 富文本与复杂结构示例
 */
final class RichStructureExample
{
    public function render(): string
    {
        return Form::make('showcase_rich_structure', '富文本与复杂结构')
            ->action((string) dp_url('showcase/admin.demo_api/submit'))
            ->header(false)
            ->data([
                'demo_title' => '复杂结构演示',
                'content' => "## Showcase\n\n用于演示富文本与组合结构。",
                'role_keys' => ['editor'],
                'contact_name' => '张三',
                'contact_mobile' => '13800138000',
                'example_key' => 'rich.structure',
            ])
            ->items([
                ['text:*', 'demo_title', '演示标题', '请输入演示标题'],
                ['vditor', 'content', '演示正文', '请输入正文'],
                [
                    'type' => 'transfer',
                    'name' => 'role_keys',
                    'label' => '角色分配',
                    'tips' => '请选择角色',
                    'value' => ['editor'],
                    'options' => [
                        'admin' => '管理员',
                        'editor' => '编辑',
                        'auditor' => '审核员',
                        'guest' => '访客',
                    ],
                ],
                [
                    'type' => 'fieldset',
                    'name' => 'contact_block',
                    'label' => '联系人信息',
                    'tips' => '该区块展示由字段块组合而成的复杂结构。',
                    'options' => [
                        ['type' => 'text', 'name' => 'contact_name', 'label' => '联系人'],
                        ['type' => 'text', 'name' => 'contact_mobile', 'label' => '联系电话'],
                    ],
                ],
                ['hidden', 'example_key', '', '', 'rich.structure'],
            ])
            ->fetch();
    }
}
