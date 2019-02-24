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
use app\admin\model\Packet as PacketModel;

/**
 * 数据包控制器
 * @package app\admin\controller
 */
class Packet extends Admin
{
    /**
     * 首页
     * @param string $group 分组
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed|string
     * @throws \think\Exception
     */
    public function index($group = 'local')
    {
        // 配置分组信息
        $list_group = ['local' => '本地数据包'];
        $tab_list   = [];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['url']   = url('index', ['group' => $key]);
        }

        $PacketModel = new PacketModel;
        $data_list = $PacketModel->getAll();
        foreach ($data_list as &$value) {
            if (isset($value['author_url']) && !empty($value['author_url'])) {
                $value['author'] = '<a href="'. $value['author_url']. '" target="_blank">'. $value['author'] .'</a>';
            }
        }

        if ($data_list === false) {
            $this->error($PacketModel->getError());
        }

        // 自定义按钮
        $btn_install = [
            'title' => '安装',
            'icon'  => 'fa fa-fw fa-sign-in',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('install', ['name' => '__id__'])
        ];
        $btn_uninstall = [
            'title' => '卸载',
            'icon'  => 'fa fa-fw fa-sign-out',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('uninstall', ['name' => '__id__'])
        ];
        $btn_install_all = [
            'title' => '安装',
            'icon'  => 'fa fa-fw fa-sign-in',
            'class' => 'btn btn-primary ajax-post confirm',
            'href'  => url('install')
        ];
        $btn_uninstall_all = [
            'title' => '卸载',
            'icon'  => 'fa fa-fw fa-sign-out',
            'class' => 'btn btn-danger ajax-post confirm',
            'href'  => url('uninstall')
        ];

        switch ($group) {
            case 'local':
                // 使用ZBuilder快速创建数据表格
                return ZBuilder::make('table')
                    ->setPageTitle('数据包管理') // 设置页面标题
                    ->setPrimaryKey('name')
                    ->setTabNav($tab_list, $group) // 设置tab分页
                    ->addColumns([ // 批量添加数据列
                        ['name', '名称'],
                        ['title', '标题'],
                        ['author', '作者'],
                        ['version', '版本号'],
                        ['status', '是否安装', 'yesno'],
                        ['right_button', '操作', 'btn']
                    ])
                    ->addTopButton('custom', $btn_install_all)
                    ->addTopButton('custom', $btn_uninstall_all)
                    ->addRightButton('custom', $btn_install) // 添加右侧按钮
                    ->addRightButton('custom', $btn_uninstall) // 添加右侧按钮
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch(); // 渲染模板
                break;
            case 'online':
                return '<h2>正在制作中...</h2>';
                break;
        }
    }

    /**
     * 安装
     * @param string $name 数据包名
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function install($name = '')
    {
        $names = $name != '' ? (array)$name : $this->request->param('ids/a');

        foreach ($names as $name) {
            $result = PacketModel::install($name);
            if ($result === true) {
                if (!PacketModel::where('name', $name)->find()) {
                    $data = PacketModel::getInfoFromFile($name);
                    $data['status'] = 1;
                    $data['tables'] = json_encode($data['tables']);
                    PacketModel::create($data);
                }
            } else {
                $this->error('安装失败：'. $result);
            }
        }
        // 记录行为
        $packet_titles = PacketModel::where('name', 'in', $names)->column('title');
        action_log('packet_install', 'admin_packet', 0, UID, implode('、', $packet_titles));
        $this->success('安装成功');
    }

    /**
     * 卸载
     * @param string $name 数据包名
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function uninstall($name = '')
    {
        $names = $name != '' ? (array)$name : $this->request->param('ids/a');

        // 记录行为
        $packet_titles = PacketModel::where('name', 'in', $names)->column('title');
        action_log('packet_uninstall', 'admin_packet', 0, UID, implode('、', $packet_titles));

        foreach ($names as $name) {
            PacketModel::uninstall($name);
        }

        $this->success('卸载成功');
    }
}