<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\user\model\Message as MessageModel;
use app\user\model\User as UserModel;
use app\user\model\Role as RoleModel;

/**
 * 消息控制器
 * @package app\user\admin
 */
class Message extends Admin
{
    /**
     * 消息列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $data_list = MessageModel::where($this->getMap())
            ->order($this->getOrder('id DESC'))
            ->paginate();

        return ZBuilder::make('table')
            ->setTableName('admin_message')
            ->addTopButton('add')
            ->addTopButton('delete')
            ->addRightButton('edit')
            ->addRightButton('delete')
            ->addColumns([
                ['id', 'ID'],
                ['uid_receive', '接收者', 'callback', 'get_nickname'],
                ['uid_send', '发送者', 'callback', 'get_nickname'],
                ['type', '分类'],
                ['content', '内容'],
                ['status', '状态', 'status', '', ['未读', '已读']],
                ['create_time', '发送时间', 'datetime'],
                ['read_time', '阅读时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->addFilter('type')
            ->addFilter('status', ['未读', '已读'])
            ->setRowList($data_list)
            ->fetch();
    }

    /**
     * 新增
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['type'] == '' && $this->error('请填写消息分类');
            $data['content'] == '' && $this->error('请填写消息内容');

            $list = [];
            if ($data['send_type'] == 'uid') {
                !isset($data['uid']) && $this->error('请选择接收消息的用户');
            } else {
                !isset($data['role']) && $this->error('请选择接收消息的角色');
                $data['uid'] = UserModel::where('status', 1)
                    ->where('role', 'in', $data['role'])
                    ->column('id');
                !$data['uid'] && $this->error('所选角色无可发送的用户');
            }

            foreach ($data['uid'] as $uid) {
                $list[] = [
                    'uid_receive' => $uid,
                    'uid_send'    => UID,
                    'type'        => $data['type'],
                    'content'     => $data['content'],
                ];
            }

            $MessageModel = new MessageModel;
            if (false !== $MessageModel->saveAll($list)) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'type', '消息分类'],
                ['textarea', 'content', '消息内容'],
                ['radio', 'send_type', '发送方式', '', ['uid' => '按指定用户', 'role' => '按指定角色'], 'uid'],
                ['select', 'uid', '接收用户', '接收消息的用户', UserModel::where('status', 1)->column('id,nickname'), '', 'multiple'],
                ['select', 'role', '接收角色', '接收消息的角色', RoleModel::where('status', 1)->column('id,name'), '', 'multiple'],
            ])
            ->setTrigger('send_type', 'uid', 'uid')
            ->setTrigger('send_type', 'role', 'role')
            ->fetch();
    }
}
