<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

use app\common\render\Table;
use app\showcase\service\demo\ShowcaseTableDemoDataService;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use think\facade\Db;

/**
 * Showcase 表格真实演示构建器
 *
 * 负责为详情页生成真实 Table 预览，以及按数据集返回演示数据。
 */
final class TableLiveDemoBuilder
{
    /**
     * @var string
     */
    private const DEMO_TOKEN_CACHE_KEY_PREFIX = 'showcase:table:live-demo:';

    /**
     * Showcase callback 预设标记键。
     */
    private const CALLBACK_PRESET_KEY = '__showcase_callback__';

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     * @return array{dataset:string,table_id:string,demo_token:string,endpoint_route:string,html:string}
     */
    public function buildMainTable(array $columns, array $options = []): array
    {
        return $this->build('main', $columns, $options);
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     * @return array{dataset:string,table_id:string,demo_token:string,endpoint_route:string,html:string}
     */
    public function buildMediaTable(array $columns, array $options = []): array
    {
        return $this->build('media', $columns, $options);
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     * @return array{dataset:string,table_id:string,demo_token:string,endpoint_route:string,html:string}
     */
    public function buildComplexTable(array $columns, array $options = []): array
    {
        return $this->build('complex', $columns, $options);
    }

    public function makeTableFromToken(string $token): Table
    {
        $payload = cache($this->cacheKey($token));
        if (!is_array($payload)) {
            throw new RuntimeException(sprintf('Showcase live table demo token not found: %s', $token));
        }

        $dataset = (string) ($payload['dataset'] ?? '');
        $tableId = (string) ($payload['table_id'] ?? '');
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        $query = is_array($payload['query'] ?? null) ? $payload['query'] : [];

        if ($dataset === '' || $tableId === '' || $columns === []) {
            throw new RuntimeException(sprintf('Showcase live table demo payload is invalid: %s', $token));
        }

        $this->forgetTableInstance($tableId);

        $endpoint = $this->buildEndpointUrl($dataset, $tableId, $token);

        $table = Table::make($tableId)
            ->tableName($this->crudTableNameFor($dataset))
            ->checkbox(false)
            ->url($endpoint)
            ->data(fn() => $this->fetchRows($dataset, $query))
            ->columns($this->resolveRuntimeColumns($columns))
            ->options(array_merge([
                'page' => false,
                'limit' => 999,
                // live demo 只允许表格自身携带 tid，避免继承详情页 key 等参数后把数据筛空。
                'where' => [],
            ], $options));

        return $table;
    }

    /**
     * @param string $dataset
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    public function fetchRows(string $dataset, array $query = []): array
    {
        $this->ensureDatasetSeeded($dataset);

        $db = match ($dataset) {
            'main' => Db::name('showcase_table'),
            'media' => Db::name('showcase_table_media'),
            'complex' => Db::name('showcase_table_complex'),
            default => throw new InvalidArgumentException(sprintf('Unknown showcase table live dataset: %s', $dataset)),
        };

        foreach ((array) ($query['where'] ?? []) as $condition) {
            if (is_array($condition) && count($condition) >= 3) {
                $db->where($condition[0], $condition[1], $condition[2]);
            }
        }

        $whereIn = $query['where_in'] ?? null;
        if (is_array($whereIn) && count($whereIn) === 2 && is_array($whereIn[1])) {
            $db->whereIn((string) $whereIn[0], $whereIn[1]);
        }

        $order = $query['order'] ?? null;
        if (is_array($order) && count($order) === 2) {
            $db->order((string) $order[0], (string) $order[1]);
        } else {
            $db->order($dataset === 'media' ? 'id' : 'sort', 'asc');
        }

        $limit = (int) ($query['limit'] ?? 0);
        if ($limit > 0) {
            $db->limit($limit);
        }

        return $this->normalizeRows($dataset, $db->select()->toArray());
    }

    /**
     * @param string $dataset
     * @param array<int, array<int|string, mixed>> $columns
     * @param array<string, mixed> $options
     * @return array{dataset:string,table_id:string,demo_token:string,endpoint_route:string,html:string}
     */
    private function build(string $dataset, array $columns, array $options = []): array
    {
        $query = $this->extractQueryOptions($options);
        $tableId = sprintf('showcase_table_live_%s_%s', $dataset, substr(md5(serialize([$columns, $options])), 0, 10));
        $demoToken = sprintf('%s-%s', $dataset, substr(sha1(serialize([$dataset, $tableId, $columns, $options])), 0, 16));
        $endpoint = $this->buildEndpointUrl($dataset, $tableId, $demoToken);

        cache($this->cacheKey($demoToken), [
            'dataset' => $dataset,
            'table_id' => $tableId,
            'columns' => $columns,
            'options' => $options,
            'query' => $query,
        ], 3600);

        $table = $this->makeTableFromToken($demoToken)
            ->url($endpoint)
            ->renderPage();

        return [
            'dataset' => $dataset,
            'table_id' => $tableId,
            'demo_token' => $demoToken,
            'endpoint_route' => 'showcase/admin.table/show',
            'html' => $table->fetch(),
        ];
    }

    private function cacheKey(string $token): string
    {
        return self::DEMO_TOKEN_CACHE_KEY_PREFIX . $token;
    }

    private function buildEndpointUrl(string $dataset, string $tableId, string $token): string
    {
        return sprintf(
            '/showcase/admin.table/show.html?key=_live_demo&_table_demo=1&_dataset=%s&_table_id=%s&_demo_token=%s',
            rawurlencode($dataset),
            rawurlencode($tableId),
            rawurlencode($token)
        );
    }

    /**
     * 从表格 options 中提取仅用于查询的配置项，并原地移除。
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function extractQueryOptions(array &$options): array
    {
        $query = [
            'where' => (array) ($options['_where'] ?? []),
            'where_in' => $options['_where_in'] ?? null,
            'order' => $options['_order'] ?? null,
            'limit' => $options['_limit'] ?? null,
        ];

        unset($options['_where'], $options['_where_in'], $options['_order'], $options['_limit']);

        return $query;
    }

    private function ensureDatasetSeeded(string $dataset): void
    {
        $table = match ($dataset) {
            'main' => 'showcase_table',
            'media' => 'showcase_table_media',
            'complex' => 'showcase_table_complex',
            default => throw new InvalidArgumentException(sprintf('Unknown showcase table live dataset: %s', $dataset)),
        };

        if ((int) Db::name($table)->count() > 0) {
            return;
        }

        app(ShowcaseTableDemoDataService::class)->resetDemoData();
    }

    private function forgetTableInstance(string $tableId): void
    {
        $reflection = new ReflectionClass(Table::class);
        $property = $reflection->getProperty('instances');
        $property->setAccessible(true);
        $instance = $property->getValue(app(Table::class));

        if (!is_array($instance) || !isset($instance[$tableId])) {
            return;
        }

        unset($instance[$tableId]);
        $property->setValue(app(Table::class), $instance);
    }

    private function crudTableNameFor(string $dataset): string
    {
        return match ($dataset) {
            'main' => 'showcase_table',
            'media' => 'showcase_table_media',
            'complex' => 'showcase_table_complex',
            default => throw new InvalidArgumentException(sprintf('Unknown showcase table live dataset: %s', $dataset)),
        };
    }

    /**
     * @param string $dataset
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(string $dataset, array $rows): array
    {
        if ($dataset !== 'main') {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $rows[$index] = $this->normalizeMainRow($row);
        }

        return $rows;
    }

    /**
     * 将 showcase 主表中的字符串日期预处理成时间戳，便于 datetime/pretty_time 列真实渲染。
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeMainRow(array $row): array
    {
        foreach (['expire_date', 'publish_time', 'created_at', 'updated_at'] as $field) {
            if (!array_key_exists($field, $row)) {
                continue;
            }

            $value = $row[$field];
            if ($value === null || $value === '' || is_int($value)) {
                continue;
            }

            if (is_numeric($value)) {
                $row[$field] = (int) $value;
                continue;
            }

            $timestamp = strtotime((string) $value);
            if ($timestamp !== false) {
                $row[$field] = $timestamp;
            }
        }

        return $row;
    }

    /**
     * @param array<int, array<int|string, mixed>> $columns
     * @return array<int, array<int|string, mixed>>
     */
    private function resolveRuntimeColumns(array $columns): array
    {
        foreach ($columns as $index => $column) {
            if (!is_array($column)) {
                continue;
            }

            $columns[$index] = $this->resolveRuntimeColumn($column);
        }

        return $columns;
    }

    /**
     * 将可序列化的 showcase 回调预设恢复成真实闭包。
     *
     * @param array<int|string, mixed> $column
     * @return array<int|string, mixed>
     */
    private function resolveRuntimeColumn(array $column): array
    {
        $type = (string) ($column[2] ?? $column['type'] ?? '');
        if ($type !== 'callback') {
            return $column;
        }

        $callback = $column['callback'] ?? ($column[3] ?? null);
        if (!is_array($callback) || !isset($callback[self::CALLBACK_PRESET_KEY])) {
            return $column;
        }

        $resolved = $this->callbackPreset((string) $callback[self::CALLBACK_PRESET_KEY]);
        if (array_key_exists('callback', $column)) {
            $column['callback'] = $resolved;
        } else {
            $column[3] = $resolved;
        }

        return $column;
    }

    /**
     * Showcase 详情页 live demo 内部使用的 callback 预设。
     */
    private function callbackPreset(string $name): \Closure
    {
        return match ($name) {
            'uppercase_file_ext' => static fn($value): string => strtoupper((string) $value),
            'gallery_to_title_rows' => static function ($value): array {
                $items = array_filter(array_map('trim', explode(',', (string) $value)));
                return array_map(static fn(string $item): array => ['title' => basename($item)], $items);
            },
            'score_label' => static fn($value): string => '评分 ' . number_format((float) $value, 2) . ' / 100',
            default => throw new InvalidArgumentException(sprintf('Unknown showcase table callback preset: %s', $name)),
        };
    }
}
