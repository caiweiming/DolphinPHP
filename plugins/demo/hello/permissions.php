<?php
declare(strict_types=1);

/**
 * 示例插件权限定义
 *
 * 用于演示插件启用时如何同步菜单和权限，禁用/卸载时如何按插件前缀清理。
 */
return [
    'items' => [
        [
            'name'   => '示例插件',
            'code'   => 'plugin.demo.hello',
            'type'   => 'menu',
            'route'  => 'plugin/demo/hello',
            'icon'   => 'ti ti-plug',
            'sort'   => 80,
            'remark' => '示例插件入口菜单',
        ],
        [
            'name'        => '健康检查',
            'code'        => 'plugin.demo.hello.ping',
            'type'        => 'button',
            'route'       => 'plugin/demo/hello/ping',
            'parent_code' => 'plugin.demo.hello',
            'sort'        => 10,
            'remark'      => '示例插件健康检查接口',
        ],
    ],
];
