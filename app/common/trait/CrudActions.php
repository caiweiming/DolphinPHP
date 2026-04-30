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
declare(strict_types=1);

namespace app\common\trait;

use think\db\Query;
use think\response\Json;
use think\facade\Db;
use think\facade\Config;
use think\Validate;
use think\exception\ValidateException;

/**
 * CRUD通用操作 Trait
 * 提供常用的启用、禁用、排序、删除等快捷操作
 * 支持模型和原生DB两种方式
 * @package app\common\trait
 */
trait CrudActions
{
    /**
     * 启用记录
     * @return Json
     */
    public function enable(): Json
    {
        $this->validateCrudToken('enable');
        $ids = $this->getRequestIds();

        // 检查是否有启用前的钩子
        if (method_exists($this, 'beforeEnable')) {
            $result = $this->beforeEnable($ids);
            if ($result === false) {
                $this->error('启用失败');
            }
        }

        // 执行启用操作
        $count = $this->setStatus(1, $ids);

        // 启用后的钩子
        if (method_exists($this, 'afterEnable')) {
            $this->afterEnable($ids, $count);
        }

        if (false !== $count) {
            $this->success('启用成功');
        } else {
            $this->error('启用失败');
        }
    }

    /**
     * 禁用记录
     * @return Json
     */
    public function disable(): Json
    {
        $this->validateCrudToken('disable');
        $ids = $this->getRequestIds();

        // 检查是否有禁用前的钩子
        if (method_exists($this, 'beforeDisable')) {
            $result = $this->beforeDisable($ids);
            if ($result === false) {
                $this->error('禁用失败');
            }
        }

        // 执行禁用操作
        $count = $this->setStatus(0, $ids);

        // 禁用后的钩子
        if (method_exists($this, 'afterDisable')) {
            $this->afterDisable($ids, $count);
        }

        if (false !== $count) {
            $this->success('禁用成功');
        } else {
            $this->error('禁用失败');
        }
    }

    /**
     * 设置状态
     * @param int $status 状态值
     * @param array $ids ID数组
     * @return int|false 受影响的记录数，失败返回false
     */
    protected function setStatus(int $status, array $ids): int|false
    {
        // 获取数据库操作对象（模型或DB）
        $db          = $this->getCrudDb();
        $statusField = $this->getCrudStatusField();

        // 执行更新（返回影响的行数或false）
        $count = $db->whereIn('id', $ids)->update([$statusField => $status]);

        // 批量清除缓存
        if (false !== $count) {
            $this->clearCacheForIds($ids);
        }

        return $count;
    }

    /**
     * 修改排序
     * @return Json
     */
    public function sort(): Json
    {
        $this->validateCrudToken('sort');
        $id   = $this->request->param('id/d', 0);
        $sort = $this->request->param('sort/d', 0);

        if (!$id) {
            $this->error('参数错误');
        }

        $db        = $this->getCrudDb();
        $sortField = $this->getCrudSortField();

        $result = $db->where('id', $id)->update([$sortField => $sort]);

        if (false !== $result) {
            // 清除缓存
            if (method_exists($this, 'clearCache')) {
                $this->clearCache($id);
            }
            $this->success('排序更新成功');
        } else {
            $this->error('排序更新失败');
        }
    }

