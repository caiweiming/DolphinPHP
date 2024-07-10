<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

return [
    // 拒绝ie访问
    'deny_ie'       => false,
    // 模块管理中，不读取模块信息的目录
    'except_module' => ['common', 'admin', 'index', 'extra', 'user', 'install'],
    // 禁用函数
    'disable_functions' => [
        'eval',
        'passthru',
        'exec',
        'system',
        'chroot',
        'chgrp',
        'popen',
        'ini_alter',
        'ini_restore',
        'dl',
        'openlog',
        'syslog',
        'readlink',
        'symlink',
        'popepassthru',
        'phpinfo',
        'shell_exec'
    ]
];