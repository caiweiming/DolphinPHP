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

namespace app\common\service;

use app\common\model\Config as ConfigModel;
use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\Validate;
use Throwable;

/**
 * 动态配置服务
 */
class ConfigService
{
    /**
     * 默认应用
     */
    public const DEFAULT_APP = 'admin';

    /**
     * 支持的配置类型白名单
     */
    public const SUPPORTED_TYPES = [
        'text'     => '单行文本',
        'textarea' => '多行文本',
        'number'   => '数字',
        'switch'   => '开关',
        'radio'    => '单选框',
        'checkbox' => '多选框',
        'select'   => '下拉选择',
        'select2'  => '增强下拉',
        'tags'     => '标签输入',
        'image'    => '图片上传',
        'file'     => '文件上传',
        'color'    => '颜色选择',
        'date'     => '日期',
        'datetime' => '日期时间',
        'time'     => '时间',
    ];

    /**
     * 结构化扩展参数可见类型映射
     */
    private const OPTION_EDITOR_FIELD_TYPES = [
        'placeholder'  => ['text', 'textarea', 'number', 'select', 'select2', 'tags', 'color', 'date', 'datetime', 'time'],
        'tips'         => ['text', 'textarea', 'number', 'switch', 'radio', 'checkbox', 'select', 'select2', 'tags', 'image', 'file', 'color', 'date', 'datetime', 'time'],
        'option_lines' => ['radio', 'checkbox', 'select', 'select2'],
        'multiple'     => ['select', 'select2'],
        'driver'       => ['file', 'image'],
        'dir'          => ['file', 'image'],
    ];

    /**
     * 缓存键
     */
    private const CACHE_KEY_ALL = 'admin_config:all';

    /**
     * 启用配置缓存键
     */
    private const CACHE_KEY_ENABLED = 'admin_config:enabled';

    /**
     * 缓存时间
     */
    private const CACHE_TTL = 600;

    /**
     * 配置模型
     * @var ConfigModel
     */
    protected ConfigModel $model;

    /**
     * @param ConfigModel|null $model
     */
    public function __construct(?ConfigModel $model = null)
    {
        $this->model = $model ?? new ConfigModel();
    }

    /**
     * 获取配置类型列表
     * @return array
     */
    public function getTypeOptions(): array
    {
        return self::SUPPORTED_TYPES;
    }

    /**
     * 获取结构化扩展参数可见类型映射
     * @return array
     */
    public function getOptionEditorTypeMap(): array
    {
        return self::OPTION_EDITOR_FIELD_TYPES;
    }

    /**
     * 获取上传驱动选项
     * @return array
     */
    public function getUploadDriverOptions(): array
    {
        return [
            'local'  => '本地存储',
            'aliyun' => '阿里云 OSS',
            'qiniu'  => '七牛云存储',
        ];
    }

    /**
     * 判断是否为允许的配置类型
     * @param string $type
     * @return bool
     */
    public function isSupportedType(string $type): bool
    {
        return array_key_exists(trim($type), self::SUPPORTED_TYPES);
    }

    /**
     * 获取分组选项
     * @param string $app
     * @param bool $enabledOnly
     * @return array
     */
    public function getGroupOptions(string $app = '', bool $enabledOnly = false): array
    {
        $groups = [];
        foreach ($this->getConfigs($enabledOnly, $app) as $record) {
            $group = trim((string)($record['group'] ?? ''));
            if ($group === '') {
                continue;
            }
            $groups[$group] = $group;
        }

        return $groups;
    }