    /**
     * 快速编辑
     * @return Json
     */
    public function quickEdit(): Json
    {
        $this->validateCrudToken('quickEdit');
        $id    = $this->request->param('id/d', 0);
        $field = $this->request->param('field', '');
        $value = $this->request->param('value', '');
        $type  = $this->request->param('type', '');

        if (!$id || !$field) {
            $this->error('参数错误');
        }

        // 检查字段是否允许快速编辑
        $allowFields      = $this->getCrudQuickEditFields();
        $requireWhitelist = Config::get('table.security.quick_edit_require_whitelist', true);
        if ($requireWhitelist && empty($allowFields)) {
            $this->error('该页面未配置可快速编辑字段');
        }

        if (!empty($allowFields) && !in_array($field, $allowFields, true)) {
            $this->error('该字段不允许快速编辑');
        }

        // quickEdit 验证（由 Table::validate() 配置）
        $this->validateQuickEditValue($id, $field, $value, $type);

        // 如果存在特殊类型，进行特殊处理
        if ($type == 'datetime') {
            $value = strtotime($value);
        }

        $db = $this->getCrudDb();

        $oldValue = $db->where('id', $id)->value($field);

        // 快速编辑前的钩子
        if (method_exists($this, 'beforeQuickEdit')) {
            $result = $this->beforeQuickEdit($id, $field, $value, $oldValue);
            if ($result === false) {
                $this->error('编辑失败');
            }
        }

        $result = $db->where('id', $id)->update([$field => $value]);

        // 快速编辑后的钩子
        if (method_exists($this, 'afterQuickEdit')) {
            $this->afterQuickEdit($id, $field, $value, $oldValue, $result);
        }

        if (false !== $result) {
            // 清除缓存
            if (method_exists($this, 'clearCache')) {
                $this->clearCache($id);
            }
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 删除记录
     * @return Json
     */
    public function delete(): Json
    {
        $this->validateCrudToken('delete');
        $ids = $this->getRequestIds();

        $db = $this->getCrudDb();

        // 检查是否有删除前的钩子
        if (method_exists($this, 'beforeDelete')) {
            $result = $this->beforeDelete($ids);
            if ($result === false) {
                $this->error('删除失败');
            }
        }

        // 判断是否为模型实例,如果是则使用destroy方法触发模型事件
        if (is_object($db) && method_exists($db, 'destroy')) {
            // 模型实例:使用destroy批量删除并触发模型事件
            $count = $db::destroy($ids);
        } else {
            // DB查询构造器:批量删除
            $count = $db->whereIn('id', $ids)->delete();
        }

        // 删除后的钩子
        if (method_exists($this, 'afterDelete')) {
            $this->afterDelete($ids, $count);
        }

        // 返回结果（false !== $count 表示操作成功执行）
        if (false !== $count) {
            // 批量清除缓存
            $this->clearCacheForIds($ids);
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 获取请求中的ID参数
     * @return array
     */
    protected function getRequestIds(): array
    {
        // 优先检查批量参数
        $ids = $this->parseIdsParam($this->request->param('ids', []));

        // 如果 ids 为空，检查单个参数 id
        if (empty($ids)) {
            $id = $this->request->param('id/d', 0);
            if (!$id) {
                $this->error('参数错误');
            }
            $ids = [$id];
        }

        // 过滤无效值并转为整数
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            $this->error('参数错误');
        }

        return $ids;
    }

    /**
     * 解析 ids 参数，兼容数组、逗号字符串、JSON 数组字符串
     * @param mixed $idsParam
     * @return array
     */
    protected function parseIdsParam(mixed $idsParam): array
    {
        if (is_array($idsParam)) {
            return $idsParam;
        }

        if (!is_string($idsParam) || trim($idsParam) === '') {
            return [];
        }

        $raw = trim($idsParam);

        // JSON 数组字符串: [1,2,3]
        if (str_starts_with($raw, '[') && str_ends_with($raw, ']')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // 逗号分隔字符串: 1,2,3
        if (str_contains($raw, ',')) {
            return array_map('trim', explode(',', $raw));
        }

        // 单值字符串
        return [$raw];
    }

    /**
     * 批量清除缓存
     * @param array $ids
     * @return void
     */
    protected function clearCacheForIds(array $ids): void
    {
        if (method_exists($this, 'clearCache')) {
            foreach ($ids as $id) {
                $this->clearCache($id);
            }
        }
    }

    /**
     * 获取CRUD操作的数据库对象
     * 优先级：
     * 1. URL token参数（解密后的表名）
     * 2. 子类自定义 resolveCrudDb() 方法
     * 3. 子类模型 $this->model
     * 4. 自动推断：模块_控制器
     *
     * @return mixed
     */
    protected function getCrudDb(): mixed
    {
        // 1. 从URL token获取表配置
        $token = $this->request->param('_t', '');
        if ($token) {
            $tableConfig = dp_crud_token_decode($token);
            if ($tableConfig) {
                return $this->createDbInstance($tableConfig);
            }
        }

        // 2. 子类自定义解析方法
        if (method_exists($this, 'resolveCrudDb')) {
            return $this->resolveCrudDb();
        }

        // 3. 子类模型属性（用于触发模型事件）
        if (isset($this->model)) {
            return $this->model;
        }

        // 4. 自动推断表名：模块_控制器
        $tableName = $this->getTableName();
        return Db::name($tableName);
    }

    /**
     * 自动推断表名
     * 规则：admin_user (模块名_控制器名)
     * @return string
     */
    protected function getTableName(): string
    {
        // 自定义表名优先
        if (isset($this->tableName)) {
            return $this->tableName;
        }

        $app        = app('http')->getName();
        $controller = $this->request->controller();
        return dp_resolve_crud_table_name((string)$controller, (string)$app);
    }

    /**
     * 创建数据库实例
     * @param array $config
     * @return Query|Db
     */
    protected function createDbInstance(array $config): Db|Query
    {
        $type = $config['type'] ?? 'name';

        if ($type === 'table') {
            return Db::table($config['table']);
        } else {
            return Db::name($config['name']);
        }
    }

    /**
     * 获取CRUD操作的状态字段名
     * @return string
     */
    protected function getCrudStatusField(): string
    {
        return $this->statusField ?? 'status';
    }

    /**
     * 获取CRUD操作的排序字段名
     * @return string
     */
    protected function getCrudSortField(): string
    {
        return $this->sortField ?? 'sort';
    }

    /**
     * 获取CRUD操作允许快速编辑的字段列表
     * @return array
     */
    protected function getCrudQuickEditFields(): array
    {
        return $this->quickEditFields ?? [];
    }

    /**
     * quickEdit 字段验证
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @param string $type
     * @return void
     */
    protected function validateQuickEditValue(int $id, string $field, mixed $value, string $type = ''): void
    {
        $config = $this->getQuickEditValidateConfigFromToken();
        if (empty($config['enabled'])) {
            return;
        }

        $fields = $config['fields'] ?? [];
        if (!empty($fields) && !in_array($field, $fields, true)) {
            return;
        }

        $validator = trim((string)($config['validator'] ?? ''));
        if ($validator === '') {
            // 默认约定：控制器同名验证器
            $validator = $this->request->controller();
        }

        $data = [
            'id'    => $id,
            'field' => $field,
            'type'  => $type,
            'value' => $value,
            $field  => $value,
        ];

        try {
            $this->validateByField($validator, $field, $data);
        } catch (ValidateException $e) {
            $this->error($e->getError());
        }
    }

    /**
     * 使用 ThinkPHP 验证器按字段校验
     * @param string $validate 验证器标识
     * @param string $field 字段名
     * @param array $data 数据
     * @return void
     * @throws ValidateException
     */
    protected function validateByField(string $validate, string $field, array $data): void
    {
        if (str_contains($validate, '.')) {
            $this->error('quickEdit 校验不支持验证场景，请仅传入验证器名');
        }

        $class = str_contains($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
        if (!class_exists($class)) {
            $this->error('验证器不存在：' . $validate);
        }

        $v = new $class();
        if (!$v instanceof Validate) {
            $this->error('无效的验证器：' . $validate);
        }

        $rules = $v->getRules();
        if (!is_array($rules) || $rules === []) {
            return;
        }

        $ruleKey = $this->matchValidateRuleKey($rules, $field);
        if ($ruleKey === '') {
            // 当前字段没有验证规则，跳过
            return;
        }

        $single = new Validate();
        $single->rule([$ruleKey => $rules[$ruleKey]]);

        $messages = $v->getMessage();
        if (is_array($messages) && $messages !== []) {
            $fieldMessages = [];
            foreach ($messages as $messageKey => $messageText) {
                if (str_starts_with((string)$messageKey, $field . '.')) {
                    $fieldMessages[$messageKey] = $messageText;
                }
            }
            if ($fieldMessages !== []) {
                $single->message($fieldMessages);
            }
        }

        $single->failException()->check([
            $field => $data[$field] ?? null,
        ]);
    }

    /**
     * 匹配字段对应的验证规则键名（兼容 field|label）
     * @param array $rules
     * @param string $field
     * @return string
     */
    protected function matchValidateRuleKey(array $rules, string $field): string
    {
        foreach ($rules as $ruleKey => $rule) {
            $name = trim((string)$ruleKey);
            if ($name === '') {
                continue;
            }

            $fieldName = trim(strstr($name, '|', true) ?: $name);
            if ($fieldName === $field) {
                return $name;
            }
        }

        return '';
    }

    /**
     * 从 token 读取 quickEdit 验证配置
     * @return array{enabled:bool,validator:string,fields:array}
     */
    protected function getQuickEditValidateConfigFromToken(): array
    {
        $default = [
            'enabled'   => false,
            'validator' => '',
            'fields'    => [],
        ];

        $token = (string)$this->request->param('_t', '');
        if ($token === '') {
            return $default;
        }

        $tableConfig = dp_crud_token_decode($token);
        if (!$tableConfig || !is_array($tableConfig)) {
            return $default;
        }

        $config = $tableConfig['meta']['quick_edit_validate'] ?? null;
        if (!is_array($config)) {
            return $default;
        }

        $fields = [];
        if (is_array($config['fields'] ?? null)) {
            foreach ($config['fields'] as $item) {
                $item = trim((string)$item);
                if ($item !== '') {
                    $fields[] = $item;
                }
            }
            $fields = array_values(array_unique($fields));
        }

        return [
            'enabled'   => (bool)($config['enabled'] ?? false),
            'validator' => trim((string)($config['validator'] ?? '')),
            'fields'    => $fields,
        ];
    }

    /**
     * 校验 CRUD token
     * @param string $action 当前操作名
     * @return void
     */
    protected function validateCrudToken(string $action): void
    {
        $requireToken = Config::get('table.security.require_token', true);
        $tokenActions = Config::get('table.security.token_actions', ['quickEdit', 'enable', 'disable', 'delete', 'sort']);

        if (!$requireToken || !in_array($action, $tokenActions, true)) {
            return;
        }

        $token = (string)$this->request->param('_t', '');
        if ($token === '') {
            $this->error('缺少安全令牌');
        }

        $config = dp_crud_token_decode($token);
        if (!$config) {
            $this->error('安全令牌无效或已过期');
        }
    }
}
