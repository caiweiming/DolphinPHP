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

namespace app\admin\facade;

use think\Facade;

/**
 * 附件逻辑门面
 * @package app\admin\facade
 * @method static mixed getFileById(mixed $id) 通过文件id获取文件信息
 * @method static mixed getFileByHash(string $hash) 通过文件hash获取文件信息
 * @method static mixed save(array $data) 保存附件信息
 * @method static mixed saveFile(object $file, string $path, string $from) 保存附件信息,必须传入文件对象和保存路径
 * @method static mixed exists(mixed $id) 判断附件是否存在
 * @method static bool overSize(mixed $file) 判断文件大小是否超过限制
 * @method static bool overExt(mixed $file, string $filePath = null) 判断附件是否存在
 * @method static mixed getList(string $type = '', array $params = [], int $userId = 0) 获取附件列表
 */
class FileService extends Facade
{
    /**
     * getFacadeClass
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return 'app\admin\service\File';
    }
}
