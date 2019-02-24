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
use app\cms\model\Document as DocumentModel;
use think\Db;

/**
 * 回收站控制器
 * @package app\cms\admin
 */
class Recycle extends Admin
{
    /**
     * 文档列表
     * @param string $model 内容模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index($model = '')
    {
        if ($model == '') {
            // 查询
            $map   = $this->getMap();
            $map[] = ['cms_document.trash', '=', 1];
            // 排序
            $order = $this->getOrder('update_time desc');
            // 数据列表
            $data_list = DocumentModel::getList($map, $order);

            // 自定义按钮
            $btnRestore = [
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'icon'  => 'fa fa-fw fa-reply',
                'title' => '还原',
                'href'  => url('restore', ['ids' => '__id__'])
            ];
            $btnRestoreAll = [
                'class' => 'btn btn-success ajax-post confirm',
                'icon'  => 'fa fa-fw fa-reply-all',
                'title' => '批量还原',
                'href'  => url('restore')
            ];

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
                    ['right_button', '操作', 'btn']
                ])
                ->addTopButton('enable', $btnRestoreAll) // 批量添加顶部按钮
                ->addTopButton('delete', ['title' => '批量删除', 'href' => url('delete'), 'data-tips' => '删除后不可回复！']) // 批量添加顶部按钮
                ->addRightButton('custom', $btnRestore) // 添加右侧按钮
                ->addRightButton('delete', ['href' => url('delete', ['ids' => '__id__']), 'data-tips' => '删除后不可回复！']) // 添加右侧按钮
                ->addOrder('id,title,column_name,view,username,update_time')
                ->addFilter(['column_name' => 'cms_column.name', 'username' => 'admin_user'])
                ->setRowList($data_list) // 设置表格数据
                ->fetch(); // 渲染模板
        } else {
            $table_name = get_model_table($model);

            // 查询
            $map   = $this->getMap();
            $map[] = ['trash', '=', 1];

            // 排序
            $order = $this->getOrder('update_time desc');
            // 数据列表
            $data_list = Db::view($table_name, true)
                ->view("cms_column", ['name' => 'column_name'], 'cms_column.id='.$table_name.'.cid', 'left')
                ->view("admin_user", 'username', 'admin_user.id='.$table_name.'.uid', 'left')
                ->where($map)
                ->order($order)
                ->paginate();

            // 自定义按钮
            $btnRestore = [
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'icon'  => 'fa fa-fw fa-reply',
                'title' => '还原',
                'href'  => url('restore', ['table' => $table_name, 'ids' => '__id__'])
            ];
            $btnRestoreAll = [
                'class' => 'btn btn-success ajax-post confirm',
                'icon'  => 'fa fa-fw fa-reply-all',
                'title' => '批量还原',
                'href'  => url('restore', ['table' => $table_name])
            ];

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
                    ['right_button', '操作', 'btn']
                ])
                ->addTopButton('enable', $btnRestoreAll) // 添加顶部按钮
                ->addTopButton('delete', ['title' => '批量删除', 'href' => url('delete', ['table' => $table_name]), 'data-tips' => '删除后不可回复！']) // 添加顶部按钮
                ->addRightButton('custom', $btnRestore) // 添加右侧按钮
                ->addRightButton('delete', ['href' => url('delete', ['ids' => '__id__', 'table' => $table_name]), 'data-tips' => '删除后不可回复！']) // 添加右侧按钮
                ->addOrder('id,title,column_name,view,username,update_time')
                ->addFilter(['column_name' => 'cms_column.name', 'username' => 'admin_user'])
                ->setRowList($data_list) // 设置表格数据
                ->fetch(); // 渲染模板
        }
    }

    /**
     * 还原文档
     * @param null $ids 文档id
     * @param string $table 表名
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function restore($ids = null, $table = '')
    {
        if ($ids === null) $this->error('请选择要操作的数据');
        $table = $table != '' ? substr($table, strlen(config('database.prefix'))) : 'cms_document';

        $document_id    = is_array($ids) ? '' : $ids;
        $document_title = Db::name($table)->where('id', 'in', $ids)->column('title');

        // 还原文档
        if (false === Db::name($table)->where('id', 'in', $ids)->setField('trash', 0)) {
            $this->error('还原失败');
        }

        // 删除并记录日志
        action_log('document_restore', $table, $document_id, UID, implode('、', $document_title));
        $this->success('还原成功');
    }

    /**
     * 彻底删除文档
     * @param null $ids 文档id
     * @param string $table 表名
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($ids = null, $table = '')
    {
        if ($ids === null) $this->error('请选择要操作的数据');
        $ids = is_array($ids) ? $ids : (array)$ids;

        if ($table == '') {
            // 获取文档标题和模型id
            $data_list = Db::name('cms_document')->where('id', 'in', $ids)->column('id,model,title');

            foreach ($data_list as $document) {
                // 附加表名
                $extra_table = get_model_table($document['model']);

                // 删除附加表文档
                if (false === Db::table($extra_table)->where('aid', $document['id'])->delete()) {
                    $this->error('删除文档：'. $document['title']. ' 失败');
                }

                // 删除主表文档
                if (false === Db::name('cms_document')->where('id', $document['id'])->delete()) {
                    $this->error('删除失败');
                }

                // 记录行为
                action_log('document_delete', 'cms_document', $document['id'], UID, $document['title']);
            }
        } else {
            // 文档标题
            $document_title = Db::table($table)->where('id', 'in', $ids)->column('title');

            // 删除独立文档
            if (false === Db::table($table)->where('id', 'in', $ids)->delete()) {
                $this->error('删除失败');
            }

            // 记录行为
            action_log('document_delete', $table, 0, UID, '表('.$table.'),文档('.implode('、', $document_title).')');
        }
        $this->success('删除成功');
    }
}
