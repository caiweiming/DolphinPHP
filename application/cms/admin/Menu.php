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
use app\cms\model\Menu as MenuModel;
use app\cms\model\Column as ColumnModel;
use app\cms\model\Page as PageModel;
use util\Tree;
use think\Db;

/**
 * 菜单控制器
 * @package app\cms\admin
 */
class Menu extends Admin
{
    /**
     * 菜单列表
     * @param null $id 导航id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 查询
        $map = $this->getMap();

        // 数据列表
        $data_list = Db::view('cms_menu', true)
            ->view('cms_column', ['name' => 'column_name'], 'cms_menu.column=cms_column.id', 'left')
            ->view('cms_page', ['title' => 'page_title'], 'cms_menu.page=cms_page.id', 'left')
            ->where('cms_menu.nid', $id)
            ->order('cms_menu.sort,cms_menu.pid,cms_menu.id')
            ->select();

        foreach ($data_list as &$item) {
            if ($item['type'] == 0) {
                $item['title'] = $item['column_name'];
            } elseif ($item['type'] == 1) {
                $item['title'] = $item['page_title'];
            }
        }

        if (empty($map)) {
            $data_list = Tree::toList($data_list);
        }

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '新增子菜单', 'href' => url('add', ['nid' => $id, 'pid' => '__id__'])];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['cms_menu.title|cms_column.name|cms_page.title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题', 'callback', function($value, $data){
                    return isset($data['title_prefix']) ? $data['title_display'] : $value;
                }, '__data__'],
                ['type', '类型', 'text', '', ['栏目链接', '单页链接', '自定义链接']],
                ['target', '打开方式', 'select', ['_self' => '当前窗口', '_blank' => '新窗口']],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['sort', '排序', 'text.edit'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('back', ['href' => url('nav/index')])
            ->addTopButton('add', ['href' => url('add', ['nid' => $id])])
            ->addTopButtons('enable,disable')// 批量添加顶部按钮
            ->addRightButton('custom', $btnAdd)
            ->addRightButton('edit')
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->addValidate('Nav', 'title')
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @param null $nid 导航id
     * @param int $pid 菜单父级id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add($nid = null, $pid = 0)
    {
        if ($nid === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Menu');
            if(true !== $result) $this->error($result);

            if ($menu = MenuModel::create($data)) {
                // 记录行为
                action_log('menu_add', 'cms_menu', $menu['id'], UID, $data['title']);
                $this->success('新增成功', url('index', ['id' => $nid]));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'nid', $nid],
                ['hidden', 'pid', $pid],
                ['radio', 'type', '类型', '', ['栏目链接', '单页链接', '自定义链接'], 0],
                ['select', 'column', '栏目', '<code>必选</code>', ColumnModel::getTreeList(0, false)],
                ['select', 'page', '单页', '<code>必选</code>', PageModel::getTitleList()],
                ['text', 'title', '菜单标题', '<code>必填</code>，只用于区分'],
                ['text', 'url', 'URL', "<code>必填</code>。如果是模块链接，请填写<code>模块/控制器/操作</code>，如：<code>admin/menu/add</code>。如果是普通链接，则直接填写url地址，如：<code>http://www.dolphinphp.com</code>"],
                ['text', 'css', 'CSS类', '可选'],
                ['text', 'rel', '链接关系网（XFN）', '可选，即链接的rel值'],
                ['radio', 'target', '打开方式', '', ['_self' => '当前窗口', '_blank' => '新窗口'], '_self'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->setTrigger('type', '0', 'column')
            ->setTrigger('type', '1', 'page')
            ->setTrigger('type', '2', 'title,url')
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 菜单id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Menu');
            if(true !== $result) $this->error($result);

            if (MenuModel::update($data)) {
                // 记录行为
                action_log('menu_edit', 'cms_menu', $id, UID, $data['title']);
                $this->success('编辑成功', url('index', ['id' => $data['nid']]));
            } else {
                $this->error('编辑失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'nid'],
                ['radio', 'type', '类型', '', ['栏目链接', '单页链接', '自定义链接']],
                ['select', 'column', '栏目', '<code>必选</code>', ColumnModel::getTreeList(0, false)],
                ['select', 'page', '单页', '<code>必选</code>', PageModel::getTitleList()],
                ['text', 'title', '菜单标题', '<code>必填</code>，只用于区分'],
                ['text', 'url', 'URL', "<code>必填</code>。如果是模块链接，请填写<code>模块/控制器/操作</code>，如：<code>admin/menu/add</code>。如果是普通链接，则直接填写url地址，如：<code>http://www.dolphinphp.com</code>"],
                ['text', 'css', 'CSS类', '可选'],
                ['text', 'rel', '链接关系网（XFN）', '可选，即链接的rel值'],
                ['radio', 'target', '打开方式', '', ['_self' => '当前窗口', '_blank' => '新窗口'], '_self'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->setFormData(MenuModel::get($id))
            ->setTrigger('type', '0', 'column')
            ->setTrigger('type', '1', 'page')
            ->setTrigger('type', '2', 'title,url')
            ->fetch();
    }

    /**
     * 删除菜单
     * @param null $ids 菜单id
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($ids = null)
    {
        // 检查是否有子菜单
        if (MenuModel::where('pid', $ids)->find()) {
            $this->error('请先删除或移动该菜单下的子菜单');
        }
        return $this->setStatus('delete');
    }

    /**
     * 启用菜单
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
     * 禁用菜单
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
     * 设置菜单状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids        = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $menu_title = MenuModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['menu_'.$type, 'cms_menu', 0, UID, implode('、', $menu_title)]);
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
        $menu    = MenuModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $menu . ')，新值：(' . $value . ')';
        return parent::quickEdit(['menu_edit', 'cms_menu', $id, UID, $details]);
    }
}
