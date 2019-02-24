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
use app\cms\model\Advert as AdvertModel;
use app\cms\model\AdvertType as AdvertTypeModel;
use think\facade\Validate;

/**
 * 广告控制器
 * @package app\cms\admin
 */
class Advert extends Admin
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
        $data_list = AdvertModel::where($map)->order($order)->paginate();

        $btnType = [
            'class' => 'btn btn-info',
            'title' => '广告分类',
            'icon'  => 'fa fa-fw fa-sitemap',
            'href'  => url('advert_type/index')
        ];

        $list_type = AdvertTypeModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '广告名称', 'text.edit'],
                ['typeid', '分类', 'select', $list_type],
                ['ad_type', '类型', 'text', '', ['代码', '文字', '图片', 'flash']],
                ['timeset', '时间限制', 'text', '', ['永不过期', '限时']],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addTopButton('custom', $btnType) // 添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,name,typeid,timeset,ad_type,create_time,update_time')
            ->setRowList($data_list) // 设置表格数据
            ->addValidate('Advert', 'name')
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
            $result = $this->validate($data, 'Advert');
            if (true !== $result) $this->error($result);
            if ($data['ad_type'] != 0) {
                $data['link'] == '' && $this->error('链接不能为空');
                Validate::is($data['link'], 'url') === false && $this->error('链接不是有效的url地址'); // true
            }

            // 广告类型
            switch ($data['ad_type']) {
                case 0: // 代码
                    $data['content'] = $data['code'];
                    break;
                case 1: // 文字
                    $data['content'] = '<a href="'.$data['link'].'" target="_blank" style="';
                    if ($data['size'] != '') {
                        $data['content'] .= 'font-size:'.$data['size'].'px;';
                    }
                    if ($data['color'] != '') {
                        $data['content'] .= 'color:'.$data['color'];
                    }
                    $data['content'] .= '">'.$data['title'].'</a>';
                    break;
                case 2: // 图片
                    $data['content'] = '<a href="'.$data['link'].'" target="_blank"><img src="'.get_file_path($data['src']).'" style="';
                    if ($data['width'] != '') {
                        $data['content'] .= 'width:'.$data['width'].'px;';
                    }
                    if ($data['height'] != '') {
                        $data['content'] .= 'height:'.$data['height'].'px;';
                    }
                    if ($data['alt'] != '') {
                        $data['content'] .= '" alt="'.$data['alt'];
                    }
                    $data['content'] .= '" /></a>';
                    break;
                case 3: // flash
                    $data['content'] = '';
                    $data['content'] = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"';
                    if ($data['width'] != '') {
                        $data['content'] .= ' width="'.$data['width'].'"';
                    }
                    if ($data['height'] != '') {
                        $data['content'] .= ' height="'.$data['height'].'"';
                    }
                    $data['content'] .= '><param name="quality" value="high" /><param name="movie" value="'.$data['link'].'" /><embed allowfullscreen="true"';
                    if ($data['height'] != '') {
                        $data['content'] .= ' height="'.$data['height'].'"';
                    }
                    $data['content'] .= ' pluginspage="http://www.macromedia.com/go/getflashplayer" quality="high" src="'.$data['link'].'" type="application/x-shockwave-flash"';
                    if ($data['width'] != '') {
                        $data['content'] .= ' width="'.$data['width'].'"';
                    }
                    $data['content'] .= '></embed></object>';
                    break;
            }

            if ($advert = AdvertModel::create($data)) {
                // 记录行为
                action_log('advert_add', 'cms_advert', $advert['id'], UID, $data['name']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $list_type = AdvertTypeModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法添加的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['select', 'typeid', '广告分类', '', $list_type, 0],
                ['text', 'tagname', '广告位标识', '由小写字母、数字或下划线组成，不能以数字开头'],
                ['text', 'name', '广告位名称'],
                ['radio', 'timeset', '时间限制', '', ['永不过期', '在设内时间内有效'], 0],
                ['daterange', 'start_time,end_time', '开始时间-结束时间'],
                ['radio', 'ad_type', '广告类型', '', ['代码', '文字', '图片', 'flash'], 0],
                ['textarea', 'code', '代码', '<code>必填</code>，支持html代码'],
                ['image', 'src', '图片', '<code>必须</code>'],
                ['text', 'title', '文字内容', '<code>必填</code>'],
                ['text', 'link', '链接', '<code>必填</code>'],
                ['colorpicker', 'color', '文字颜色', '', '', 'rgb'],
                ['text', 'size', '文字大小', '只需填写数字，例如:12，表示12px', '',  ['', 'px']],
                ['text', 'width', '宽度', '不用填写单位，只需填写具体数字'],
                ['text', 'height', '高度', '不用填写单位，只需填写具体数字'],
                ['text', 'alt', '图片描述', '即图片alt的值'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->setTrigger('ad_type', '0', 'code')
            ->setTrigger('ad_type', '1', 'title,color,size')
            ->setTrigger('ad_type', '2', 'src,alt')
            ->setTrigger('ad_type', '2,3', 'width,height')
            ->setTrigger('ad_type', '1,2,3', 'link')
            ->setTrigger('timeset', '1', 'start_time')
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 广告id
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
            $result = $this->validate($data, 'Advert');
            if (true !== $result) $this->error($result);

            if (AdvertModel::update($data)) {
                // 记录行为
                action_log('advert_edit', 'cms_advert', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $list_type = AdvertTypeModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        $info = AdvertModel::get($id);
        $info['ad_type'] = ['代码', '文字', '图片', 'flash'][$info['ad_type']];

        // 显示编辑页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法添加的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'tagname'],
                ['static', 'tagname', '广告位标识'],
                ['static', 'ad_type', '广告类型'],
                ['text', 'name', '广告位名称'],
                ['select', 'typeid', '广告分类', '', $list_type],
                ['radio', 'timeset', '时间限制', '', ['永不过期', '在设内时间内有效']],
                ['daterange', 'start_time,end_time', '开始时间-结束时间'],
                ['textarea', 'content', '广告内容'],
                ['radio', 'status', '立即启用', '', ['否', '是']]
            ])
            ->setTrigger('timeset', '1', 'start_time')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除广告
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
     * 启用广告
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
     * 禁用广告
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
     * 设置广告状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids         = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $advert_name = AdvertModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['advert_'.$type, 'cms_advert', 0, UID, implode('、', $advert_name)]);
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
        $advert  = AdvertModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $advert . ')，新值：(' . $value . ')';
        return parent::quickEdit(['advert_edit', 'cms_advert', $id, UID, $details]);
    }
}
