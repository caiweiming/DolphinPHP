<?php
// +----------------------------------------------------------------------
// | 商店应用配置
// +----------------------------------------------------------------------

return [
    'storefront' => [
        'title' => 'DolphinPHP 官方商店',
    ],
    'download'   => [
        'allow_guest' => false,
    ],
    'token'      => [
        'ttl' => 30 * 86400,
    ],
    'payment' => [
        'alipay' => [
            'enabled'              => env('STORE_ALIPAY_ENABLED', false),
            'mode'                 => env('STORE_ALIPAY_MODE', 'sandbox'),
            'gateway'              => env('STORE_ALIPAY_GATEWAY', 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'),
            'app_id'               => env('STORE_ALIPAY_APP_ID', ''),
            'merchant_private_key' => env('STORE_ALIPAY_PRIVATE_KEY', ''),
            'alipay_public_key'    => env('STORE_ALIPAY_PUBLIC_KEY', ''),
            'notify_url'           => env('STORE_ALIPAY_NOTIFY_URL', ''),
            'return_url'           => env('STORE_ALIPAY_RETURN_URL', ''),
            'charset'              => 'utf-8',
            'sign_type'            => 'RSA2',
            'product_code'         => 'FAST_INSTANT_TRADE_PAY',
        ],
    ],
];
