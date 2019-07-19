<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Column as ColumnModel;
use app\cms\model\Document as DocumentModel;
use app\cms\model\Field as FieldModel;
use think\Db;
use util\Tree;

/**
 * 文档控制器
 * @package app\cms\admin
 */
class Document extends Admin
{
    /**
     * 文档列表
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map   = $this->getMap();
        $map[] = ['cms_document.trash', '=', 0];
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = DocumentModel::getList($map, $order);

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题', 'cms_column.name' => '栏目名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['column_name', '栏目名称'],
                ['view', '点击量'],
                ['username', '发布人'],
                ['update_time', '更新时间', 'datetime'],
                ['sort', '排序', 'text.edit'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete']) // 批量添加右侧按钮
            ->addOrder(['column_name' => 'cms_document.cid'])
            ->addOrder('id,title,view,username,update_time')
            ->addFilter(['column_name' => 'cms_column.name', 'username' => 'admin_user'])
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 添加文档
     * @param int $cid 栏目id
     * @param string $model 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add($cid = 0, $model = '')
    {
        // 保存文档数据
        if ($this->request->isAjax()) {
            $DocumentModel = new DocumentModel();
            if (false === $DocumentModel->saveData()) {
                $this->error($DocumentModel->getError());
            }
            $this->success('新增成功', cookie('__forward__'));
        }

        // 第二步，填写文档信息
        if ($cid > 0) {
            cookie('__forward__', url('add', ['cid' => $cid]));
            // 获取栏目数据
            $column = ColumnModel::getInfo($cid);

            // 独立模型只取该模型的字段，不包含系统字段
            $where = [];
            if (get_model_type($column['model']) == 2) {
                $where[] = ['model', '=', $column['model']];
            } else {
                $where[] = ['model', 'in', [0, $column['model']]];
            }

            // 获取文档模型字段
            $where[] = ['status', '=', 1];
            $where[] = ['show', '=', 1];
            $fields = FieldModel::where($where)->order('sort asc,id asc')->column(true);

            foreach ($fields as &$value) {
                // 解析options
                if ($value['options'] != '') {
                    $value['options'] = parse_attr($value['options']);
                }

                switch ($value['type']) {
                    case 'linkage':// 解析联动下拉框异步请求地址
                        if (!empty($value['ajax_url']) && substr($value['ajax_url'], 0, 4) != 'http') {
                            $value['ajax_url'] = url($value['ajax_url']);
                        }
                        break;
                    case 'date':
                    case 'time':
                    case 'datetime':
                        $value['value'] = '';
                        break;
                    case 'bmap':
                        $value['level'] = $value['level'] == 0 ? 12 : $value['level'];
                        break;
                    case 'colorpicker':
                        $value['mode']  = 'rgba';
                        break;
                }
            }

            // 添加额外表单项信息
            $extra_field = [
                ['name' => 'cid', 'title' => '所属栏目', 'type' => 'static', 'value' => $column['name']],
                ['name' => 'cid', 'type' => 'hidden', 'value' => $cid],
                ['name' => 'model', 'type' => 'hidden', 'value' => $column['model']]
            ];
            $fields = array_merge($extra_field, $fields);

            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setFormItems($fields)
                ->hideBtn('back')
                ->fetch();
        }

        // 第一步，选择栏目
        if ($model == '') {
            $columns = ColumnModel::getTreeList(0, false);
        } else {
            // 获取相同内容模型的栏目
            $columns = Db::name('cms_column')->where('model', $model)->order('pid,id')->column('id,name,pid');
            $columns = Tree::config(['title' => 'name'])->toList($columns, current($columns)['pid']);
            $result  = [];
            foreach ($columns as $column) {
                $result[$column['id']] = $column['title_display'];
            }
            $columns = $result;
        }
        return ZBuilder::make('form')
            ->addFormItem('select', 'cid', '选择栏目', '请选择栏目', $columns)
            ->setBtnTitle('submit', '下一步')
            ->hideBtn('back')
            ->isAjax(false)
            ->fetch();
    }

    /**
     * 编辑文档
     * @param null $id 文档id
     * @param string $model 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = null, $model = '')
    {
        if ($id === null) $this->error('参数错误');

        // 保存文档数据
        if ($this->request->isPost()) {
            $DocumentModel = new DocumentModel();
            $result = $DocumentModel->saveData();
            if (false === $result) {
                $this->error($DocumentModel->getError());
            }
            $this->success('编辑成功', cookie('__forward__'));
        }

        // 获取数据
        $info = DocumentModel::getOne($id, $model);

        // 独立模型只取该模型的字段，不包含系统字段
        $where = [];
        if ($model != '') {
            $where[] = ['model', '=', $model];
        } else {
            $where[] = ['model', 'in', [0, $info['model']]];
        }

        // 用于查询内容模型栏目
        $map = $where;

        // 获取文档模型字段
        $where[] = ['status', '=', 1];
        $where[] = ['show', '=', 1];
        $fields = FieldModel::where($where)->order('sort asc,id asc')->column(true);

        foreach ($fields as $id => &$value) {
            // 解析options
            if ($value['options'] != '') {
                $value['options'] = parse_attr($value['options']);
            }
            // 日期时间
            switch ($value['type']) {
                case 'date':
                    $info[$value['name']] = format_time($info[$value['name']], 'Y-m-d');
                    break;
                case 'time':
                    $info[$value['name']] = format_time($info[$value['name']], 'H:i:s');
                    break;
                case 'datetime':
                    $info[$value['name']] = empty($info[$value['name']]) ? '' : format_time($info[$value['name']]);
                    break;
                case 'bmap':
                    $value['level'] = $value['level'] == 0 ? 12 : $value['level'];
                    break;
                case 'colorpicker':
                    $value['mode']  = 'rgba';
                    break;
            }
        }

        // 获取相同内容模型的栏目
        $columns = Db::name('cms_column')->where($map)->whereOr('model', $info['model'])->order('pid,id')->column('id,name,pid');
        $columns = Tree::config(['title' => 'name'])->toList($columns, current($columns)['pid']);
        $result  = [];
        foreach ($columns as $column) {
            $result[$column['id']] = $column['title_display'];
        }
        $columns = $result;


        // 添加额外表单项信息
        $extra_field = [
            ['name' => 'id', 'type' => 'hidden'],
            ['name' => 'cid', 'title' => '所属栏目', 'type' => 'select', 'options' => $columns],
            ['name' => 'model', 'type' => 'hidden']
        ];
        $fields = array_merge($extra_field, $fields);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setFormItems($fields)
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除文档(不是彻底删除，而是移动到回收站)
     * @param null $ids 文档id
     * @param string $table 数据表
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function delete($ids = null, $table = '')
    {
        if ($ids === null) $this->error('参数错误');

        $document_id    = is_array($ids) ? '' : $ids;
        $document_title = Db::name($table)->where('id', 'in', $ids)->column('title');

        // 移动文档到回收站
        if (false === Db::name($table)->where('id', 'in', $ids)->setField('trash', 1)) {
            $this->error('删除失败');
        }

        // 删除并记录日志
        action_log('document_trash', $table, $document_id, UID, implode('、', $document_title));
        $this->success('删除成功');
    }

    /**
     * 启用文档
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用文档
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置文档状态：删除、禁用、启用
     * @param string $type 类型：enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $table_token = input('param._t', '');
        $table_token == '' && $this->error('缺少参数');
        !session('?'.$table_token) && $this->error('参数错误');

        $table_data     = session($table_token);
        $table_name     = $table_data['table'];
        $ids            = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $document_id    = is_array($ids) ? '' : $ids;
        $document_title = Db::name($table_name)->where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['document_'.$type, 'cms_document', $document_id, UID, implode('、', $document_title)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $table_token = input('param._t', '');
        $table_token == '' && $this->error('缺少参数');
        !session('?'.$table_token) && $this->error('参数错误');

        $table_data = session($table_token);
        $table      = $table_data['table'];
        $id         = input('post.pk', '');
        $field      = input('post.name', '');
        $value      = input('post.value', '');
        $document   = Db::name($table)->where('id', $id)->value($field);
        $details    = '表名(' . $table . ')，字段(' . $field . ')，原值(' . $document . ')，新值：(' . $value . ')';
        return parent::quickEdit(['document_edit', 'cms_document', $id, UID, $details]);
    }
}
