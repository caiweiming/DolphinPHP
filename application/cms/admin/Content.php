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
use app\cms\model\Document;
use think\Db;

/**
 * 内容控制器
 * @package app\cms\admin
 */
class Content extends Admin
{
    /**
     * 空操作，用于显示各个模型的文档列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _empty()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $model_name = $this->request->action();
        $model      = Db::name('cms_model')->where('name', $model_name)->find();
        if (!$model) $this->error('找不到该内容');

        // 独立模型
        if ($model['type'] == 2) {
            $table_name = substr($model['table'], strlen(config('database.prefix')));

            // 查询
            $map   = $this->getMap();
            $map[] = ['trash', '=', 0];

            // 排序
            $order = $this->getOrder('update_time desc');
            // 数据列表
            $data_list = Db::view($table_name, true)
                ->view("cms_column", ['name' => 'column_name'], 'cms_column.id='.$table_name.'.cid', 'left')
                ->view("admin_user", 'username', 'admin_user.id='.$table_name.'.uid', 'left')
                ->where($map)
                ->order($order)
                ->paginate();

            $trash_count = Db::table($model['table'])->where('trash', 1)->count();

            // 自定义按钮
            $btnRecycle = [
                'title' => '回收站('.$trash_count.')',
                'icon'  => 'fa fa-trash',
                'class' => 'btn btn-info',
                'href'  => url('recycle/index', ['model' => $model['id']])
            ];
            $columns = Db::name('cms_column')->where(['model' => $model['id']])->column('id,name');

            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->setSearch(['title' => '标题', 'cms_column.name' => '栏目名称']) // 设置搜索框
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['cid', '栏目名称', 'select', $columns],
                    ['view', '点击量'],
                    ['username', '发布人'],
                    ['update_time', '更新时间', 'datetime'],
                    ['sort', '排序', 'text.edit'],
                    ['status', '状态', 'switch'],
                    ['right_button', '操作', 'btn']
                ])
                ->setTableName($table_name)
                ->addTopButton('add', ['href' => url('document/add', ['model' => $model['id']])]) // 添加顶部按钮
                ->addTopButton('enable', ['href' => url('document/enable', ['table' => $table_name])]) // 添加顶部按钮
                ->addTopButton('disable', ['href' => url('document/disable', ['table' => $table_name])]) // 添加顶部按钮
                ->addTopButton('delete', ['href' => url('document/delete', ['table' => $table_name])]) // 添加顶部按钮
                ->addTopButton('custom', $btnRecycle) // 添加顶部按钮
                ->addRightButton('edit', ['href' => url('document/edit', ['model' => $model['id'], 'id' => '__id__'])]) // 添加右侧按钮
                ->addRightButton('delete', ['href' => url('document/delete', ['ids' => '__id__', 'table' => $table_name])]) // 添加右侧按钮
                ->addOrder('id,title,cid,view,username,update_time')
                ->addFilter('cid', $columns)
                ->addFilter(['username' => 'admin_user'])
                ->addFilterMap(['cid' => ['model' => $model['id']]])
                ->setRowList($data_list) // 设置表格数据
                ->fetch(); // 渲染模板
        } else {
            // 查询
            $map = $this->getMap();
            $map[] = ['cms_document.trash', '=', 0];
            $map[] = ['cms_document.model', '=', $model['id']];
            // 排序
            $order = $this->getOrder('update_time desc');
            // 数据列表
            $data_list = Document::getList($map, $order);

            $columns = Db::name('cms_column')->where(['model' => $model['id']])->column('id,name');

            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->setSearch(['title' => '标题', 'cms_column.name' => '栏目名称']) // 设置搜索框
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['cid', '栏目名称', 'select', $columns],
                    ['view', '点击量'],
                    ['username', '发布人'],
                    ['update_time', '更新时间', 'datetime'],
                    ['sort', '排序', 'text.edit'],
                    ['status', '状态', 'switch'],
                    ['right_button', '操作', 'btn']
                ])
                ->setTableName('cms_document')
                ->addTopButton('add', ['href' => url('document/add', ['model' => $model['id']])]) // 添加顶部按钮
                ->addTopButton('enable', ['href' => url('document/enable', ['table' => 'cms_document'])]) // 添加顶部按钮
                ->addTopButton('disable', ['href' => url('document/disable', ['table' => 'cms_document'])]) // 添加顶部按钮
                ->addTopButton('delete', ['href' => url('document/delete', ['table' => 'cms_document'])]) // 添加顶部按钮
                ->addRightButton('edit', ['href' => url('document/edit', ['id' => '__id__'])]) // 添加右侧按钮
                ->addRightButton('delete', ['href' => url('document/delete', ['ids' => '__id__', 'table' => 'cms_document'])]) // 添加右侧按钮
                ->addOrder('id,title,cid,view,username,update_time')
                ->addFilter('cid', $columns)
                ->addFilter(['username' => 'admin_user'])
                ->addFilterMap(['cid' => ['model' => $model['id']]])
                ->setRowList($data_list) // 设置表格数据
                ->fetch(); // 渲染模板
        }
    }
}
