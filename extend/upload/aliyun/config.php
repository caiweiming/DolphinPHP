<?php
return [
    // 驱动元信息
    'meta'         => [
        'name'        => '阿里云OSS',
        'version'     => '1.0.0',
        'description' => '基于阿里云对象存储的上传驱动',
        'author'      => 'DolphinPHP',
        'homepage'    => 'https://www.aliyun.com/product/oss',
        'requires'    => [
            'php'      => '>=8.0',
            'ext-curl' => '*'
        ]
    ],

    // 驱动类信息
    'driver'       => [
        'name' => 'aliyun'
    ],

    // 默认配置
    'config'       => [
        // Bucket空间名称
        'bucket'     => '',
        // Bucket所在地域
        'region'     => '',
        // 地域节点(用于上传资源)
        'endpoint'   => '',
        // Bucket域名(用于访问资源)，建议用“//”开头，如：//qiniu.dolphinphp.com
        'domain'     => '',
        // AccessKey ID
        'access_key' => '',
        // AccessKey Secret
        'secret_key' => '',
        // 凭证过期时间, 单位秒
        'expire'     => 3600
    ]
];
