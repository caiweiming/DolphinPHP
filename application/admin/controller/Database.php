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
use think\Db;
use util\Database as DatabaseModel;

/**
 * 数据库管理
 * @package app\admin\controller
 */
class Database extends Admin
{
    /**
     * 数据库管理
     * @param string $group 分组
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function index($group = 'export')
    {
        // 配置分组信息
        $list_group = ['export' =>'备份数据库', 'import' => '还原数据库'];
        $tab_list = [];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['url']   = url('index', ['group' => $key]);
        }

        switch ($group) {
            case 'export':
                $data_list = Db::query("SHOW TABLE STATUS");
                $data_list = array_map('array_change_key_case', $data_list);

                // 自定义按钮
                $btn_export = [
                    'title' => '立即备份',
                    'icon'  => 'fa fa-fw fa-copy',
                    'class' => 'btn btn-primary ajax-post confirm',
                    'href'  => url('export')
                ];
                $btn_optimize_all = [
                    'title' => '优化表',
                    'icon'  => 'fa fa-fw fa-cogs',
                    'class' => 'btn btn-success ajax-post',
                    'href'  => url('optimize')
                ];
                $btn_repair_all = [
                    'title' => '修复表',
                    'icon'  => 'fa fa-fw fa-wrench',
                    'class' => 'btn btn-success ajax-post',
                    'href'  => url('repair')
                ];
                $btn_optimize = [
                    'title' => '优化表',
                    'icon'  => 'fa fa-fw fa-cogs',
                    'class' => 'btn btn-xs btn-default ajax-get',
                    'href'  => url('optimize', ['ids' => '__id__'])
                ];
                $btn_repair = [
                    'title' => '修复表',
                    'icon'  => 'fa fa-fw fa-wrench',
                    'class' => 'btn btn-xs btn-default ajax-get',
                    'href'  => url('repair', ['ids' => '__id__'])
                ];

                // 使用ZBuilder快速创建数据表格
                return ZBuilder::make('table')
                    ->setPageTitle('数据库管理') // 设置页面标题
                    ->setPrimaryKey('name')
                    ->setTabNav($tab_list, $group) // 设置tab分页
                    ->addColumns([ // 批量添加数据列
                        ['name', '表名'],
                        ['rows', '行数'],
                        ['data_length', '大小', 'byte'],
                        ['data_free', '冗余', 'byte'],
                        ['comment', '备注'],
                        ['right_button', '操作', 'btn']
                    ])
                    ->addTopButton('custom', $btn_export) // 添加单个顶部按钮
                    ->addTopButton('custom', $btn_optimize_all) // 添加单个顶部按钮
                    ->addTopButton('custom', $btn_repair_all) // 添加单个顶部按钮
                    ->addRightButton('custom', $btn_optimize) // 添加右侧按钮
                    ->addRightButton('custom', $btn_repair) // 添加右侧按钮
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch(); // 渲染模板
                break;
            case 'import':
                // 列出备份文件列表
                $path = config('data_backup_path');
                if(!is_dir($path)){
                    mkdir($path, 0755, true);
                }
                $path = realpath($path);
                $flag = \FilesystemIterator::KEY_AS_FILENAME;
                $glob = new \FilesystemIterator($path, $flag);

                $data_list = [];
                foreach ($glob as $name => $file) {
                    if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)){
                        $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

                        $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                        $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                        $part = $name[6];

                        if(isset($data_list["{$date} {$time}"])){
                            $info = $data_list["{$date} {$time}"];
                            $info['part'] = max($info['part'], $part);
                            $info['size'] = $info['size'] + $file->getSize();
                        } else {
                            $info['part'] = $part;
                            $info['size'] = $file->getSize();
                        }
                        $extension        = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                        $info['compress'] = ($extension === 'SQL') ? '-' : $extension;
                        $info['time']     = strtotime("{$date} {$time}");
                        $info['name']     = $info['time'];

                        $data_list["{$date} {$time}"] = $info;
                    }
                }

                $data_list = !empty($data_list) ? array_values($data_list) : $data_list;

                // 自定义按钮
                $btn_import = [
                    'title' => '还原',
                    'icon'  => 'fa fa-fw fa-reply',
                    'class' => 'btn btn-xs btn-default ajax-get confirm',
                    'href'  => url('import', ['time' => '__id__'])
                ];

                // 使用ZBuilder快速创建数据表格
                return ZBuilder::make('table')
                    ->setPageTitle('数据库管理') // 设置页面标题
                    ->setPrimaryKey('time')
                    ->hideCheckbox()
                    ->setTabNav($tab_list, $group) // 设置tab分页
                    ->addColumns([ // 批量添加数据列
                        ['name', '备份名称', 'datetime', '', 'Ymd-His'],
                        ['part', '卷数'],
                        ['compress', '压缩'],
                        ['size', '数据大小', 'byte'],
                        ['time', '备份时间', 'datetime', '', 'Y-m-d H:i:s'],
                        ['right_button', '操作', 'btn']
                    ])
                    ->addRightButton('custom', $btn_import) // 添加右侧按钮
                    ->addRightButton('delete') // 添加右侧按钮
                    ->setRowList($data_list) // 设置表格数据
                    ->fetch(); // 渲染模板
                break;
        }
    }

    /**
     * 备份数据库(参考onthink 麦当苗儿 <zuojiazi@vip.qq.com>)
     * @param null|array $ids 表名
     * @param integer $start 起始行数
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function export($ids = null, $start = 0)
    {
        $tables = $ids;
        if ($this->request->isPost() && !empty($tables) && is_array($tables)) {
            // 初始化
            $path = config('data_backup_path');
            if(!is_dir($path)){
                mkdir($path, 0755, true);
            }

            // 读取备份配置
            $config = array(
                'path'     => realpath($path) . DIRECTORY_SEPARATOR,
                'part'     => config('data_backup_part_size'),
                'compress' => config('data_backup_compress'),
                'level'    => config('data_backup_compress_level'),
            );

            // 检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if(is_file($lock)){
                $this->error('检测到有一个备份任务正在执行，请稍后再试！');
            } else {
                // 创建锁文件
                file_put_contents($lock, $this->request->time());
            }

            // 检查备份目录是否可写
            is_writeable($config['path']) || $this->error('备份目录不存在或不可写，请检查后重试！');

            // 生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', $this->request->time()),
                'part' => 1,
            );

            // 创建备份文件
            $Database = new DatabaseModel($file, $config);
            if(false !== $Database->create()){
                // 备份指定表
                foreach ($tables as $table) {
                    $start = $Database->backup($table, $start);
                    while (0 !== $start) {
                        if (false === $start) { // 出错
                            $this->error('备份出错！');
                        }
                        $start = $Database->backup($table, $start[0]);
                    }
                }

                // 备份完成，删除锁定文件
                unlink($lock);
                // 记录行为
                action_log('database_export', 'database', 0, UID, implode(',', $tables));
                $this->success('备份完成！');
            } else {
                $this->error('初始化失败，备份文件创建失败！');
            }
        } else {
            $this->error('参数错误！');
        }
    }

    /**
     * 还原数据库(参考onthink 麦当苗儿 <zuojiazi@vip.qq.com>)
     * @param int $time 文件时间戳
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function import($time = 0)
    {
        if ($time === 0) $this->error('参数错误！');

        // 初始化
        $name  = date('Ymd-His', $time) . '-*.sql*';
        $path  = realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
        $files = glob($path);
        $list  = array();
        foreach($files as $name){
            $basename = basename($name);
            $match    = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
            $gz       = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
            $list[$match[6]] = array($match[6], $name, $gz);
        }
        ksort($list);

        // 检测文件正确性
        $last = end($list);
        if(count($list) === $last[0]){
            foreach ($list as $item) {
                $config = [
                    'path'     => realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR,
                    'compress' => $item[2]
                ];
                $Database = new DatabaseModel($item, $config);
                $start = $Database->import(0);

                // 循环导入数据
                while (0 !== $start) {
                    if (false === $start) { // 出错
                        $this->error('还原数据出错！');
                    }
                    $start = $Database->import($start[0]);
                }
            }
            // 记录行为
            action_log('database_import', 'database', 0, UID, date('Ymd-His', $time));
            $this->success('还原完成！');
        } else {
            $this->error('备份文件可能已经损坏，请检查！');
        }
    }

    /**
     * 优化表
     * @param null|string|array $ids 表名
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function optimize($ids = null)
    {
        $tables = $ids;
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list   = Db::query("OPTIMIZE TABLE `{$tables}`");

                if($list){
                    // 记录行为
                    action_log('database_optimize', 'database', 0, UID, "`{$tables}`");
                    $this->success("数据表优化完成！");
                } else {
                    $this->error("数据表优化出错请重试！");
                }
            } else {
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");
                if($list){
                    // 记录行为
                    action_log('database_optimize', 'database', 0, UID, $tables);
                    $this->success("数据表'{$tables}'优化完成！");
                } else {
                    $this->error("数据表'{$tables}'优化出错请重试！");
                }
            }
        } else {
            $this->error("请选择要优化的表！");
        }
    }

    /**
     * 修复表
     * @param null|string|array $ids 表名
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function repair($ids = null)
    {
        $tables = $ids;
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list = Db::query("REPAIR TABLE `{$tables}`");

                if($list){
                    // 记录行为
                    action_log('database_repair', 'database', 0, UID, "`{$tables}`");
                    $this->success("数据表修复完成！");
                } else {
                    $this->error("数据表修复出错请重试！");
                }
            } else {
                $list = Db::query("REPAIR TABLE `{$tables}`");
                if($list){
                    // 记录行为
                    action_log('database_repair', 'database', 0, UID, $tables);
                    $this->success("数据表'{$tables}'修复完成！");
                } else {
                    $this->error("数据表'{$tables}'修复出错请重试！");
                }
            }
        } else {
            $this->error("请指定要修复的表！");
        }
    }

    /**
     * 删除备份文件
     * @param int $ids 备份时间
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function delete($ids = 0)
    {
        if ($ids == 0) $this->error('参数错误！');

        $name  = date('Ymd-His', $ids) . '-*.sql*';
        $path  = realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
        array_map("unlink", glob($path));
        if(count(glob($path))){
            $this->error('备份文件删除失败，请检查权限！');
        } else {
            // 记录行为
            action_log('database_backup_delete', 'database', 0, UID, date('Ymd-His', $ids));
            $this->success('备份文件删除成功！');
        }
    }
}