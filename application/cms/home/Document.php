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
use app\cms\model\Document as DocumentModel;
use util\Tree;
use think\Db;

/**
 * 文档控制器
 * @package app\cms\home
 */
class Document extends Common
{
    /**
     * 文档详情页
     * @param null $id 文档id
     * @param string $model 独立模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function detail($id = null, $model = '')
    {
        if ($id === null) $this->error('缺少参数');

        if ($model != '') {
            $table = get_model_table($model);
            $map = [
                $table.'.status' => 1,
                $table.'.trash'  => 0
            ];
        } else {
            $map = [
                'cms_document.status' => 1,
                'cms_document.trash'  => 0
            ];
        }

        $info = DocumentModel::getOne($id, $model, $map);
        if (isset($info['tags'])) {
            $info['tags'] = explode(',', $info['tags']);
        }

        $this->assign('document', $info);
        $this->assign('breadcrumb', $this->getBreadcrumb($info['cid']));
        $this->assign('prev', $this->getPrev($id, $model));
        $this->assign('next', $this->getNext($id, $model));

        $template = $info['detail_template'] == '' ? 'detail' : substr($info['detail_template'], 0, strpos($info['detail_template'], '.'));
        return $this->fetch($template);
    }

    /**
     * 获取栏目面包屑导航
     * @param int $id 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    private function getBreadcrumb($id)
    {
        $columns = ColumnModel::where('status', 1)->column('id,pid,name,url,target,type');
        foreach ($columns as &$column) {
            if ($column['type'] == 0) {
                $column['url'] = url('cms/column/index', ['id' => $column['id']]);
            }
        }
        return Tree::config(['title' => 'name'])->getParents($columns, $id);
    }

    /**
     * 获取上一篇文档
     * @param int $id 当前文档id
     * @param string $model 独立模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getPrev($id, $model = '')
    {
        if ($model == '') {
            $cid = Db::name('cms_document')->where('id', $id)->value('cid');
            $document = Db::name('cms_document')->where([
                ['status', '=', 1],
                ['trash', '=', 0],
                ['cid', '=', $cid],
                ['id', 'lt', $id]
            ])->order('id desc')->find();
        } else {
            $table = get_model_table($model);
            $cid   = Db::table($table)->where('id', $id)->value('cid');
            $document = Db::table($table)->where([
                ['status', '=', 1],
                ['trash', '=', 0],
                ['cid', '=', $cid],
                ['id', 'lt', $id]
            ])->order('id desc')->find();
        }

        if ($document) {
            $document['url'] = url('cms/document/detail', ['id' => $document['id'], 'model' => $model]);
        }
        return $document;
    }

    /**
     * 获取下一篇文档
     * @param int $id 当前文档id
     * @param string $model 独立模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getNext($id, $model = '')
    {
        if ($model == '') {
            $cid = Db::name('cms_document')->where('id', $id)->value('cid');
            $document = Db::name('cms_document')->where([
                ['status', '=', 1],
                ['trash', '=', 0],
                ['cid', '=', $cid],
                ['id', 'gt', $id]
            ])->find();
        } else {
            $table = get_model_table($model);
            $cid   = Db::table($table)->where('id', $id)->value('cid');
            $document = Db::table($table)->where([
                ['status', '=', 1],
                ['trash', '=', 0],
                ['cid', '=', $cid],
                ['id', 'gt', $id]
            ])->find();
        }

        if ($document) {
            $document['url'] = url('cms/document/detail', ['id' => $document['id'], 'model' => $model]);
        }

        return $document;
    }
}
