<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;

/**
 * 附件模型
 * @package app\admin\model
 */
class Attachment extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_attachment';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 根据附件id获取路径
     * @param string|array $id 附件id
     * @param  int $type 类型：0-补全目录，1-直接返回数据库记录的地址
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFilePath($id = '', $type = 0)
    {
        if (is_array($id)) {
            $data_list = $this->where('id', 'in', $id)->select();
            $paths = [];
            foreach ($data_list as $key => $value) {
                if ($value['driver'] == 'local') {
                    $paths[$value['id']] = ($type == 0 ? PUBLIC_PATH : '').$value['path'];
                } else {
                    $paths[$value['id']] = $value['path'];
                }
            }
            return $paths;
        } else {
            $data = $this->where('id', $id)->find();
            if ($data) {
                if ($data['driver'] == 'local') {
                    return ($type == 0 ? PUBLIC_PATH : '').$data['path'];
                } else {
                    return $data['path'];
                }
            } else {
                return false;
            }
        }
    }

    /**
     * 根据图片id获取缩略图路径，如果缩略图不存在，则返回原图路径
     * @param string $id 图片id
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getThumbPath($id = '')
    {
        $result = $this->where('id', $id)->field('path,driver,thumb')->find();
        if ($result) {
            if ($result['driver'] == 'local') {
                return $result['thumb'] != '' ? PUBLIC_PATH.$result['thumb'] : PUBLIC_PATH.$result['path'];
            } else {
                return $result['thumb'] != '' ? $result['thumb'] : $result['path'];
            }
        } else {
            return $result;
        }
    }

    /**
     * 根据附件id获取名称
     * @param  string $id 附件id
     * @return string     名称
     */
    public function getFileName($id = '')
    {
        return $this->where('id', $id)->value('name');
    }
}
