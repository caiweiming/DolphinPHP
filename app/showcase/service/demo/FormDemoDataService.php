<?php
declare(strict_types=1);

namespace app\showcase\service\demo;

/**
 * 表单示例假数据服务
 *
 * 为 Showcase 示例提供稳定的选项与联动树数据。
 */
final class FormDemoDataService
{
    /**
     * 按名称返回示例数据集。
     *
     * @param string $name 数据集名称
     * @return array<int, array<string, mixed>>|null
     */
    public function dataset(string $name, array $params = []): ?array
    {
        return match ($name) {
            'departments' => $this->departments(),
            'select2_departments' => $this->select2Departments(),
            'roles' => $this->roles(),
            'linkage' => $this->linkageOptions($params),
            'linkages' => $this->linkagesOptions($params),
            default => null,
        };
    }

    /**
     * 返回部门选项。
     *
     * @return array<int, array<string, string>>
     */
    public function departments(): array
    {
        return [
            ['value' => 'product', 'label' => '产品中心'],
            ['value' => 'engineering', 'label' => '研发中心'],
            ['value' => 'operations', 'label' => '运营中心'],
            ['value' => 'finance', 'label' => '财务部'],
        ];
    }

    /**
     * 返回 Select2 远程搜索选项。
     *
     * Select2 自定义 ajax URL 场景要求后端返回 id/text 结构。
     *
     * @return array<int, array<string, string>>
     */
    public function select2Departments(): array
    {
        return [
            ['id' => 'product', 'text' => '产品中心'],
            ['id' => 'engineering', 'text' => '研发中心'],
            ['id' => 'operations', 'text' => '运营中心'],
            ['id' => 'finance', 'text' => '财务部'],
        ];
    }

    /**
     * 返回角色选项。
     *
     * @return array<int, array<string, string>>
     */
    public function roles(): array
    {
        return [
            ['value' => 'manager', 'label' => '部门负责人'],
            ['value' => 'leader', 'label' => '项目负责人'],
            ['value' => 'member', 'label' => '执行成员'],
            ['value' => 'guest', 'label' => '协作访客'],
        ];
    }

