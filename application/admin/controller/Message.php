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
use app\user\model\Message as MessageModel;

/**
 * 消息控制器
 * @package app\admin\controller
 */
class Message extends Admin
{
    /**
     * 消息中心
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $data_list = MessageModel::where($this->getMap())
            ->where('uid_receive', UID)
            ->order($this->getOrder('id DESC'))
            ->paginate();

        return ZBuilder::make('table')
            ->setTableName('admin_message')
            ->addTopButton('enable', ['title' => '设置已阅读'])
            ->addTopButton('delete')
            ->addRightButton('enable', ['title' => '设置已阅读'])
            ->addRightButton('delete')
            ->addColumns([
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
     * 设置已阅读
     * @param array $ids
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($ids = [])
    {
        empty($ids) && $this->error('参数错误');
        $map = [
            ['uid_receive', '=', UID],
            ['id', 'in', $ids]
        ];
        $result = MessageModel::where($map)
            ->update(['status' => 1, 'read_time' => $this->request->time()]);
        if (false !== $result) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }
}