    /**
     * 获取全部配置
     * @param bool $enabledOnly
     * @param string $app
     * @return array
     */
    public function getConfigs(bool $enabledOnly = false, string $app = ''): array
    {
        $cacheKey = $enabledOnly ? self::CACHE_KEY_ENABLED : self::CACHE_KEY_ALL;

        try {
            $records = Cache::remember($cacheKey, function () use ($enabledOnly) {
                $query = $this->model
                    ->order('app', 'asc')
                    ->order('group', 'asc')
                    ->order('sort', 'asc')
                    ->order('id', 'asc');

                if ($enabledOnly) {
                    $query->where('status', 1);
                }

                return $query->select()->toArray();
            }, self::CACHE_TTL);

            $app = trim($app);
            if ($app === '') {
                return $records;
            }

            return array_values(array_filter($records, static function (array $record) use ($app): bool {
                return (string)($record['app'] ?? self::DEFAULT_APP) === $app;
            }));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 获取键名映射
     * @param bool $enabledOnly
     * @param string $app
     * @return array
     */
    public function getConfigMap(bool $enabledOnly = true, string $app = self::DEFAULT_APP): array
    {
        $map = [];
        foreach ($this->getConfigs($enabledOnly, $app) as $record) {
            $key = trim((string)($record['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $map[$key] = $record;
        }

        return $map;
    }

    /**
     * 获取按分组组织的启用配置
     * @param string $app
     * @return array
     */
    public function getEnabledConfigsByGroup(string $app = self::DEFAULT_APP): array
    {
        $result    = [];
        $groupSort = [];

        foreach ($this->getConfigs(true, $app) as $record) {
            $group            = trim((string)($record['group'] ?? ''));
            $group            = $group !== '' ? $group : '默认分组';
            $result[$group][] = $record;

            $sort = (int)($record['sort'] ?? 0);
            if (!isset($groupSort[$group]) || $sort < $groupSort[$group]) {
                $groupSort[$group] = $sort;
            }
        }

        if ($result === []) {
            return [];
        }

        uksort($result, static function (string $left, string $right) use ($groupSort): int {
            $leftSort  = $groupSort[$left] ?? 0;
            $rightSort = $groupSort[$right] ?? 0;

            if ($leftSort === $rightSort) {
                return strcmp($left, $right);
            }

            return $leftSort <=> $rightSort;
        });

        return $result;
    }

    /**
     * 统一读取动态配置
     * @param string $key
     * @param mixed $default
     * @param string $app
     * @return mixed
     */
    public function get(string $key, mixed $default = null, string $app = self::DEFAULT_APP): mixed
    {
        $key = trim($key);
        if ($key === '') {
            return $default;
        }

        $record = $this->getConfigMap(true, $app)[$key] ?? null;
        if (!$record) {
            return $default;
        }

        $value = $this->convertRecordValue($record);
        return $value !== null ? $value : $default;
    }

    /**
     * 准备配置定义持久化数据
     * @param array $data
     * @param ConfigModel|null $existing
     * @param bool $allowSystemFlag
     * @return array
     * @throws Exception
     */
    public function preparePersistData(
        array        $data,
        ?ConfigModel $existing = null,
        bool         $allowSystemFlag = false
    ): array
    {
        $payload = [
            'app'           => trim((string)($data['app'] ?? $existing?->getAttr('app') ?? self::DEFAULT_APP)),
            'group'         => trim((string)($data['group'] ?? '')),
            'title'         => trim((string)($data['title'] ?? '')),
            'key'           => strtolower(trim((string)($data['key'] ?? ''))),
            'type'          => trim((string)($data['type'] ?? '')),
            'value'         => $this->normalizeEditorValue($data['value'] ?? ''),
            'default_value' => $this->normalizeEditorValue($data['default_value'] ?? ''),
            'options'       => $this->normalizeOptionsForStorage($data),
            'rules'         => $this->normalizeRulesForStorage($data['rules'] ?? ''),
            'remark'        => trim((string)($data['remark'] ?? '')),
            'sort'          => max(0, (int)($data['sort'] ?? 0)),
            'status'        => !empty($data['status']) ? 1 : 0,
            'is_system'     => $allowSystemFlag
                ? (!empty($data['is_system']) ? 1 : 0)
                : (int)($existing?->getAttr('is_system') ?? 0),
        ];

        if (!$this->isSupportedType($payload['type'])) {
            throw new Exception('配置类型不受支持');
        }

        if ($payload['app'] === '') {
            throw new Exception('所属应用不能为空');
        }

        if (!app(AppService::class)->canUseDynamicConfig($payload['app'])) {
            throw new Exception('当前应用未被授权在 Config 中管理动态配置');
        }

        $this->assertUniqueKey($payload['app'], $payload['key'], $existing);

        return $payload;
    }

    /**
     * 保存系统设置值
     * @param array $submitted
     * @param array $records
     * @return array
     * @throws Exception
     */
    public function saveValues(array $submitted, array $records): array
    {
        $updates = [];
        $changes = [];

        foreach ($records as $record) {
            $key      = trim((string)($record['key'] ?? ''));
            $title    = trim((string)($record['title'] ?? $key));
            $existing = (string)($record['value'] ?? '');
            $rawValue = array_key_exists($key, $submitted)
                ? $submitted[$key]
                : $this->getMissingSubmittedValue($record);

            $this->validateSystemValue($record, $rawValue, $title);
            $storedValue = $this->normalizeSubmittedValueInternal($record, $rawValue);

            if ($storedValue === $existing) {
                continue;
            }

            $updates[(int)$record['id']] = [
                'value' => $storedValue,
            ];

            $changes[] = [
                'id'     => (int)$record['id'],
                'app'    => (string)($record['app'] ?? self::DEFAULT_APP),
                'source' => 'dynamic',
                'group'  => (string)($record['group'] ?? ''),
                'title'  => $title,
                'key'    => $key,
                'type'   => (string)($record['type'] ?? ''),
                'before' => $this->summarizeStoredValue($existing, (string)($record['type'] ?? ''), $record),
                'after'  => $this->summarizeStoredValue($storedValue, (string)($record['type'] ?? ''), $record),
            ];
        }

        if ($updates === []) {
            return [];
        }

        Db::transaction(function () use ($updates) {
            foreach ($updates as $id => $payload) {
                $this->model->where('id', $id)->update($payload);
            }
        });

        $this->clearCache();
        return $changes;
    }

    /**
     * 判断指定应用是否存在动态配置
     * @param string $app
     * @return bool
     */
    public function hasConfigs(string $app = self::DEFAULT_APP): bool
    {
        return $this->getConfigs(false, $app) !== [];
    }

    /**
     * 构建 System 页面表单项
     * @param array $record
     * @return array
     */
    public function buildFormItem(array $record): array
    {
        $key      = trim((string)($record['key'] ?? ''));
        $type     = trim((string)($record['type'] ?? 'text'));
        $extra    = $this->decodeOptions((string)($record['options'] ?? ''));
        $required = $this->isRequiredRule((string)($record['rules'] ?? ''));
        $item     = [
            'type'     => $type,
            'name'     => sprintf('config[%s]', $key),
            'id'       => 'config_' . substr(md5($key), 0, 12),
            'label'    => (string)($record['title'] ?? $key),
            'value'    => $this->convertRecordValue($record, true),
            'required' => $required,
        ];

        $tips = trim((string)($extra['tips'] ?? ''));
        if ($tips === '') {
            $tips = trim((string)($record['remark'] ?? ''));
        }
        if ($tips !== '') {
            $item['tips'] = $tips;
        }

        if (isset($extra['help'])) {
            $item['help'] = (string)$extra['help'];
        }

        if ($type === 'switch' && isset($extra['text']) && !isset($extra['title'])) {
            $extra['title'] = (string)$extra['text'];
            unset($extra['text']);
        }

        unset($extra['tips'], $extra['required']);
        return array_replace_recursive($item, $extra);
    }

    /**
     * 准备编辑页显示数据
     * @param array|ConfigModel $record
     * @return array
     */
    public function prepareEditorData(array|ConfigModel $record): array
    {
        $data = $record instanceof ConfigModel ? $record->toArray() : $record;

        $data['rules'] = $this->prettyPrintJson((string)($data['rules'] ?? ''), false);
        return array_merge($data, $this->extractOptionEditorData(
            (string)($data['options'] ?? ''),
            (string)($data['type'] ?? 'text')
        ));
    }

    /**
     * 格式化已存储值摘要
     * @param mixed $value
     * @param string $type
     * @param array $record
     * @return string
     */
    public function summarizeStoredValue(mixed $value, string $type, array $record = []): string
    {
        $value = is_string($value) ? $value : $this->normalizeEditorValue($value);
        if ($value === '') {
            return '-';
        }

        $type = trim($type);
        if ($type === 'switch') {
            return $this->toBoolValue($value) ? '开启' : '关闭';
        }

        if ($type === 'file' || $type === 'image') {
            $items = $this->parseListValue($value);
            return count($items) > 1 ? sprintf('%d 个附件', count($items)) : ('附件 #' . ($items[0] ?? $value));
        }

        if ($type === 'checkbox' || $type === 'tags' || $this->usesMultipleValue($record)) {
            $items = $this->parseListValue($value);
            if ($items === []) {
                return '-';
            }
            $text = implode(' / ', $items);
            return mb_strlen($text) > 36 ? (mb_substr($text, 0, 36) . '...') : $text;
        }

        if ($this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $text = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_string($text)) {
                    return mb_strlen($text) > 36 ? (mb_substr($text, 0, 36) . '...') : $text;
                }
            }
        }

        $text = strip_tags($value);
        return mb_strlen($text) > 36 ? (mb_substr($text, 0, 36) . '...') : $text;
    }

    /**
     * 对外暴露：转换存储值
     * @param array $record
     * @param mixed $value
     * @param bool $forForm
     * @return mixed
     */
    public function convertStoredValue(array $record, mixed $value, bool $forForm = false): mixed
    {
        $temp          = $record;
        $temp['value'] = is_string($value) ? $value : $this->normalizeEditorValue($value);

        return $this->convertRecordValue($temp, $forForm);
    }

    /**
     * 对外暴露：校验提交值
     * @param array $record
     * @param mixed $value
     * @param string|null $title
     * @return void
     * @throws Exception
     */
    public function validateSubmittedValue(array $record, mixed $value, ?string $title = null): void
    {
        $this->validateSystemValue($record, $value, trim((string)($title ?? ($record['title'] ?? $record['key'] ?? '配置项'))));
    }

    /**
     * 对外暴露：规范化提交值
     * @param array $record
     * @param mixed $value
     * @return string
     */
    public function normalizeSubmittedValue(array $record, mixed $value): string
    {
        return $this->normalizeSubmittedValueInternal($record, $value);
    }

    /**
     * 对外暴露：获取缺失提交值
     * @param array $record
     * @return string|int|array
     */
    public function getMissingSubmittedValue(array $record): string|int|array
    {
        return $this->resolveMissingSubmittedValue($record);
    }

    /**
     * 清理缓存
     * @return void
     */
    public function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY_ALL);
        Cache::delete(self::CACHE_KEY_ENABLED);
    }

    /**
     * 转换记录值
     * @param array $record
     * @param bool $forForm
     * @return mixed
     */
    private function convertRecordValue(array $record, bool $forForm = false): mixed
    {
        $raw = $this->resolveStoredValue($record);
        if ($raw === '') {
            return $forForm ? '' : null;
        }

        $type = trim((string)($record['type'] ?? 'text'));

        return match ($type) {
            'switch' => $this->toBoolValue($raw) ? 1 : 0,
            'number' => $this->toNumericValue($raw),
            'checkbox' => $this->parseListValue($raw),
            'tags' => $forForm ? implode(',', $this->parseListValue($raw)) : $this->parseListValue($raw),
            'select',
            'select2' => $this->usesMultipleValue($record)
                ? $this->parseListValue($raw)
                : $raw,
            'file',
            'image' => $this->usesMultipleValue($record)
                ? implode(',', $this->parseListValue($raw))
                : $raw,
            default => $raw,
        };
    }

    /**
     * 获取真实存储值
     * @param array $record
     * @return string
     */
    private function resolveStoredValue(array $record): string
    {
        $value = (string)($record['value'] ?? '');
        if ($value !== '') {
            return $value;
        }

        return (string)($record['default_value'] ?? '');
    }

    /**
     * 断言应用内配置键唯一
     * @param string $app
     * @param string $key
     * @param ConfigModel|null $existing
     * @return void
     * @throws Exception
     */
    private function assertUniqueKey(string $app, string $key, ?ConfigModel $existing = null): void
    {
        $query = $this->model
            ->where('app', $app)
            ->where('key', $key);

        if ($existing && $existing->getAttr('id')) {
            $query->where('id', '<>', (int)$existing->getAttr('id'));
        }

        if ($query->find()) {
            throw new Exception('同一应用下的配置键名已存在');
        }
    }

    /**
     * 规范化编辑器输入值
     * @param mixed $value
     * @return string
     */
    private function normalizeEditorValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '' : $encoded;
        }

