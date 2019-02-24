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
use app\cms\model\Model as DocumentModel;
use app\cms\model\Column as ColumnModel;
use app\cms\model\Document;
use app\user\model\Role as RoleModel;
use util\Tree;
use util\File;
use think\facade\Env;

/**
 * 栏目控制器
 * @package app\cms\admin
 */
class Column extends Admin
{
    /**
     * 栏目列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();

        // 数据列表
        $data_list = ColumnModel::where($map)->column(true);
        if (empty($map)) {
            $data_list = Tree::config(['title' => 'name'])->toList($data_list);
        }

        // 自定义按钮
        $btnMove = [
            'class' => 'btn btn-xs btn-default js-move-column',
            'icon'  => 'fa fa-fw fa-arrow-circle-right',
            'title' => '移动栏目'
        ];
        $btnAdd = [
            'class' => 'btn btn-xs btn-default',
            'icon'  => 'fa fa-fw fa-plus',
            'title' => '新增子栏目',
            'href'  => url('add', ['pid' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '栏目名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
               ['id', 'ID'],
               ['icon', '图标', 'icon'],
               ['name', '栏目名称', 'callback', function($value, $data){
                   return isset($data['title_prefix']) ? $data['title_display'] : $value;
               }, '__data__'],
               ['model', '内容模型', 'select', DocumentModel::getTitleList()],
               ['rank_auth', '浏览权限', 'select', RoleModel::getTree(null, '开放浏览')],
               ['hide', '是否隐藏', 'yesno'],
               ['post_auth', '支持投稿', 'yesno'],
               ['create_time', '创建时间', 'datetime'],
               ['sort', '排序', 'text.edit'],
               ['status', '状态', 'switch'],
               ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable') // 批量添加顶部按钮
            ->addRightButton('custom', $btnAdd)
            ->addRightButton('edit') // 添加右侧按钮
//            ->addRightButton('custom', $btnMove)
            ->addRightButton('delete', ['data-tips' => '删除栏目前，请确保无子栏目和文档！']) // 添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增栏目
     * @param int $pid 父级id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');
            if(true !== $result) $this->error($result);

            if ($column = ColumnModel::create($data)) {
                cache('cms_column_list', null);
                // 记录行为
                action_log('column_add', 'cms_column', $column['id'], UID, $data['name']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $template_list   = File::get_dirs(Env::get('app_path').'cms/view/column/')['file'];
        $template_detail = File::get_dirs(Env::get('app_path').'cms/view/document/')['file'];

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'pid', '所属栏目', '<span class="text-danger">必选</span>', ColumnModel::getTreeList(), $pid],
                ['text', 'name', '栏目名称', '<span class="text-danger">必填</span>'],
                ['radio', 'model', '内容模型', '<span class="text-danger">必选</span>', DocumentModel::getTitleList()],
                ['radio', 'type', '栏目属性', '', ['最终列表栏目', '外部链接'], 0],
                ['text', 'url', '链接', '可以填写完整的url，如：<code>http://www.dolphinphp.com</code>，也可以填写 <code>模块/控制器/操作</code>，如：<code>cms/index/index</code>'],
                ['radio', 'target', '打开方式', '', ['_self' => '当前窗口', '_blank' => '新窗口'], '_self'],
//                ['select', 'index_template', '封面页模板', '可选'],
                ['select', 'list_template', '列表页模板', '可选，模板目录： <code>cms/view/column</code>', parse_array($template_list)],
                ['select', 'detail_template', '详情页模板', '可选，模板目录： <code>cms/view/document</code>', parse_array($template_detail)],
                ['ckeditor', 'content', '栏目内容', '可作为单页使用'],
                ['icon', 'icon', '图标'],
                ['radio', 'post_auth', '是否支持投稿', '是否允许前台用户投稿', ['禁止投稿', '允许投稿'], 1],
                ['radio', 'hide', '是否隐藏栏目', '隐藏后前台不可见', ['显示', '隐藏'], 0],
                ['select', 'rank_auth', '浏览权限', '', RoleModel::getTree(null, '开放浏览'), 0],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                ['text', 'sort', '排序', '', 100],
            ])
            ->setTrigger('type', '0,2', 'index_template,list_template,detail_template')
            ->setTrigger('type', '1', 'url,target')
            ->fetch();
    }

    /**
     * 编辑栏目
     * @param string $id 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = '')
    {
        if ($id === 0) $this->error('参数错误');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if (ColumnModel::update($data)) {
                // 记录行为
                action_log('column_edit', 'cms_column', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = ColumnModel::get($id);

        $template_list   = File::get_dirs(Env::get('app_path').'cms/view/column/')['file'];
        $template_detail = File::get_dirs(Env::get('app_path').'cms/view/document/')['file'];

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'pid', '所属栏目', '<span class="text-danger">必选</span>', ColumnModel::getTreeList($id)],
                ['text', 'name', '栏目名称', '<span class="text-danger">必填</span>'],
                ['radio', 'model', '内容模型', '<span class="text-danger">必选</span>', DocumentModel::getTitleList()],
                ['radio', 'type', '栏目属性', '', ['最终列表栏目', '外部链接'], 0],
                ['text', 'url', '链接', '可以填写完整的url，如：<code>http://www.dolphinphp.com</code>，也可以填写 <code>模块/控制器/操作</code>，如：<code>cms/index/index</code>'],
                ['radio', 'target', '打开方式', '', ['_self' => '当前窗口', '_blank' => '新窗口'], '_self'],
//                ['select', 'index_template', '封面页模板', '可选'],
                ['select', 'list_template', '列表页模板', '可选，模板目录： <code>cms/view/column</code>', parse_array($template_list)],
                ['select', 'detail_template', '详情页模板', '可选，模板目录： <code>cms/view/document</code>', parse_array($template_detail)],
                ['ckeditor', 'content', '栏目内容', '可作为单页使用'],
                ['icon', 'icon', '图标'],
                ['radio', 'post_auth', '是否支持投稿', '是否允许前台用户投稿', ['禁止投稿', '允许投稿']],
                ['radio', 'hide', '是否隐藏栏目', '隐藏后前台不可见', ['显示', '隐藏'], 0],
                ['select', 'rank_auth', '浏览权限', '', RoleModel::getTree(null, '开放浏览')],
                ['radio', 'status', '立即启用', '', ['否', '是']],
                ['text', 'sort', '排序'],
            ])
            ->setTrigger('type', '0,2', 'index_template,list_template,detail_template')
            ->setTrigger('type', '1', 'url,target')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除栏目
     * @param null $ids 栏目id
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('参数错误');

        // 检查是否有子栏目
        if (ColumnModel::where('pid', $ids)->find()) {
            $this->error('请先删除或移动该栏目下的子栏目');
        }

        // 检查是否有文档
        if (Document::where('cid', $ids)->find()) {
            $this->error('请先删除或移动该栏目下的所有文档');
        }

        // 删除并记录日志
        $column_name = get_column_name($ids);
        return parent::delete(['column_delete', 'cms_column', 0, UID, $column_name]);
    }

    /**
     * 启用栏目
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
     * 禁用栏目
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
     * 设置栏目状态：删除、禁用、启用
     * @param string $type 类型：enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids           = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $column_delete = is_array($ids) ? '' : $ids;
        $column_names  = ColumnModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['column_'.$type, 'cms_column', $column_delete, UID, implode('、', $column_names)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $column  = ColumnModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $column . ')，新值：(' . $value . ')';
        return parent::quickEdit(['column_edit', 'cms_column', $id, UID, $details]);
    }
}
