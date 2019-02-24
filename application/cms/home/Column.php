<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\cms\home;

use app\cms\model\Column as ColumnModel;
use think\Db;
use util\Tree;

/**
 * 前台栏目文档列表控制器
 * @package app\cms\admin
 */
class Column extends Common
{
    /**
     * 栏目文章列表
     * @param null $id 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        $map = [
            'status' => 1,
            'id'     => $id
        ];

        $column = Db::name('cms_column')->where($map)->find();
        if (!$column) $this->error('该栏目不存在');

        $model = Db::name('cms_model')->where('id', $column['model'])->find();

        if ($model['type'] == 2) {
            $cid_all   = ColumnModel::getChildsId($id);
            $cid_all[] = (int)$id;

            $map = [
                [$model['table'].'.trash', '=', 0],
                [$model['table'].'.status', '=', 1],
                [$model['table'].'.cid', 'in', $cid_all],
            ];

            $data_list = Db::view($model['table'], true)
                ->view('admin_user', 'username', $model['table'].'.uid=admin_user.id', 'left')
                ->where($map)
                ->order('create_time desc')
                ->paginate(config('list_rows'));
            $this->assign('model', $column['model']);
        } else {
            $cid_all   = ColumnModel::getChildsId($id);
            $cid_all[] = (int)$id;

            $map = [
                ['cms_document.trash', '=', 0],
                ['cms_document.status', '=', 1],
                ['cms_document.cid', 'in', $cid_all],
            ];

            $data_list = Db::view('cms_document', true)
                ->view('admin_user', 'username', 'cms_document.uid=admin_user.id', 'left')
                ->view($model['table'], '*', 'cms_document.id='. $model['table'] . '.aid', 'left')
                ->where($map)
                ->order('create_time desc')
                ->paginate(config('list_rows'));
            $this->assign('model', '');
        }

        $this->assign('lists', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('breadcrumb', $this->getBreadcrumb($id));
        $this->assign('column_info', $column);

        $template = $column['list_template'] == '' ? 'list' : substr($column['list_template'], 0, strpos($column['list_template'], '.'));
        return $this->fetch($template);
    }

    /**
     * 获取栏目面包屑导航
     * @param $id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function getBreadcrumb($id)
    {
        $columns = ColumnModel::where('status', 1)->column('id,pid,name,url,target,type');
        foreach ($columns as &$column) {
            if ($column['type'] == 0) {
                $column['url'] = url('cms/column/index', ['id' => $column['id']]);
            }
        }

        return Tree::config(['title' => 'name'])->getParents($columns, $id);
    }
}
