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

namespace app\admin\controller;

use app\admin\facade\FileService;
use app\common\attribute\Permission as PermissionAttribute;
use app\common\helper\Logger;
use Exception;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Filesystem;
use think\File;
use think\response\Json;
use think\Paginator;
use Throwable;

/**
 * Api请求控制器
 */
class Api extends Auth
{
    // 禁用表单令牌验证
    public mixed $csrf = false;

    /**
     * 快速联动 filters 允许的查询方法白名单
     */
    private const LINKAGES_FILTER_METHODS = [
        'where',
        'whereOr',
        'whereXor',
        'whereNull',
        'whereNotNull',
        'whereIn',
        'whereNotIn',
        'whereBetween',
        'whereNotBetween',
        'whereLike',
        'whereNotLike',
        'whereExists',
        'whereNotExists',
        'whereExp',
        'whereColumn',
        'whereRaw',
    ];

    /**
     * 获取SelectAjax数据
     * @param string $keyword 关键字
     * @param string $token token
     * @param string $type 操作类型：query-查询，query:append-查询分页数据，default-查询默认值
     * @param string $default 默认值
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSelectAjax(string $keyword = '', string $token = '', string $type = '', string $default = ''): Json
    {
        // 返回的数据格式
        $result = [
            'code' => 1,
            'data' => [],
            'msg'  => '获取成功'
        ];
        // 获取参数
        $params = session($token);
        if (!$params) {
            $result['code'] = -1;
            $result['msg']  = '数据获取失败, 请刷新页面重试';
            return json($result);
        }
        // 主键字段
        $key = $params['key'] ?? 'id';
        // 标题字段
        $title = $params['title'] ?? 'title';
        // 返回的字段格式
        $field = [
            $key   => 'id',
            $title => 'text'
        ];

        // 表对象
        if (isset($params['prefix']) && $params['prefix'] === true) {
            $table = Db::table($params['table']);
        } else {
            $table = Db::name($params['table']);
        }

        if ($type == 'default') {
            // 获取默认选项
            $data = $table->where($key, 'in', $default)
                ->field($field)
                ->select();

            $result['total'] = count($data);
        } else {
            // 要模糊查询的字段
            $search = $params['search'] ?? $key . '|' . $title;

            // 查询条件
            $where = [
                [$search, 'like', '%' . $keyword . '%']
            ];
            if (isset($params['where'])) {
                $where = array_merge($where, $params['where']);
            }
            $whereOr = [];
            if (isset($params['whereOr'])) {
                $whereOr = $params['whereOr'];
            }

            // 每页显示数量
            $listRows = $params['rows'] ?? 15;

            $data = $table->where($where)
                ->whereOr($whereOr)
                ->field($field)
                ->paginate($listRows);

            $result['total'] = $data->total();
        }

        if ($data->isEmpty()) {
            $result['code'] = 0;
            $result['msg']  = '无数据';
        } else {
            if ($data instanceof Paginator) {
                $data = $data->toArray()['data'];
            }

            if (isset($params['callback']) && is_callable($params['callback'])) {
                $data = $params['callback']($data);
            }

            $result['data'] = $data;
        }

        return json($result);
    }

    /**
     * 快速联动内置查询接口
     * @return Json
     * @throws Throwable
     */
    public function getLinkages(): Json
    {
        $token  = (string)$this->request->param('token', '');
        $level  = max(1, (int)$this->request->param('level', 1));
        $parent = $this->request->param('parent');

        if ($token === '') {
            $this->error('参数错误: token 不能为空');
        }

        $config = session($token);
        if (!is_array($config) || empty($config['table'])) {
            $this->error('联动配置不存在或已过期');
        }

        $table = (string)$config['table'];
        if (!preg_match('/^[A-Za-z0-9_.]+$/', $table)) {
            $this->error('非法表名');
        }

        $fields     = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $idField    = (string)($fields['id'] ?? 'id');
        $nameField  = (string)($fields['name'] ?? 'name');
        $pidField   = (string)($fields['pid'] ?? 'pid');
        $connection = (string)($config['connection'] ?? '');
        $withPrefix = (bool)($config['prefix'] ?? false);
        $rootPid    = $config['root_pid'] ?? 0;

        try {
            $query = $this->buildLinkagesQuery($table, $connection, $withPrefix);

            if ($level === 1) {
                $query->where($pidField, '=', $rootPid);
            } else {
                if ($parent === null || $parent === '') {
                    return json(['code' => 1, 'msg' => '获取成功', 'data' => []]);
                }
                $query->where($pidField, '=', $parent);
            }

            $this->applyLinkagesFilters($query, $config['filters'] ?? [], $level);

            $data = $query
                ->field([$idField => 'key', $nameField => 'value'])
                ->order($idField, 'asc')
                ->select()
                ->toArray();

            return json(['code' => 1, 'msg' => '获取成功', 'data' => $data]);
        } catch (Throwable $e) {
            dp_log('快速联动查询失败')
                ->context([
                    'table'           => $table,
                    'level'           => $level,
                    'exception_class' => get_class($e),
                    'exception_code'  => $e->getCode(),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                ])
                ->error();
            return json(['code' => 0, 'msg' => '快速联动数据查询失败，请查看日志信息', 'data' => []]);
        }
    }

