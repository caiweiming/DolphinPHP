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

namespace upload\aliyun;

use upload\AliyunBase;

/**
 * 阿里云上传驱动 - Vditor组件
 */
class Vditor extends AliyunBase
{
    /**
     * 获取组件类型
     * @return string
     */
    protected function getComponentType(): string
    {
        return 'vditor';
    }

    /**
     * 设置上传URL（Vditor特有的URL结构）
     * @param array $item
     * @return array
     */
    protected function setUploadUrl(array $item): array
    {
        $item['options']['upload']['url'] = 'https://' . $this->config['bucket'] . '.' . $this->config['endpoint'];
        return $item;
    }
}
