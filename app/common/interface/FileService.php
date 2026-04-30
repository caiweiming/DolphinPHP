<?php
declare(strict_types=1);

namespace app\common\interface;

use app\admin\model\File as FileModel;
use think\file\UploadedFile;
use think\Model;

/**
 * 文件服务接口
 */
interface FileService
{
    /**
     * 检查文件是否存在
     * @param int|string $id 文件id或者hash
     * @return array|bool
     */
    public function exists(int|string $id = 0): mixed;
    
    /**
     * 保存文件信息
     * @param array $data 文件数据
     * @return array
     */
    public function save(array $data): array;

    /**
     * 保存上传的文件
     * @param mixed $file 上传的文件
     * @param string $path 文件路径
     * @param string $from 来源
     * @return FileModel|Model
     */
    public function saveFile(UploadedFile $file, string $path = '', string $from = ''): FileModel|Model;
    
    /**
     * 检查文件大小是否超限
     * @param mixed $file 文件对象
     * @return bool
     */
    public function overSize(UploadedFile $file): bool;
    
    /**
     * 检查文件扩展名是否合法
     * @param mixed $file 文件对象或参数数组
     * @return bool
     */
    public function overExt(array|UploadedFile $file): bool;
    
    /**
     * 获取文件信息
     * @param int $id 文件ID
     * @return array|null
     */
    public function getFileInfo(int $id): ?array;
    
    /**
     * 删除文件
     * @param int $id 文件ID
     * @return bool
     */
    public function deleteFile(int $id): bool;
} 