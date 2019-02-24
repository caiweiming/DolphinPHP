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
use app\cms\model\Nav as NavModel;
use app\cms\model\Menu as MenuModel;

/**
 * 导航控制器
 * @package app\cms\admin
 */
class Nav extends Admin
{
    /**
     * 导航列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = NavModel::where($map)->order($order)->paginate();

        // 自定义按钮
        $btnMenuList = [
            'title' => '菜单列表',
            'icon'  => 'fa fa-list',
            'href'  => url('menu/index', ['id' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['tag', '标识', 'text.edit'],
                ['title', '标题', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')// 批量添加顶部按钮
            ->addRightButton('custom', $btnMenuList)
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->addOrder('id,title,create_time,update_time')
            ->setRowList($data_list)// 设置表格数据
            ->addValidate('Nav', 'tag,title')
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Nav');
            if(true !== $result) $this->error($result);

            if ($nav = NavModel::create($data)) {
                // 记录行为
                action_log('nav_add', 'cms_nav', $nav['id'], UID, $data['title']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'tag', '菜单标识', '由字母和下划线组成，如：main_nav'],
                ['text', 'title', '菜单标题', '必填'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
    }

    /**
     * 删除导航
     * @param null $ids 菜单id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('参数错误');
        // 删除该导航的所有子菜单
        if (false === MenuModel::where('nid', 'in', $ids)->delete()) {
            $this->error('删除失败');
        }
        return $this->setStatus('delete');
    }

    /**
     * 启用导航
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
     * 禁用导航
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
     * 设置导航状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids        = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $nav_title = NavModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['nav_'.$type, 'cms_nav', 0, UID, implode('、', $nav_title)]);
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
        $nav     = NavModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $nav . ')，新值：(' . $value . ')';
        return parent::quickEdit(['nav_edit', 'cms_nav', $id, UID, $details]);
    }
}
