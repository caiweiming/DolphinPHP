<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\admin\model\Log as LogModel;

/**
 * 系统日志控制器
 * @package app\admin\controller
 */
class Log extends Admin
{
    /**
     * 日志列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('admin_log.id desc');
        // 数据列表
        $data_list = LogModel::getAll($map, $order);
        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('系统日志') // 设置页面标题
            ->setSearch(['admin_action.title' => '行为名称', 'admin_user.username' => '执行者', 'admin_module.title' => '所属模块']) // 设置搜索框
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', '编号'],
                ['title', '行为名称'],
                ['username', '执行者'],
                ['action_ip', '执行IP', 'callback', function($value){
                    return long2ip(intval($value));
                }],
                ['module_title', '所属模块'],
                ['create_time', '执行时间', 'datetime', '', 'Y-m-d H:i:s'],
                ['right_button', '操作', 'btn']
            ])
            ->addOrder(['title' => 'admin_action', 'username' => 'admin_user', 'module_title' => 'admin_module.title'])
            ->addFilter(['admin_action.title', 'admin_user.username', 'module_title' => 'admin_module.title'])
            ->addRightButton('edit', ['icon' => 'fa fa-eye', 'title' => '详情', 'href' => url('details', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染模板
    }

    /**
     * 日志详情
     * @param null $id 日志id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function details($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        $info = LogModel::getAll(['admin_log.id' => $id]);
        $info = $info[0];
        $info['action_ip'] = long2ip(intval($info['action_ip']));

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                 ['hidden', 'id'],
                 ['static', 'title', '行为名称'],
                 ['static', 'username', '执行者'],
                 ['static', 'record_id', '目标ID'],
                 ['static', 'action_ip', '执行IP'],
                 ['static', 'module_title', '所属模块'],
                 ['textarea', 'remark', '备注'],
            ])
            ->hideBtn('submit')
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}