    /**
     * 返回联动树数据。
     *
     * @return array<int, array<string, mixed>>
     */
    public function linkageTree(): array
    {
        return [
            [
                'value' => 'north',
                'label' => '华北大区',
                'children' => [
                    [
                        'value' => 'north-bj',
                        'label' => '北京分部',
                        'children' => [
                            ['value' => 'north-bj-cy', 'label' => '朝阳团队', 'children' => []],
                            ['value' => 'north-bj-hd', 'label' => '海淀团队', 'children' => []],
                        ],
                    ],
                    [
                        'value' => 'north-tj',
                        'label' => '天津分部',
                        'children' => [
                            ['value' => 'north-tj-bh', 'label' => '滨海团队', 'children' => []],
                        ],
                    ],
                ],
            ],
            [
                'value' => 'east',
                'label' => '华东大区',
                'children' => [
                    [
                        'value' => 'east-sh',
                        'label' => '上海分部',
                        'children' => [
                            ['value' => 'east-sh-pd', 'label' => '浦东团队', 'children' => []],
                            ['value' => 'east-sh-mh', 'label' => '闵行团队', 'children' => []],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 返回 linkage 组件当前级别选项。
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, string>>
     */
    public function linkageOptions(array $params = []): array
    {
        $tree = $this->linkageTree();
        $keys = ['region', 'site', 'team'];

        if (($params['scene'] ?? null) === 'north') {
            $params['region'] = 'north';
        } elseif (($params['scene'] ?? null) === 'east') {
            $params['region'] = 'east';
        }

        $matchedLevel = -1;
        $children = $tree;

        foreach ($keys as $index => $key) {
            $selected = isset($params[$key]) ? trim((string) $params[$key]) : '';
            if ($selected === '') {
                $matchedLevel = $index;
                break;
            }

            $matched = $this->findLinkageNode($children, $selected);
            if ($matched === null) {
                return [];
            }

            $children = is_array($matched['children'] ?? null) ? $matched['children'] : [];
        }

        if ($matchedLevel === -1) {
            return [];
        }

        return array_values(array_map(static function (array $node): array {
            return [
                'key' => (string) ($node['value'] ?? ''),
                'value' => (string) ($node['label'] ?? ''),
            ];
        }, array_filter($children, static function (mixed $node): bool {
            return is_array($node) && isset($node['value'], $node['label']);
        })));
    }

    /**
     * 在当前层级节点中查找指定值。
     *
     * @param array<int, array<string, mixed>> $nodes
     * @param string $value
     * @return array<string, mixed>|null
     */
    private function findLinkageNode(array $nodes, string $value): ?array
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if ((string) ($node['value'] ?? '') === $value) {
                return $node;
            }
        }

        return null;
    }

    /**
     * 返回 linkages 组件当前级别选项。
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, string>>
     */
    public function linkagesOptions(array $params = []): array
    {
        $token = trim((string)($params['token'] ?? ''));
        if ($token === '') {
            return [];
        }

        $config = session($token);
        if (!is_array($config)) {
            return [];
        }

        $table = trim((string)($config['table'] ?? ''));
        if ($table === '') {
            return [];
        }

        $rows = $this->linkagesRows($table);
        if ($rows === []) {
            return [];
        }

        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $idField = (string)($fields['id'] ?? 'id');
        $nameField = (string)($fields['name'] ?? 'name');
        $pidField = (string)($fields['pid'] ?? 'pid');
        $level = max(1, (int)($params['level'] ?? 1));
        $parent = $params['parent'] ?? null;
        $rootPid = $config['root_pid'] ?? 0;

        $filtered = array_values(array_filter($rows, function (array $row) use ($pidField, $level, $parent, $rootPid): bool {
            $pidValue = $row[$pidField] ?? null;

            if ($level === 1) {
                return (string)$pidValue === (string)$rootPid;
            }

            if ($parent === null || $parent === '') {
                return false;
            }

            return (string)$pidValue === (string)$parent;
        }));

        $filtered = $this->applyLinkagesDemoFilters($filtered, $config['filters'] ?? []);

        return array_values(array_map(static function (array $row) use ($idField, $nameField): array {
            return [
                'key' => (string)($row[$idField] ?? ''),
                'value' => (string)($row[$nameField] ?? ''),
            ];
        }, $filtered));
    }

    /**
     * 返回 linkages 演示树表数据。
     *
     * @param string $table
     * @return array<int, array<string, mixed>>
     */
    private function linkagesRows(string $table): array
    {
        return match ($table) {
            'region' => [
                ['id' => 'north', 'name' => '华北大区', 'pid' => '0'],
                ['id' => 'east', 'name' => '华东大区', 'pid' => '0'],
                ['id' => 'north-bj', 'name' => '北京分部', 'pid' => 'north'],
                ['id' => 'north-tj', 'name' => '天津分部', 'pid' => 'north'],
                ['id' => 'east-sh', 'name' => '上海分部', 'pid' => 'east'],
                ['id' => 'north-bj-cy', 'name' => '朝阳团队', 'pid' => 'north-bj'],
                ['id' => 'north-bj-hd', 'name' => '海淀团队', 'pid' => 'north-bj'],
                ['id' => 'north-tj-bh', 'name' => '滨海团队', 'pid' => 'north-tj'],
                ['id' => 'east-sh-pd', 'name' => '浦东团队', 'pid' => 'east-sh'],
                ['id' => 'east-sh-mh', 'name' => '闵行团队', 'pid' => 'east-sh'],
            ],
            'goods_category' => [
                ['id' => '10', 'name' => '数码电器', 'pid' => '0', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '20', 'name' => '家居生活', 'pid' => '0', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '108', 'name' => '手机通讯', 'pid' => '10', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '118', 'name' => '电脑办公', 'pid' => '10', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '208', 'name' => '厨具用品', 'pid' => '20', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '218', 'name' => '家纺软饰', 'pid' => '20', 'status' => 0, 'scene' => 'archive'],
                ['id' => '1086', 'name' => '5G 手机', 'pid' => '108', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '1088', 'name' => '折叠屏手机', 'pid' => '108', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '1186', 'name' => '轻薄本', 'pid' => '118', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '2086', 'name' => '锅具套装', 'pid' => '208', 'status' => 1, 'scene' => 'showcase'],
                ['id' => '2186', 'name' => '北欧地毯', 'pid' => '218', 'status' => 0, 'scene' => 'archive'],
            ],
            'department_tree' => [
                ['dept_id' => 'bg', 'dept_name' => '事业群 A', 'parent_id' => 'root'],
                ['dept_id' => 'cg', 'dept_name' => '事业群 B', 'parent_id' => 'root'],
                ['dept_id' => 'bg-rd', 'dept_name' => '研发部', 'parent_id' => 'bg'],
                ['dept_id' => 'bg-op', 'dept_name' => '运营部', 'parent_id' => 'bg'],
                ['dept_id' => 'cg-mkt', 'dept_name' => '市场部', 'parent_id' => 'cg'],
                ['dept_id' => 'bg-rd-api', 'dept_name' => '接口组', 'parent_id' => 'bg-rd'],
                ['dept_id' => 'bg-rd-web', 'dept_name' => '前端组', 'parent_id' => 'bg-rd'],
                ['dept_id' => 'bg-op-cs', 'dept_name' => '客服组', 'parent_id' => 'bg-op'],
                ['dept_id' => 'cg-mkt-brand', 'dept_name' => '品牌组', 'parent_id' => 'cg-mkt'],
            ],
            'tenant_region' => [
                ['id' => 'tenant-north', 'name' => '租户华北', 'pid' => '0'],
                ['id' => 'tenant-east', 'name' => '租户华东', 'pid' => '0'],
                ['id' => 'tenant-north-bj', 'name' => '租户北京', 'pid' => 'tenant-north'],
                ['id' => 'tenant-east-sh', 'name' => '租户上海', 'pid' => 'tenant-east'],
                ['id' => 'tenant-north-bj-a', 'name' => '租户北京一部', 'pid' => 'tenant-north-bj'],
                ['id' => 'tenant-east-sh-a', 'name' => '租户上海一部', 'pid' => 'tenant-east-sh'],
            ],
            default => [],
        };
    }

    /**
     * 对 linkages 演示数据应用简单过滤。
     *
     * @param array<int, array<string, mixed>> $rows
     * @param mixed $filters
     * @return array<int, array<string, mixed>>
     */
    private function applyLinkagesDemoFilters(array $rows, mixed $filters): array
    {
        if (!is_array($filters) || $filters === []) {
            return $rows;
        }

        $isAssoc = !array_is_list($filters);
        if (!$isAssoc) {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($filters): bool {
            foreach ($filters as $field => $expected) {
                if (!array_key_exists((string)$field, $row)) {
                    return false;
                }

                if ((string)$row[(string)$field] !== (string)$expected) {
                    return false;
                }
            }

            return true;
        }));
    }
}
