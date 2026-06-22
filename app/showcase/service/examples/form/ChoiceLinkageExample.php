<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;

/**
 * Showcase 选择与联动示例
 */
final class ChoiceLinkageExample
{
    public function render(): string
    {
        return Form::make('showcase_choice_linkage', '选择与联动')
            ->action((string) dp_url('showcase/admin.demo_api/submit'))
            ->header(false)
            ->data([
                'demo_title' => '联动选择演示',
                'role_scene' => 'internal',
                'department' => 'ops',
                'department_path' => [
                    'region' => 'north',
                    'site' => 'north-bj',
                    'team' => 'north-bj-cy',
                ],
                'example_key' => 'choice.linkage',
            ])
            ->items([
                ['text:*', 'demo_title', '演示标题', '请输入演示标题'],
                [
                    'type' => 'radio_group',
                    'name' => 'role_scene',
                    'label' => '演示场景',
                    'options' => [
                        'internal' => '内部协作',
                        'channel' => '渠道拓展',
                    ],
                    'value' => 'internal',
                ],
                [
                    'type' => 'select',
                    'name' => 'department',
                    'label' => '所属部门',
                    'tips' => '请选择一个部门',
                    'options' => [
                        'ops' => '运营中心',
                        'sales' => '销售中心',
                        'product' => '产品中心',
                    ],
                    'props' => 'data-source-url="' . (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'departments']) . '"',
                ],
                [
                    'type' => 'linkage',
                    'name' => 'department_path',
                    'label' => '联动路径',
                    'tips' => '选择大区和站点',
                    'levels' => [
                        ['key' => 'region', 'label' => '大区'],
                        ['key' => 'site', 'label' => '站点'],
                        ['key' => 'team', 'label' => '团队'],
                    ],
                    'options' => [
                        ['key' => 'north', 'value' => '华北大区'],
                        ['key' => 'east', 'value' => '华东大区'],
                    ],
                    'url' => (string) dp_url('showcase/admin.demo_api/options', ['dataset' => 'linkage']),
                ],
                ['hidden', 'example_key', '', '', 'choice.linkage'],
            ])
            ->fetch();
    }
}
