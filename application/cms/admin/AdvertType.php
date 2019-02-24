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
use app\cms\model\AdvertType as AdvertTypeModel;

/**
 * 广告分类控制器
 * @package app\cms\admin
 */
class AdvertType extends Admin
{
    /**
     * 广告列表
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
        $data_list = AdvertTypeModel::where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '分类名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '分类名称', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('cms_advert_type')
            ->addTopButton('back', ['href' => url('advert/index')]) // 批量添加顶部按钮
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,name,create_time,update_time')
            ->setRowList($data_list) // 设置表格数据
            ->addValidate('AdvertType', 'name')
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
            $result = $this->validate($data, 'AdvertType');
            if(true !== $result) $this->error($result);

            if ($type = AdvertTypeModel::create($data)) {
                // 记录行为
                action_log('advert_type_add', 'cms_advert_type', $type['id'], UID, $data['name']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法添加的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['text', 'name', '分类名称'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id
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
            $result = $this->validate($data, 'AdvertType');
            if(true !== $result) $this->error($result);

            if (AdvertTypeModel::update($data)) {
                // 记录行为
                action_log('advert_type_edit', 'cms_advert_type', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = AdvertTypeModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法编辑的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '分类名称'],
                ['radio', 'status', '立即启用', '', ['否', '是']]
            ])
            ->setFormdata($info)
            ->fetch();
    }

    /**
     * 删除广告分类
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
     * 启用广告分类
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
     * 禁用广告分类
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
     * 设置广告分类状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record 日志记录
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids       = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $type_name = AdvertTypeModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['advert_type_'.$type, 'cms_advert_type', 0, UID, implode('、', $type_name)]);
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
        $type    = AdvertTypeModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $type . ')，新值：(' . $value . ')';
        return parent::quickEdit(['advert_type_edit', 'cms_advert_type', $id, UID, $details]);
    }
}
