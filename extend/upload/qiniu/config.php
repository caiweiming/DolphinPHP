<?php
return [
    // 驱动元信息
    'meta'         => [
        'name'        => '七牛云存储',
        'version'     => '1.0.0',
        'description' => '基于七牛云对象存储的上传驱动',
        'author'      => 'DolphinPHP',
        'homepage'    => 'https://www.qiniu.com',
        'requires'    => [
            'php'           => '>=8.0',
            'ext-curl'      => '*',
            'qiniu/php-sdk' => '^7.0'
        ]
    ],

    // 驱动类信息
    'driver'       => [
        'name' => 'qiniu'
    ],

    // 默认配置（可被环境变量覆盖）
    'config'       => [
        // 七牛云上传url, 空间存储区域不同, url可能不一样
        // 参考:https://developer.qiniu.com/kodo/1671/region-endpoint-fq
        'url'        => 'https://up.qiniup.com',
        // 密钥AK
        'access_key' => '',
        // 密钥SK
        'secret_key' => '',
        // 空间名称
        'bucket'     => '',
        // 空间域名，建议用“//”开头，如：//qiniu.dolphinphp.com
        'domain'     => '',
    ]
];
