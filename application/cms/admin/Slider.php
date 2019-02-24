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
use app\cms\model\Slider as SliderModel;

/**
 * 滚动图片控制器
 * @package app\cms\admin
 */
class Slider extends Admin
{
    /**
     * 滚动图片列表
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
        $order = $this->getOrder();
        // 数据列表
        $data_list = SliderModel::where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['cover', '图片', 'picture'],
                ['title', '标题', 'text.edit'],
                ['url', '链接', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['sort', '排序', 'text.edit'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,title,create_time')
            ->setRowList($data_list) // 设置表格数据
            ->addValidate('Slider', 'title,url')
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
            $result = $this->validate($data, 'Slider');
            if(true !== $result) $this->error($result);

            if ($slider = SliderModel::create($data)) {
                // 记录行为
                action_log('slider_add', 'cms_slider', $slider['id'], UID, $data['title']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['image', 'cover', '图片'],
                ['text', 'url', '链接'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 滚动图片id
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
            $result = $this->validate($data, 'Slider');
            if(true !== $result) $this->error($result);

            if (SliderModel::update($data)) {
                // 记录行为
                action_log('slider_add', 'cms_slider', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = SliderModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '标题'],
                ['image', 'cover', '图片'],
                ['text', 'url', '链接'],
                ['text', 'sort', '排序'],
                ['radio', 'status', '立即启用', '', ['否', '是']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除单页
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
     * 启用单页
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
     * 禁用单页
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
     * 设置单页状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids          = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $slider_title = SliderModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['slider_'.$type, 'cms_slider', 0, UID, implode('、', $slider_title)]);
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
        $slider  = SliderModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $slider . ')，新值：(' . $value . ')';
        return parent::quickEdit(['slider_edit', 'cms_slider', $id, UID, $details]);
    }
}
