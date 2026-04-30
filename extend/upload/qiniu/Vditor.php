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

namespace upload\qiniu;

use upload\QiniuBase;

/**
 * 七牛云上传驱动 - Vditor组件
 */
class Vditor extends QiniuBase
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
     * 入口方法（Vditor特有的URL结构）
     * @param array $item
     * @return array
     */
    public function handle(array $item = []): array
    {
        $item['options']['upload']['url'] = $this->config['url'];
        $item['dir']                      = $this->getDir($item);
        return $item;
    }
}
