<?php
declare(strict_types=1);

namespace app\cms\controller\admin;

use app\common\attribute\Permission;
use app\cms\service\CmsViewCacheService;
use Throwable;
use think\facade\Db;
use think\Paginator;

/**
 * CMS 生命周期演示后台页
 */
#[Permission('CMS生命周期演示', icon: 'ti ti-clock-play')]
class Demo extends Auth
{
    /**
     * 演示记录列表
     * @return string
     */
    #[Permission('查看')]
    public function index(): string
    {
        $this->table
            ->alert(
                '本页用于展示 install.sql 和 upgrade.sql 写入的 CMS 生命周期演示记录。',
                '生命周期演示说明',
                'info:icon,close'
            )
            ->checkbox(false)
            ->search([])
            ->columns([
                ['id', 'ID', '', [], ['width' => 80]],
                ['event_code', '事件编码', '', [], ['width' => 140]],
                ['event_title', '事件标题', '', [], ['minWidth' => 160]],
                ['details', '说明', '', [], ['minWidth' => 320]],
                ['create_time_text', '创建时间', '', [], ['width' => 180]],
            ])
            ->render();

        $this->page->row($this->table);
        return $this->fetch();
    }

    /**
     * 演示记录数据
     * @return Paginator
     */
    protected function data(): Paginator
    {
        try {
            return Db::table('dp_cms_demo_lifecycle')
                ->order('id', 'desc')
                ->paginate(dp_get_list_rows())
                ->each(function (array $row) {
                    $row['create_time_text'] = !empty($row['create_time'])
                        ? date('Y-m-d H:i:s', (int)$row['create_time'])
                        : '-';

                    return $row;
                });
        } catch (Throwable) {
            $this->table->alert('演示表不存在或尚未初始化', '生命周期演示状态', 'warning:icon,close');
            return Db::table('dp_admin_app')->where('id', 0)->paginate(dp_get_list_rows());
        }
    }
}
