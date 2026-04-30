<?php

namespace app\common\annotation;

use Attribute;

/**
 * 操作日志注解
 * 用于在控制器方法上声明日志记录需求
 */
#[Attribute(Attribute::TARGET_METHOD)]
class OpLog
{
    /**
     * 构造函数
     *
     * @param string $title 日志标题，为空时自动生成
     * @param string $level 日志级别：debug, info, warning, error
     * @param bool $params 是否记录请求参数
     * @param bool $result 是否记录响应结果
     * @param array $fields 指定记录的字段列表
     * @param bool $auto 是否自动处理（false时需要手动调用）
     * @param array $exclude 排除的字段列表
     * @param int|null $sample_rate 采样率(1-100)，null时使用全局配置
     * @param bool $async 是否异步处理
     * @param array $context 额外的上下文数据
     */
    public function __construct(
        public string $title = '',
        public string $level = 'info',
        public bool   $params = false,
        public bool   $result = false,
        public array  $fields = [],
        public bool   $auto = true,
        public array  $exclude = [],
        public ?int   $sample_rate = null,
        public bool   $async = false,
        public array  $context = []
    )
    {
        // 验证日志级别
        $validLevels = ['debug', 'info', 'warning', 'error'];
        if (!in_array($level, $validLevels)) {
            $this->level = 'info';
        }

        // 验证采样率
        if ($sample_rate !== null && ($sample_rate < 1 || $sample_rate > 100)) {
            $this->sample_rate = null;
        }

        // 过滤敏感字段
        $sensitiveFields = config('logging.filter.sensitive_fields', []);
        if (!empty($fields)) {
            $this->fields = array_diff($fields, $sensitiveFields);
        }
    }

    /**
     * 获取日志标题
     *
     * @param string $controller 控制器名
     * @param string $action 方法名
     * @return string
     */
    public function getTitle(string $controller = '', string $action = ''): string
    {
        if (!empty($this->title)) {
            return $this->title;
        }

        // 自动生成标题
        $controllerName = $this->formatControllerName($controller);
        $actionName     = $this->formatActionName($action);

        return $controllerName . $actionName;
    }

    /**
     * 格式化控制器名称
     *
     * @param string $controller
     * @return string
     */
    private function formatControllerName(string $controller): string
    {
        // 移除Controller后缀和命名空间
        $name = basename(str_replace('Controller', '', $controller));

        // 转换为中文描述（可以根据实际需求扩展）
        $mapping = [
            'User'       => '用户',
            'Admin'      => '管理员',
            'File'       => '文件',
            'Menu'       => '菜单',
            'Role'       => '角色',
            'Permission' => '权限',
            'Log'        => '日志',
            'System'     => '系统',
            'Config'     => '配置',
            'Auth'       => '认证',
            'Login'      => '登录',
            'Logout'     => '登出',
        ];

        return $mapping[$name] ?? $name;
    }

    /**
     * 格式化方法名称
     *
     * @param string $action
     * @return string
     */
    private function formatActionName(string $action): string
    {
        // 转换为中文描述
        $mapping = [
            'index'    => '列表',
            'create'   => '创建',
            'store'    => '保存',
            'show'     => '查看',
            'edit'     => '编辑',
            'update'   => '更新',
            'delete'   => '删除',
            'destroy'  => '删除',
            'login'    => '登录',
            'logout'   => '登出',
            'upload'   => '上传',
            'download' => '下载',
            'export'   => '导出',
            'import'   => '导入',
            'enable'   => '启用',
            'disable'  => '禁用',
            'search'   => '搜索',
            'filter'   => '筛选',
            'sort'     => '排序',
        ];

        return $mapping[$action] ?? $action;
    }

    /**
     * 检查是否应该记录此日志
     *
     * @return bool
     */
    public function shouldLog(): bool
    {
        // 检查全局开关
        if (!config('logging.enable', true)) {
            return false;
        }

        // 检查日志级别过滤
        $levelFilter  = config('logging.level_filter', 'info');
        $levels       = config('logging.levels', ['debug', 'info', 'warning', 'error']);
        $currentIndex = array_search($this->level, $levels);
        $filterIndex  = array_search($levelFilter, $levels);

        if ($currentIndex === false || $filterIndex === false || $currentIndex < $filterIndex) {
            return false;
        }

        // 检查采样率
        $sampleRate = $this->sample_rate ?? config('logging.performance.sample_rate', 100);
        if ($sampleRate < 100 && mt_rand(1, 100) > $sampleRate) {
            return false;
        }

        return true;
    }

    /**
     * 获取配置的字段列表
     *
     * @param array $requestParams 请求参数
     * @return array
     */
    public function getFieldData(array $requestParams = []): array
    {
        if (empty($this->fields)) {
            return [];
        }

        $result = [];
        foreach ($this->fields as $field) {
            if (isset($requestParams[$field])) {
                $result[$field] = $requestParams[$field];
            }
        }

        return $result;
    }

    /**
     * 过滤排除的字段
     *
     * @param array $data
     * @return array
     */
    public function filterExcludedFields(array $data): array
    {
        if (empty($this->exclude)) {
            return $data;
        }

        foreach ($this->exclude as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}