    /**
     * 构建联动查询对象
     * @param string $table
     * @param string $connection
     * @param bool $withPrefix
     * @return BaseQuery
     */
    private function buildLinkagesQuery(string $table, string $connection = '', bool $withPrefix = false): BaseQuery
    {
        $db = $connection !== '' ? Db::connect($connection) : Db::connect();

        if ($withPrefix) {
            return $db->table($table);
        }

        return $db->name($table);
    }

    /**
     * 应用分级筛选条件
     * @param BaseQuery $query
     * @param mixed $filters
     * @param int $level
     * @return void
     */
    private function applyLinkagesFilters(BaseQuery $query, mixed $filters, int $level): void
    {
        if (!is_array($filters)) {
            return;
        }

        $levelFilter = $filters[$level] ?? ($filters[(string)$level] ?? null);
        if (!is_array($levelFilter) || empty($levelFilter)) {
            return;
        }

        if ($this->isStructuredLinkagesFilter($levelFilter)) {
            $this->applyStructuredLinkagesFilters($query, $levelFilter);
            return;
        }

        // 兼容旧格式：关联数组 / 三元条件数组，默认等同 where(...)
        if (array_is_list($levelFilter)) {
            foreach ($levelFilter as $condition) {
                if (is_array($condition) && count($condition) === 3) {
                    $query->where($condition[0], $condition[1], $condition[2]);
                }
            }
            return;
        }

        foreach ($levelFilter as $field => $value) {
            $query->where($field, '=', $value);
        }
    }

