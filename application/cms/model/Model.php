<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 内容模型
 * @package app\cms\model
 */
class Model extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'cms_model';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取内容模型列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getList()
    {
        $data_list = cache('cms_model_list');
        if (!$data_list) {
            $data_list = self::where('status', 1)->column(true, 'id');
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('cms_model_list', $data_list);
            }
        }
        return $data_list;
    }

    /**
     * 获取内容模型标题列表（只含id和title）
     * @param array $map 筛选条件
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|mixed
     */
    public static function getTitleList($map = [])
    {
        return self::where('status', 1)->where($map)->column('id,title');
    }

    /**
     * 删除附加表
     * @param null $model 内容模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public static function deleteTable($model = null)
    {
        if ($model === null) {
            return false;
        }

        $table_name = self::where('id', $model)->value('table');
        return false !== Db::execute("DROP TABLE IF EXISTS `{$table_name}`");
    }

    /**
     * 创建独立模型表
     * @param mixed $data 模型数据
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public static function createTable($data)
    {
        if ($data['type'] == 2) {
            // 新建独立扩展表
            $sql = <<<EOF
            CREATE TABLE IF NOT EXISTS `{$data['table']}` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文档id' ,
            `cid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '栏目id' ,
            `uid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id' ,
            `model` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模型id' ,
            `title` varchar(256) NOT NULL DEFAULT '' COMMENT '标题' ,
            `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间' ,
            `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间' ,
            `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序' ,
            `status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态' ,
            `view` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击量' ,
            `trash` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回收站' ,
            PRIMARY KEY (`id`)
            )
            ENGINE=MyISAM
            DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
            CHECKSUM=0
            ROW_FORMAT=DYNAMIC
            DELAY_KEY_WRITE=0
            COMMENT='{$data['title']}模型表'
            ;
EOF;
        } else {
            // 新建普通扩展表
            $sql = <<<EOF
                CREATE TABLE IF NOT EXISTS `{$data['table']}` (
                `aid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文档id' ,
                PRIMARY KEY (`aid`)
                )
                ENGINE=MyISAM
                DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
                CHECKSUM=0
                ROW_FORMAT=DYNAMIC
                DELAY_KEY_WRITE=0
                COMMENT='{$data['title']}模型扩展表'
                ;
EOF;
        }

        try {
            Db::execute($sql);
        } catch(\Exception $e) {
            return false;
        }

        if ($data['type'] == 2) {
            // 添加默认字段
            $default = [
                'model'       => $data['id'],
                'level'       => '',
                'create_time' => request()->time(),
                'update_time' => request()->time(),
                'status'      => 1
            ];
            $data = [
                [
                    'name'        => 'id',
                    'title'       => '文档id',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 0
                ],
                [
                    'name'        => 'cid',
                    'title'       => '栏目',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'static',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'uid',
                    'title'       => '用户id',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'model',
                    'title'       => '文档模型',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'title',
                    'title'       => '标题',
                    'define'      => 'varchar(256) NOT NULL',
                    'type'        => 'text',
                    'show'        => 1
                ],
                [
                    'name'        => 'create_time',
                    'title'       => '创建时间',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'datetime',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'update_time',
                    'title'       => '更新时间',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'datetime',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'sort',
                    'title'       => '排序',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 1,
                    'value'       => 100,
                ],
                [
                    'name'        => 'status',
                    'title'       => '状态',
                    'define'      => 'tinyint(2) NOT NULL',
                    'type'        => 'radio',
                    'show'        => 1,
                    'value'       => 1,
                    'options'     => '0:禁用
1:启用'
                ],
                [
                    'name'        => 'view',
                    'title'       => '点击量',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 0,
                    'value'       => 0
                ],
                [
                    'name'        => 'trash',
                    'title'       => '回收站',
                    'define'      => 'tinyint(2) NOT NULL',
                    'type'        => 'radio',
                    'show'        => 0,
                    'value'       => 0
                ]
            ];

            foreach ($data as $item) {
                $item = array_merge($item, $default);
                Db::name('cms_field')->insert($item);
            }
        }
        return true;
    }
}
