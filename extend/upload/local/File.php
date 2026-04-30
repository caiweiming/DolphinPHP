<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace upload\local;

use upload\LocalBase;

/**
 * 本地上传驱动 - 文件组件
 */
class File extends LocalBase
{
    /**
     * 获取组件类型
     * @return string
     */
    protected function getComponentType(): string
    {
        return 'file';
    }

    /**
     * 自定义处理方法（File组件需要设置dataType）
     * @param array $item
     * @return array
     */
    protected function customizeHandle(array $item): array
    {
        $item['options']['dataType'] = 'json';
        return $item;
    }
}