    /**
     * 判断是否为结构化联动过滤配置
     * @param array $levelFilter
     * @return bool
     */
    private function isStructuredLinkagesFilter(array $levelFilter): bool
    {
        foreach (array_keys($levelFilter) as $method) {
            if (!is_string($method)) {
                continue;
            }
            $normalizedMethod = $this->normalizeFilterMethod($method);
            if (in_array($normalizedMethod, self::LINKAGES_FILTER_METHODS, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 应用结构化联动过滤条件
     * @param BaseQuery $query
     * @param array $levelFilter
     * @return void
     */
    private function applyStructuredLinkagesFilters(BaseQuery $query, array $levelFilter): void
    {
        foreach ($levelFilter as $method => $conditions) {
            if (!is_string($method)) {
                continue;
            }

            $normalizedMethod = $this->normalizeFilterMethod($method);
            if (!in_array($normalizedMethod, self::LINKAGES_FILTER_METHODS, true)) {
                continue;
            }

            foreach ($this->normalizeFilterCalls($normalizedMethod, $conditions) as $args) {
                $query->{$normalizedMethod}(...$args);
            }
        }
    }

    /**
     * 规范化 filters 方法名
     * @param string $method
     * @return string
     */
    private function normalizeFilterMethod(string $method): string
    {
        return $method;
    }

    /**
     * 规范化单个方法的调用参数列表
     * @param string $method
     * @param mixed $conditions
     * @return array
     */
    private function normalizeFilterCalls(string $method, mixed $conditions): array
    {
        if ($conditions === null || $conditions === '' || $conditions === []) {
            return [];
        }

        if ($this->isGroupCapableFilterMethod($method)) {
            $groupCalls = $this->normalizeGroupFilterCalls($conditions);
            if ($groupCalls !== null) {
                return $groupCalls;
            }
        }

        // where/whereOr 兼容旧格式：
        // - ['status' => 1]
        // - [['status', '=', 1], ['type', '=', 'city']]
        if (($method === 'where' || $method === 'whereOr') && is_array($conditions) && !array_is_list($conditions)) {
            $calls = [];
            foreach ($conditions as $field => $value) {
                $calls[] = [$field, '=', $value];
            }
            return $calls;
        }

        if (!is_array($conditions)) {
            return [[$conditions]];
        }

        if ($method === 'whereRaw' && !array_is_list($conditions)) {
            $sql  = isset($conditions['sql']) && is_string($conditions['sql']) ? $conditions['sql'] : '';
            $bind = (isset($conditions['bind']) && is_array($conditions['bind'])) ? $conditions['bind'] : [];
            if ($sql !== '') {
                return [[$sql, $bind]];
            }
        }

        if (array_is_list($conditions)) {
            $allArrayItems = true;
            foreach ($conditions as $item) {
                if (!is_array($item)) {
                    $allArrayItems = false;
                    break;
                }
            }

            // 多组调用：[['id',[1,2]], ['status',[1]]]
            if ($allArrayItems) {
                return $conditions;
            }

            // 单次调用参数：['id', [1,2]]、['score > :score', ['score'=>60]]
            return [$conditions];
        }

        // 其余关联数组按单次调用传入（如 whereRaw(['sql' => '...']) 这类不推荐但兼容）
        return [[$conditions]];
    }

    /**
     * 是否为支持闭包分组的筛选方法
     * @param string $method
     * @return bool
     */
    private function isGroupCapableFilterMethod(string $method): bool
    {
        return in_array($method, ['where', 'whereOr', 'whereXor'], true);
    }

    /**
     * 规范化分组条件调用参数
     * @param mixed $conditions
     * @return array|null
     */
    private function normalizeGroupFilterCalls(mixed $conditions): ?array
    {
        if (!is_array($conditions) || $conditions === []) {
            return null;
        }

        if (!array_is_list($conditions) && array_key_exists('group', $conditions)) {
            return [[$this->createLinkagesFilterGroupClosure($conditions['group'])]];
        }

        if (!array_is_list($conditions)) {
            return null;
        }

        $calls = [];
        $hasGroupItem = false;
        foreach ($conditions as $item) {
            if (is_array($item) && !array_is_list($item) && array_key_exists('group', $item)) {
                $calls[] = [$this->createLinkagesFilterGroupClosure($item['group'])];
                $hasGroupItem = true;
                continue;
            }
            $calls[] = is_array($item) ? $item : [$item];
        }

        return $hasGroupItem ? $calls : null;
    }

    /**
     * 创建联动筛选分组闭包
     * @param mixed $group
     * @return \Closure
     */
    private function createLinkagesFilterGroupClosure(mixed $group): \Closure
    {
        return function (BaseQuery $query) use ($group): void {
            if (!is_array($group) || empty($group)) {
                return;
            }

            foreach ($group as $item) {
                if (!is_array($item) || empty($item)) {
                    continue;
                }

                if (!array_is_list($item)) {
                    foreach ($item as $method => $conditions) {
                        if (!is_string($method)) {
                            continue;
                        }
                        $normalizedMethod = $this->normalizeFilterMethod($method);
                        if (!in_array($normalizedMethod, self::LINKAGES_FILTER_METHODS, true)) {
                            continue;
                        }
                        foreach ($this->normalizeFilterCalls($normalizedMethod, $conditions) as $args) {
                            $query->{$normalizedMethod}(...$args);
                        }
                    }
                    continue;
                }

                if (!isset($item[0]) || !is_string($item[0])) {
                    continue;
                }

                $method = $this->normalizeFilterMethod($item[0]);
                if (!in_array($method, self::LINKAGES_FILTER_METHODS, true)) {
                    continue;
                }

                $argsSource = count($item) === 2 ? $item[1] : array_slice($item, 1);
                foreach ($this->normalizeFilterCalls($method, $argsSource) as $args) {
                    $query->{$method}(...$args);
                }
            }
        };
    }

    /**
     * 获取文件列表
     * @throws DbException
     */
    public function getFiles(): void
    {
        $params = $this->request->param();

        $files = FileService::getList(
            (string)($params['type'] ?? ''),
            [
                'keyword'  => $params['keyword'] ?? '',
                'per_page' => $params['per_page'] ?? 15,
            ]
        );

        $files->each(function ($item) {
            if (!in_array($item['ext'], config('upload.allowed_ext.image'))) {
                $item['preview'] = '/static/img/file.jpeg';
            } else {
                $item['preview'] = $item['url'];
            }
            return $item;
        });

        $this->success('获取成功', '', $files);
    }

    /**
     * 通过文件hash检查文件是否已存在
     */
    public function checkFile(): void
    {
        $param = $this->request->param();
        $hash  = $param['hash'] ?? '';
        $hash == '' && $this->error('参数错误');

        // 判断文件是否存在
        if ($file_exists = FileService::exists($hash)) {
            $result['success'][] = [
                'id'   => $file_exists['id'] ?? 0,
                'name' => $file_exists['name'] ?? '',
                'url'  => $file_exists['url'] ?? '',
                'sha1' => $file_exists['sha1'] ?? '',
            ];
            $this->success('上传成功', '', $result);
        } else {
            $this->error('文件不存在');
        }
    }

    /**
     * 保存文件信息
     * @throws Throwable
     */
    public function saveFile(): void
    {
        $params        = $this->request->param();
        $params['uid'] = $this->getCurrentUserId();
        $this->autoValidate('File', $params);

        // 判断是否为本地文件（非 OSS 等第三方存储）
        $isLocalFile = !preg_match('/^(https?:\/\/|\/\/)/i', $params['url']);

        if ($isLocalFile) {
            // 本地文件安全验证
            $filePath = public_path() . $params['url'];

            // 检查文件是否真实存在于服务器
            if (!file_exists($filePath)) {
                $this->error('文件不存在，无法保存');
            }

            // 验证文件 hash 是否匹配
            $realHash = sha1_file($filePath);
            if ($realHash !== $params['sha1']) {
                $this->error('文件校验失败，文件可能已被篡改');
            }

            // 判断文件格式是否合法（传入文件路径进行深度检测）
            if (FileService::overExt($params, $filePath)) {
                $this->error('文件类型不正确，或非法文件');
            }
        } else {
            // 远程文件（OSS 等）只做基础格式验证
            if (FileService::overExt($params)) {
                $this->error('文件类型不正确，或非法文件');
            }
        }

        // 判断文件是否存在
        $file = FileService::exists($params['sha1']);
        if (!$file) {
            $params['ext'] = strtolower($params['ext']);
            $file          = FileService::save($params);
        }

        if ($file) {
            $this->success('保存成功', null, [
                'success' => [
                    [
                        'id'   => $file['id'],
                        'name' => $file['name'],
                        'url'  => $file['url'],
                        'sha1' => $file['sha1'],
                    ]
                ],
                'error'   => []
            ]);
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 文件上传
     * @throws Throwable
     */
    #[PermissionAttribute('上传', type: 'api')]
    public function upload(): void
    {
        set_time_limit(0);
        $from  = $this->request->param('_from', '');
        $name  = $this->request->param('_name', 'file');
        $files = $this->request->file($name);

        if ($files instanceof File) {
            $files = [$files];
        }

        // 基础校验：未选择文件
        if (empty($files) || (is_array($files) && count($files) === 0)) {
            $this->error('未选择文件');
        }

        // 上传结果
        $result = [
            'success' => [],
            'error'   => []
        ];

        foreach ($files as $file) {
            // 判断文件是否存在（统一以数组项返回）
            if ($file_result = FileService::exists($file->hash())) {
                $result['success'][] = [
                    'id'   => $file_result['id'],
                    'name' => $file_result['name'],
                    'url'  => $file_result['url'],
                    'sha1' => $file_result['sha1'],
                ];
                continue;
            }

            // 判断文件大小是否超过限制（统一以数组项返回）
            if (FileService::overSize($file)) {
                $result['error'][] = [
                    'name' => $file->getOriginalName(),
                    'msg'  => '文件过大'
                ];
                continue;
            }

            // 判断文件格式是否合法（统一以数组项返回）
            if (FileService::overExt($file)) {
                $result['error'][] = [
                    'name' => $file->getOriginalName(),
                    'msg'  => '文件类型不正确，或非法文件'
                ];
                continue;
            }

            // 如果是裁剪, 固定后缀名
            if ($from == 'cropper') {
                $file->setExtension('png');
            }

            // 保存文件（统一以数组项返回）
            $filePath = Filesystem::disk('uploads')->putFile('', $file, 'md5');
            if (!$filePath) {
                $result['error'][] = [
                    'name' => $file->getOriginalName(),
                    'msg'  => '文件上传失败'
                ];
                continue;
            }

            // 获取文件的完整路径
            $fullPath = public_path() . '/uploads/' . $filePath;

            // 深度安全检查：检查文件格式和内容（传入实际文件路径）
            if (FileService::overExt($file, $fullPath)) {
                // 删除已上传的文件
                @unlink($fullPath);
                $result['error'][] = [
                    'name' => $file->getOriginalName(),
                    'msg'  => '文件内容检测失败，可能包含恶意代码'
                ];
                continue;
            }

            // 保存文件信息
            try {
                $fileInfo = FileService::saveFile($file, $filePath, $from);

                // 记录文件上传日志
                dp_log('文件上传')
                    ->type(Logger::TYPE_FILE_OPERATION)
                    ->context([
                        'file_id'     => $fileInfo['id'] ?? 0,
                        'file_name'   => $file->getOriginalName(),
                        'file_path'   => $fileInfo['url'] ?? '',
                        'file_size'   => $file->getSize(),
                        'file_type'   => $file->getOriginalMime(),
                        'file_ext'    => $fileInfo['ext'] ?? '',
                        'file_hash'   => $fileInfo['sha1'] ?? '',
                        'from_source' => $from,
                        'upload_time' => date('Y-m-d H:i:s')
                    ])
                    ->info();
            } catch (Exception) {
                // 删除已上传的文件
                @unlink($fullPath);
                $result['error'][] = [
                    'name' => $file->getOriginalName(),
                    'msg'  => '文件保存失败'
                ];
                continue;
            }

            // 统一以数组项返回
            $result['success'][] = [
                'id'   => $fileInfo['id'] ?? 0,
                'name' => $fileInfo['name'] ?? '',
                'url'  => $fileInfo['url'] ?? '',
                'sha1' => $fileInfo['sha1'] ?? '',
            ];
        }

        // 统一附带统计信息，便于前端快速判断
        $total           = is_array($files) ? count($files) : 0;
        $successCount    = count($result['success']);
        $errorCount      = count($result['error']);
        $result['stats'] = [
            'total'       => $total,
            'success'     => $successCount,
            'error'       => $errorCount,
            'all_success' => ($successCount > 0 && $errorCount === 0),
            'all_failed'  => ($successCount === 0 && $errorCount > 0),
        ];

        // 根据统计选择语义化返回
        if ($successCount === 0 && $errorCount > 0) {
            $this->error('上传失败', null, $result);
        } elseif ($successCount > 0 && $errorCount > 0) {
            $this->success('部分成功', null, $result);
        } else {
            $this->success('上传成功', null, $result);
        }
    }

    /**
     * 下载本地文件
     * @param string $hash
     * @return \think\response\File|void
     */
    public function download(string $hash = '')
    {
        if ($hash != '') {
            $file = FileService::getFileByHash($hash);
            if ($file && $file['driver'] == 'local') {
                return download('.' . $file['url'], $file['name']);
            }
        }
        $this->error('文件不存在或不支持下载');
    }
}
