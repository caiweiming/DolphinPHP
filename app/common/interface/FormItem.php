<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\interface;

/**
 * 表单项接口
 * @package app\common\interface
 */
interface FormItem
{
    /**
     * 处理方法
     * @param array $params
     * @return array
     */
    public function handle(array $params = []): array;

    /**
     * 获取资源
     * @return array
     */
    public function getAssets(): array;

    /**
     * 获取模板路径
     * @return string
     */
    public function getTemplate(): string;
}