        return (string)$value;
    }

    /**
     * 规范化扩展参数存储格式
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    private function normalizeOptionsForStorage(mixed $value): string
    {
        if (is_array($value)) {
            if ($this->hasStructuredOptionInputs($value)) {
                return $this->buildOptionsFromEditorData($value);
            }

            if (array_key_exists('options', $value) && count($value) === 1) {
                return $this->normalizeLegacyOptionsInput($value['options']);
            }

            return $this->encodeJson($value);
        }

        return $this->normalizeLegacyOptionsInput($value);
    }

    /**
     * 规范化旧版原始 JSON 输入
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    private function normalizeLegacyOptionsInput(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new Exception('扩展参数必须是合法的 JSON 对象或数组');
        }

        return $this->encodeJson($decoded);
    }

    /**
     * 判断是否包含结构化扩展参数输入
     * @param array $data
     * @return bool
     */
    private function hasStructuredOptionInputs(array $data): bool
    {
        foreach (['placeholder', 'tips', 'option_lines', 'multiple', 'driver', 'dir', 'extra_options'] as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从结构化编辑字段构建 options 存储值
     * @param array $data
     * @return string
     * @throws Exception
     */
    private function buildOptionsFromEditorData(array $data): string
    {
        $type      = trim((string)($data['type'] ?? 'text'));
        $payload   = $this->parseExtraOptionsInput($data['extra_options'] ?? '');
        $typeMap   = $this->getOptionEditorTypeMap();
        $isUpload  = in_array($type, $typeMap['driver'], true);
        $canChoice = in_array($type, $typeMap['option_lines'], true);
        $canMulti  = in_array($type, $typeMap['multiple'], true);

        if (in_array($type, $typeMap['placeholder'], true)) {
            unset($payload['placeholder']);
            $placeholder = trim((string)($data['placeholder'] ?? ''));
            if ($placeholder !== '') {
                $payload['placeholder'] = $placeholder;
            }
        }

        if (in_array($type, $typeMap['tips'], true)) {
            unset($payload['tips']);
            $tips = trim((string)($data['tips'] ?? ''));
            if ($tips !== '') {
                $payload['tips'] = $tips;
            }
        }

        if ($canChoice) {
            $choiceOptions = $this->parseOptionLines((string)($data['option_lines'] ?? ''));
            if ($choiceOptions !== []) {
                $payload['options'] = $choiceOptions;
            }
        }

        if ($canMulti) {
            if ($isUpload) {
                $uploadOptions = isset($payload['options']) && is_array($payload['options'])
                    ? $payload['options']
                    : [];
                unset($uploadOptions['multiple']);

                if ($this->toBoolValue($data['multiple'] ?? 0)) {
                    $uploadOptions['multiple'] = true;
                }

                if ($uploadOptions === []) {
                    unset($payload['options']);
                } else {
                    $payload['options'] = $uploadOptions;
                }
            } else {
                unset($payload['multiple']);
                if ($this->toBoolValue($data['multiple'] ?? 0)) {
                    $payload['multiple'] = true;
                }
            }
        }

        if ($isUpload) {
            unset($payload['driver']);
            $driver = trim((string)($data['driver'] ?? ''));
            if ($driver !== '') {
                $payload['driver'] = $driver;
            }

            unset($payload['dir']);
            $dir = trim((string)($data['dir'] ?? ''));
            if ($dir !== '') {
                $payload['dir'] = trim($dir, '/');
            }
        }

        if ($payload === []) {
            return '';
        }

        return $this->encodeJson($payload);
    }

    /**
     * 规范化校验规则存储格式
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    private function normalizeRulesForStorage(mixed $value): string
    {
        if (is_array($value)) {
            return $this->encodeJson($value);
        }

        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if ($this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                throw new Exception('校验规则 JSON 格式不正确');
            }
            return $this->encodeJson($decoded);
        }

        return preg_replace("/(\r\n|\r|\n)+/", "\n", $value) ?? $value;
    }

    /**
     * 校验系统设置值
     * @param array $record
     * @param mixed $value
     * @param string $title
     * @return void
     * @throws Exception
     */
    private function validateSystemValue(array $record, mixed $value, string $title): void
    {
        $rules = $this->parseRules((string)($record['rules'] ?? ''));
        if ($rules === '' || $rules === []) {
            return;
        }

        $validator = new Validate();
        $validator
            ->rule(['value|' . $title => $rules])
            ->failException();

        try {
            $validator->check(['value' => $value]);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 规范化系统设置提交值
     * @param array $record
     * @param mixed $value
     * @return string
     */
    private function normalizeSubmittedValueInternal(array $record, mixed $value): string
    {
        $type = trim((string)($record['type'] ?? 'text'));

        return match ($type) {
            'switch' => $this->toBoolValue($value) ? '1' : '0',
            'checkbox' => implode(',', $this->sanitizeListValue($value)),
            'select',
            'select2' => $this->usesMultipleValue($record)
                ? implode(',', $this->sanitizeListValue($value))
                : trim((string)$value),
            'tags', 'file', 'image' => is_array($value)
                ? implode(',', $this->sanitizeListValue($value))
                : trim((string)$value),
            'number' => trim((string)$value),
            default => is_string($value) ? trim($value) : $this->normalizeEditorValue($value),
        };
    }

    /**
     * 获取缺失提交时的默认值
     * @param array $record
     * @return string|int|array
     */
    private function resolveMissingSubmittedValue(array $record): string|int|array
    {
        $type = trim((string)($record['type'] ?? 'text'));

        return match ($type) {
            'switch' => 0,
            'checkbox' => [],
            'file',
            'image', 'select', 'select2' => $this->usesMultipleValue($record) ? [] : '',
            default => '',
        };
    }

    /**
     * 解析扩展参数
     * @param string $value
     * @return array
     */
    private function decodeOptions(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 拆解 options 为结构化编辑字段
     * @param string $value
     * @param string $type
     * @return array
     */
    private function extractOptionEditorData(string $value, string $type): array
    {
        $payload = $this->decodeOptions($value);
        $working = $payload;
        $result  = [
            'placeholder'   => '',
            'tips'          => '',
            'option_lines'  => '',
            'multiple'      => 0,
            'driver'        => '',
            'dir'           => '',
            'extra_options' => '',
        ];
        $typeMap = $this->getOptionEditorTypeMap();

        if (in_array($type, $typeMap['placeholder'], true) && isset($working['placeholder'])) {
            $result['placeholder'] = (string)$working['placeholder'];
            unset($working['placeholder']);
        }

        if (in_array($type, $typeMap['tips'], true) && isset($working['tips'])) {
            $result['tips'] = (string)$working['tips'];
            unset($working['tips']);
        }

        if (in_array($type, $typeMap['option_lines'], true)
            && isset($working['options'])
            && is_array($working['options'])
            && $this->isSimpleOptionMap($working['options'])) {
            $result['option_lines'] = $this->formatOptionLines($working['options']);
            unset($working['options']);
        }

        if (in_array($type, $typeMap['multiple'], true)) {
            if (in_array($type, $typeMap['driver'], true)) {
                $uploadOptions = $working['options'] ?? null;
                if (is_array($uploadOptions) && array_key_exists('multiple', $uploadOptions) && $this->isSimpleMultipleValue($uploadOptions['multiple'])) {
                    $result['multiple'] = $this->toBoolValue($uploadOptions['multiple']) ? 1 : 0;
                    unset($uploadOptions['multiple']);

                    if ($uploadOptions === []) {
                        unset($working['options']);
                    } else {
                        $working['options'] = $uploadOptions;
                    }
                }
            } elseif (array_key_exists('multiple', $working) && $this->isSimpleMultipleValue($working['multiple'])) {
                $result['multiple'] = $this->toBoolValue($working['multiple']) ? 1 : 0;
                unset($working['multiple']);
            }
        }

        if (in_array($type, $typeMap['driver'], true) && isset($working['driver'])) {
            $result['driver'] = (string)$working['driver'];
            unset($working['driver']);
        }

        if (in_array($type, $typeMap['dir'], true) && isset($working['dir'])) {
            $result['dir'] = trim((string)$working['dir'], '/');
            unset($working['dir']);
        }

        if ($working !== []) {
            $result['extra_options'] = $this->prettyPrintJson($this->encodeJsonSafely($working));
        }

        return $result;
    }

    /**
     * 解析校验规则
     * @param string $value
     * @return array|string
     */
    private function parseRules(string $value): array|string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : '';
        }

        $value = str_replace(["\r\n", "\r", "\n"], '|', $value);
        $value = preg_replace('/\|+/', '|', $value) ?? $value;

        return trim($value, '|');
    }

    /**
     * 判断规则中是否包含必填
     * @param string $rules
     * @return bool
     */
    private function isRequiredRule(string $rules): bool
    {
        $parsed = $this->parseRules($rules);
        if (is_array($parsed)) {
            $flat = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($flat) && str_contains($flat, 'require');
        }

        return $parsed !== '' && str_contains($parsed, 'require');
    }

    /**
     * 是否多值配置
     * @param array $record
     * @return bool
     */
    private function usesMultipleValue(array $record): bool
    {
        $type    = trim((string)($record['type'] ?? ''));
        $options = $this->decodeOptions((string)($record['options'] ?? ''));

        if ($type === 'checkbox') {
            return true;
        }

        if ($type === 'select' || $type === 'select2') {
            return !empty($options['multiple']);
        }

        if ($type === 'file' || $type === 'image') {
            $uploadOptions = $options['options'] ?? $options;
            if (!is_array($uploadOptions)) {
                return false;
            }

            $multiple = $uploadOptions['multiple'] ?? false;
            if (is_numeric($multiple)) {
                return (int)$multiple !== 1;
            }

            return (bool)$multiple;
        }

        return false;
    }

    /**
     * 解析数组值
     * @param string $value
     * @return array
     */
    private function parseListValue(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if ($this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->sanitizeListValue($decoded);
            }
        }

        return $this->sanitizeListValue(explode(',', $value));
    }

    /**
     * 清洗数组值
     * @param mixed $value
     * @return array
     */
    private function sanitizeListValue(mixed $value): array
    {
        $result = [];
        foreach ((array)$value as $item) {
            if ($item === null) {
                continue;
            }

            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            $result[] = $item;
        }

        return array_values(array_unique($result));
    }

    /**
     * 转换布尔值
     * @param mixed $value
     * @return bool
     */
    private function toBoolValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return in_array((string)$value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * 转换数字值
     * @param string $value
     * @return int|float|string
     */
    private function toNumericValue(string $value): int|float|string
    {
        if (!is_numeric($value)) {
            return $value;
        }

        return str_contains($value, '.') ? (float)$value : (int)$value;
    }

    /**
     * 判断是否为 JSON 字符串
     * @param string $value
     * @return bool
     */
    private function looksLikeJson(string $value): bool
    {
        $value = trim($value);
        return ($value !== '') && (
                (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
                (str_starts_with($value, '[') && str_ends_with($value, ']'))
            );
    }

    /**
     * 美化 JSON 文本
     * @param string $value
     * @param bool $onlyJson
     * @return string
     */
    private function prettyPrintJson(string $value, bool $onlyJson = true): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!$this->looksLikeJson($value) && $onlyJson) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $value;
        }

        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : $value;
    }

    /**
     * 解析高级参数 JSON
     * @param mixed $value
     * @return array
     * @throws Exception
     */
    private function parseExtraOptionsInput(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            throw new Exception('高级参数必须是合法的 JSON 对象或数组');
        }

        return $decoded;
    }

    /**
     * 解析逐行选项
     * @param string $value
     * @return array
     */
    private function parseOptionLines(string $value): array
    {
        $value  = preg_replace("/(\r\n|\r)/", "\n", $value) ?? $value;
        $lines  = explode("\n", $value);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (!str_contains($line, ':')) {
                $result[$line] = $line;
                continue;
            }

            [$key, $label] = explode(':', $line, 2);
            $key   = trim($key);
            $label = trim($label);

            if ($key === '' && $label === '') {
                continue;
            }

            if ($key === '') {
                $key = $label;
            }

            if ($label === '') {
                $label = $key;
            }

            $result[$key] = $label;
        }

        return $result;
    }

    /**
     * 格式化选项为逐行文本
     * @param array $options
     * @return string
     */
    private function formatOptionLines(array $options): string
    {
        $lines  = [];
        $isList = array_keys($options) === range(0, count($options) - 1);

        foreach ($options as $key => $label) {
            $key   = (string)$key;
            $label = is_scalar($label) || $label === null ? trim((string)$label) : '';

            if ($label === '') {
                continue;
            }

            if ($isList || $key === $label) {
                $lines[] = $label;
                continue;
            }

            $lines[] = $key . ':' . $label;
        }

        return implode("\n", $lines);
    }

    /**
     * 判断选项是否可转换为逐行文本
     * @param array $options
     * @return bool
     */
    private function isSimpleOptionMap(array $options): bool
    {
        foreach ($options as $value) {
            if (is_array($value) || is_object($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断 multiple 值是否适合映射为布尔开关
     * @param mixed $value
     * @return bool
     */
    private function isSimpleMultipleValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value) || is_float($value)) {
            return in_array((string)$value, ['0', '1'], true);
        }

        $value = strtolower(trim((string)$value));
        return in_array($value, ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'], true);
    }

    /**
     * 安全编码数组为 JSON
     * @param array $value
     * @return string
     */
    private function encodeJsonSafely(array $value): string
    {
        try {
            return $this->encodeJson($value);
        } catch (Exception) {
            return '';
        }
    }

    /**
     * 编码 JSON
     * @param array $value
     * @return string
     * @throws Exception
     */
    private function encodeJson(array $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new Exception('JSON 编码失败');
        }

        return $encoded;
    }

}
