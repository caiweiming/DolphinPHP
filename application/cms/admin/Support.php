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
use app\cms\model\Support as SupportModel;

/**
 * 客服控制器
 * @package app\cms\admin
 */
class Support extends Admin
{
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = SupportModel::where($map)->order($order)->paginate();

        $search = [
            'name'    => '客服名称',
            'qq'      => 'QQ',
            'msn'     => 'MSN',
            'taobao'  => '淘宝旺旺',
            'alibaba' => '阿里旺旺',
            'skype'   => 'SKYPE'
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTips('添加的QQ需要到【shang.qq.com】登录后在【商家沟通组建—设置】开启QQ的在线状态，否则将显示“未启用”<br>开启和关闭在线客服功能，以及更多设置，请在 <a class="alert-link link-effect" href="'.url('admin/system/index', ['group' => 'cms']).'">系统设置</a> 中操作。')
            ->setSearch($search) // 设置搜索框
            ->addColumns([ // 批量添加数据列
               ['id', 'ID'],
               ['name', '客服名称', 'text.edit'],
               ['qq', 'QQ'],
               ['msn', 'MSN'],
               ['taobao', '淘宝旺旺'],
               ['alibaba', '阿里旺旺'],
               ['skype', 'SKYPE'],
               ['create_time', '创建时间', 'datetime'],
               ['sort', '排序', 'text.edit'],
               ['status', '状态', 'switch'],
               ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,name,create_time,update_time')
            ->addValidate('Support', 'name')
            ->setRowList($data_list) // 设置表格数据
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
            $result = $this->validate($data, 'Support');
            if(true !== $result) $this->error($result);

            if ($support = SupportModel::create($data)) {
                // 记录行为
                action_log('support_add', 'cms_support', $support['id'], UID, $data['name']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '客服名称'],
                ['text', 'qq', 'QQ号码'],
                ['text', 'msn', 'MSN号码'],
                ['text', 'taobao', '淘宝旺旺'],
                ['text', 'alibaba', '阿里旺旺'],
                ['text', 'skype', 'SKYPE'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 客服id
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
            $result = $this->validate($data, 'Support');
            if(true !== $result) $this->error($result);

            if (SupportModel::update($data)) {
                // 记录行为
                action_log('support_edit', 'cms_support', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = SupportModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '客服名称'],
                ['text', 'qq', 'QQ号码'],
                ['text', 'msn', 'MSN号码'],
                ['text', 'taobao', '淘宝旺旺'],
                ['text', 'alibaba', '阿里旺旺'],
                ['text', 'skype', 'SKYPE'],
                ['text', 'sort', '排序'],
                ['radio', 'status', '立即启用', '', ['否', '是']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除客服
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete');
    }

    /**
     * 启用客服
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
     * 禁用客服
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
     * 设置客服状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids           = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $support_title = SupportModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['support_'.$type, 'cms_support', 0, UID, implode('、', $support_title)]);
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
        $support = SupportModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $support . ')，新值：(' . $value . ')';
        return parent::quickEdit(['support_edit', 'cms_support', $id, UID, $details]);
    }
}
