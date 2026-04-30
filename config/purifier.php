<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 安全过滤配置purifier
// | 详细配置见：http://htmlpurifier.org/live/configdoc/plain.html
// +----------------------------------------------------------------------

return [
    // 基础配置
    'default'  => [
        'Core.Encoding'        => 'UTF-8',
        'Cache.SerializerPath' => runtime_path('htmlpurifier'),
    ],
    // 扩展配置
    'settings' => [
        // 默认配置
        'default'  => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],
        // 评论过滤配置
        'comment'  => [
            'HTML.Doctype'             => 'XHTML 1.0 Strict',
            'HTML.Allowed'             => 'p,a[href|title],abbr[title],acronym[title],b,strong,blockquote[cite],code,em,i,strike',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.Linkify'       => true,
            'AutoFormat.RemoveEmpty'   => true
        ],
        // Markdown 文档展示配置
        'markdown' => [
            'HTML.Doctype'             => 'XHTML 1.0 Transitional',
            'HTML.Allowed'             => 'h1,h2,h3,h4,h5,h6,p,br,hr,ul,ol,li,blockquote,pre[class],code[class],table,thead,tbody,tr,th,td,a[href|title|target|rel],strong,b,em,i,del,img[src|alt|title|width|height]',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'URI.AllowedSchemes'       => [
                'http'   => true,
                'https'  => true,
                'mailto' => true,
            ],
        ]
    ]
];
