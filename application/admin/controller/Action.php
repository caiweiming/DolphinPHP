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
use app\admin\model\Action as ActionModel;
use app\admin\model\Module as ModuleModel;

/**
 * 行为管理控制器
 * @package app\admin\controller
 */
class Action extends Admin
{
    /**
     * 首页
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 数据列表
        $data_list = ActionModel::where($map)->order('id desc')->paginate();
        // 所有模块的名称和标题
        $list_module = ModuleModel::getModule();

        // 新增或编辑页面的字段
        $fields = [
            ['hidden', 'id'],
            ['select', 'module', '所属模块', '', $list_module],
            ['text', 'name', '行为标识', '由英文字母和下划线组成'],
            ['text', 'title', '行为名称', ''],
            ['textarea', 'remark', '行为描述'],
            ['textarea', 'rule', '行为规则', '不写则只记录日志'],
            ['textarea', 'log', '日志规则', '记录日志备注时按此规则来生成，支持[变量|函数]。目前变量有：user,time,model,record,data,details'],
            ['radio', 'status', '立即启用', '', ['否', '是'], 1]
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('行为管理') // 设置页面标题
            ->setSearch(['name' => '标识', 'title' => '名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '标识'],
                ['title', '名称'],
                ['remark', '描述'],
                ['module', '所属模块', 'callback', function($module, $list_module){
                    return isset($list_module[$module]) ? $list_module[$module] : '未知';
                }, $list_module],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->autoAdd($fields, '', true, true) // 添加自动新增按钮
            ->autoEdit($fields, '', true, true) // 添加自动编辑按钮
            ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->addFilter('module', $list_module)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }
}