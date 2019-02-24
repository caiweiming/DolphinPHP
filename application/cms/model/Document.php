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
use app\cms\model\Field as FieldModel;

/**
 * 文档模型
 * @package app\cms\model
 */
class Document extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'cms_document';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取文档列表
     * @param array $map 筛选条件
     * @param array $order 排序
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public static function getList($map = [], $order = [])
    {
        $data_list = self::view('cms_document', true)
            ->view("cms_column", ['name' => 'column_name'], 'cms_column.id=cms_document.cid', 'left')
            ->view("admin_user", 'username', 'admin_user.id=cms_document.uid', 'left')
            ->where($map)
            ->order($order)
            ->paginate();
        return $data_list;
    }

    /**
     * 获取单篇文档
     * @param string $id 文档id
     * @param string $model 独立模型id
     * @param array $map 查询条件
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|string|ThinkModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOne($id = '', $model = '', $map = [])
    {
        if ($model == '') {
            $document    = self::get($id);
            $extra_table = get_model_table($document['model']);

            $data = self::view('cms_document', true);
            if ($extra_table != '') {
                $data = $data->view($extra_table, true, 'cms_document.id='.$extra_table.'.aid', 'left');
            }

            return $data->view("cms_column", ['name' => 'column_name', 'list_template', 'detail_template'], 'cms_column.id=cms_document.cid', 'left')
                ->view("admin_user", 'username', 'admin_user.id=cms_document.uid', 'left')
                ->where('cms_document.id', $id)
                ->where($map)
                ->find();
        } else {
            $table = get_model_table($model);
            return Db::view($table, true)
                ->view("cms_column", ['name' => 'column_name', 'list_template', 'detail_template'], 'cms_column.id='.$table.'.cid', 'left')
                ->where($table.'.id', $id)
                ->where($map)
                ->find();
        }
    }

    /**
     * 新增或更新文档
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function saveData()
    {
        $data = request()->post();
        $data['uid'] = UID;

        // 文档模型
        $model = Db::name('cms_model')->where('id', $data['model'])->find();

        if ($model['type'] != 2 && empty($data['summary']) && config('cms_config.summary') > 0) {
            $data['summary'] = mb_substr(strip_tags($data['content']), 0, config('cms_config.summary'), 'utf-8');
        }

        // 处理自定义属性
        if (isset($data['flag'])) {
            $data['flag'] = implode(',', $data['flag']);
        } else {
            $data['flag'] = '';
        }

        // 验证基础内容
        if ($data['title'] == '') {
            $this->error = '标题不能为空';
            return false;
        }

        // 处理特殊字段类型
        $fields = FieldModel::where('model', $data['model'])->where('status', 1)->column('name,type');

        foreach ($fields as $name => $type) {
            if (!isset($data[$name])) {
                switch ($type) {
                    // 开关
                    case 'switch':
                        $data[$name] = 0;
                        break;
                    case 'checkbox':
                        $data[$name] = '';
                        break;
                }
            } else {
                // 如果值是数组则转换成字符串，适用于复选框等类型
                if (is_array($data[$name])) {
                    $data[$name] = implode(',', $data[$name]);
                }
                switch ($type) {
                    // 开关
                    case 'switch':
                        $data[$name] = 1;
                        break;
                    // 日期时间
                    case 'date':
                    case 'time':
                    case 'datetime':
                        $data[$name] = strtotime($data[$name]);
                        break;
                }
            }
        }

        if (empty($data['id'])) {
            if ($model['type'] == 2) {
                // 新增独立模型文档
                $data['create_time'] = request()->time();
                $data['update_time'] = request()->time();
                $insert_id = Db::table($model['table'])->insertGetId($data);
                if (false === $insert_id) {
                    $this->error = '新增失败';
                    return false;
                } else {
                    // 记录行为
                    action_log('document_add', $model['table'], $insert_id, UID, $data['title']);
                    return true;
                }
            } else {
                // 新增文档基础内容
                if ($document = self::create($data)) {
                    // 新增文档扩展内容
                    if ($model['table'] != '') {
                        $data['aid'] = $document['id'];
                        if (false === Db::table($model['table'])->insert($data)) {
                            // 删除已添加的基础内容
                            self::destroy($document['id']);
                            $this->error = '新增扩展内容出错';
                            return false;
                        }
                    }
                    // 记录行为
                    action_log('document_add', 'cms_document', $document['id'], UID, $document['title']);
                    return true;
                } else {
                    $this->error = '新增基础内容出错';
                    return false;
                }
            }
        } else {
            // 更新独立模型文档
            if ($model['type'] == 2) {
                // 新增独立模型文档
                $data['update_time'] = request()->time();
                if (false === Db::table($model['table'])->update($data)) {
                    $this->error = '编辑失败';
                    return false;
                } else {
                    // 记录行为
                    action_log('document_edit', $model['table'], $data['id'], UID, $data['title']);
                    return true;
                }
            } else {
                // 更新文档基础内容
                if (self::update($data)) {
                    // 更新文档扩展内容
                    $data['aid'] = $data['id'];
                    if (false !== Db::table($model['table'])->update($data)) {
                        // 记录行为
                        action_log('document_edit', 'cms_document', $data['id'], UID, $data['title']);
                        return true;
                    } else {
                        $this->error = '更新扩展内容出错';
                        return false;
                    }
                } else {
                    $this->error = '更新基础内容出错';
                    return false;
                }
            }
        }
    }
}
