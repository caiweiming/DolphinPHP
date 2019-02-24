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
use app\admin\model\Menu as MenuModel;
use think\Db;
use think\facade\Cache;

/**
 * 内容模型控制器
 * @package app\cms\admin
 */
class Model extends Admin
{
    /**
     * 内容模型列表
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 数据列表
        $data_list = DocumentModel::where($map)->order('sort,id desc')->paginate();

        // 字段管理按钮
        $btnField = [
            'title' => '字段管理',
            'icon'  => 'fa fa-fw fa-navicon',
            'href'  => url('field/index', ['id' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '标识', 'title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
               ['id', 'ID'],
               ['icon', '图标', 'icon'],
               ['title', '标题'],
               ['name', '标识'],
               ['table', '附加表'],
               ['type', '模型', 'text', '', ['系统', '普通', '独立']],
               ['create_time', '创建时间', 'datetime'],
               ['sort', '排序', 'text.edit'],
               ['status', '状态', 'switch'],
               ['right_button', '操作', 'btn']
            ])
            ->addFilter('type', ['系统', '普通', '独立'])
            ->addTopButtons('add,enable,disable') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'custom' => $btnField, 'delete' => ['data-tips' => '删除模型将同时删除该模型下的所有字段，且无法恢复。']]) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增内容模型
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if ($data['table'] == '') {
                $data['table'] = config('database.prefix') . 'cms_document_' . $data['name'];
            } else {
                $data['table'] = str_replace('#@__', config('database.prefix'), $data['table']);
            }

            // 验证
            $result = $this->validate($data, 'Model');
            if(true !== $result) $this->error($result);
            // 严格验证附加表是否存在
            if (table_exist($data['table'])) {
                $this->error('附加表已存在');
            }

            if ($model = DocumentModel::create($data)) {
                // 创建附加表
                if (false === DocumentModel::createTable($model)) {
                    $this->error('创建附加表失败');
                }
                // 创建菜单节点
                $map = [
                    'module' => 'cms',
                    'title'  => '内容管理'
                ];
                $menu_data = [
                    "module"      => "cms",
                    "pid"         => Db::name('admin_menu')->where($map)->value('id'),
                    "title"       => $data['title'],
                    "url_type"    => "module_admin",
                    "url_value"   => "cms/content/{$data['name']}",
                    "url_target"  => "_self",
                    "icon"        => "fa fa-fw fa-list",
                    "online_hide" => "0",
                    "sort"        => "100",
                ];
                MenuModel::create($menu_data);

                // 记录行为
                action_log('model_add', 'cms_model', $model['id'], UID, $data['title']);
                Cache::clear();
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $type_tips = '此选项添加后不可更改。如果为 <code>系统模型</code> 将禁止删除，对于 <code>独立模型</code>，将强制创建字段id,cid,uid,model,title,create_time,update_time,sort,status,trash,view';

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '模型标识', '由小写字母、数字或下划线组成，不能以数字开头'],
                ['text', 'title', '模型标题', '可填写中文'],
                ['text', 'table', '附加表', '创建后不可更改。由小写字母、数字或下划线组成，如果不填写默认为 <code>'. config('database.prefix') . 'cms_document_模型标识</code>，如果需要自定义，请务必填写系统表前缀，<code>#@__</code>表示当前系统表前缀'],
                ['radio', 'type', '模型类别', $type_tips, ['系统模型', '普通模型', '独立模型(不使用主表)'], 1],
                ['icon', 'icon', '图标'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                ['text', 'sort', '排序', '', 100],
            ])
            ->fetch();
    }

    /**
     * 编辑内容模型
     * @param null $id 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = null) {
        if ($id === null) $this->error('参数错误');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Model.edit');
            if(true !== $result) $this->error($result);

            if (DocumentModel::update($data)) {
                cache('cms_model_list', null);
                cache('cms_model_title_list', null);
                // 记录行为
                action_log('model_edit', 'cms_model', $id, UID, "ID({$id}),标题({$data['title']})");
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $list_model_type = ['系统模型', '普通模型', '独立模型(不使用主表)'];

        // 模型信息
        $info = DocumentModel::get($id);
        $info['type'] = $list_model_type[$info['type']];

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'name'],
                ['static', 'name', '模型标识'],
                ['static', 'type', '模型类别'],
                ['static', 'table', '附加表'],
                ['text', 'title', '模型标题', '可填写中文'],
                ['icon', 'icon', '图标'],
                ['radio', 'status', '立即启用', '', ['否', '是']],
                ['text', 'sort', '排序'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除内容模型
     * @param null $ids 内容模型id
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

        $model = DocumentModel::where('id', $ids)->find();
        if ($model['type'] == 0) {
            $this->error('禁止删除系统模型');
        }

        // 删除表和字段信息
        if (DocumentModel::deleteTable($ids)) {
            // 删除主表中的文档
            if (false === Db::name('cms_document')->where('model', $ids)->delete()) {
                $this->error('删除主表文档失败');
            }
            // 删除菜单节点
            $map = [
                'module'    => 'cms',
                'url_value' => "cms/content/{$model['name']}"
            ];
            if (false === Db::name('admin_menu')->where($map)->delete()) {
                $this->error('删除菜单节点失败');
            }
            // 删除字段数据
            if (false !== Db::name('cms_field')->where('model', $ids)->delete()) {
                cache('cms_model_list', null);
                cache('cms_model_title_list', null);
                return parent::delete();
            } else {
                $this->error('删除内容模型字段失败');
            }
        } else {
            $this->error('删除内容模型表失败');
        }
    }
}
