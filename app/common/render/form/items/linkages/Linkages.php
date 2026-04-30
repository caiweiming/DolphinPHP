<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\render\form\items\linkages;

use app\common\abstract\FormType;
use app\common\render\form\traits\Multiple;

/**
 * 快速联动组件
 */
class Linkages extends FormType
{
    use Multiple;

    /**
     * 设置表单项类型
     * @var string
     */
    protected string $type = 'linkages';

    /**
     * 数据表
     * @param string $table
     * @return $this
     */
    public function table(string $table): static
    {
        $this->config['table'] = $table;
        return $this;
    }

    /**
     * 是否包含表前缀（true 时使用 Db::table）
     * @param bool $withPrefix
     * @return $this
     */
    public function prefix(bool $withPrefix = true): static
    {
        $this->config['prefix'] = $withPrefix;
        return $this;
    }

    /**
     * 级别配置
     * @param int|array $levels
     * @return $this
     */
    public function levels(int|array $levels): static
    {
        $this->config['levels'] = $levels;
        return $this;
    }

    /**
     * 数据库连接标识
     * @param string $connection
     * @return $this
     */
    public function connection(string $connection): static
    {
        $this->config['connection'] = $connection;
        return $this;
    }

    /**
     * 字段映射
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields = []): static
    {
        $this->config['fields'] = $fields;
        return $this;
    }

    /**
     * 根级 pid
     * @param int|string $rootPid
     * @return $this
     */
    public function rootPid(int|string $rootPid = 0): static
    {
        $this->config['root_pid'] = $rootPid;
        return $this;
    }

    /**
     * 分级筛选
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters = []): static
    {
        $this->config['filters'] = $filters;
        return $this;
    }

    /**
     * 提交所有级别值
     * @param bool $submitAll
     * @return $this
     */
    public function submitAll(bool $submitAll = true): static
    {
        $this->config['submit_all'] = $submitAll;
        return $this;
    }

    /**
     * 指定查询接口
     * @param string $url
     * @return $this
     */
    public function url(string $url): static
    {
        $this->config['url'] = $url;
        return $this;
    }

    /**
     * 指定查询接口（兼容旧写法）
     * @param string $url
     * @return $this
     */
    public function apiUrl(string $url): static
    {
        $this->config['url'] = $url;
        return $this;
    }
}
