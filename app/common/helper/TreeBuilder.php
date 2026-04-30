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

namespace app\common\helper;

/**
 * 树形结构构建器
 * 用于将一维数组转换为树形结构,支持高度自定义配置
 *
 * @package app\common\helper
 */
class TreeBuilder
{
    /**
     * 原始数据
     * @var array
     */
    protected array $data = [];

    /**
     * 主键字段名
     * @var string
     */
    protected string $idField = 'id';

    /**
     * 父级ID字段名
     * @var string
     */
    protected string $parentField = 'parent_id';

    /**
     * 子级数据字段名
     * @var string
     */
    protected string $childrenField = 'children';

    /**
     * 根节点的父级ID值
     * @var mixed
     */
    protected mixed $rootId = 0;

    /**
     * 是否添加层级字段
     * @var string|null
     */
    protected ?string $levelField = null;

    /**
     * 是否添加路径字段
     * @var string|null
     */
    protected ?string $pathField = null;

    /**
     * 路径分隔符
     * @var string
     */
    protected string $pathSeparator = '/';

    /**
     * 是否添加名称路径字段
     * @var string|null
     */
    protected ?string $namePathField = null;

    /**
     * 名称字段
     * @var string|null
     */
    protected ?string $nameField = null;

    /**
     * 名称路径分隔符
     * @var string
     */
    protected string $namePathSeparator = ' > ';

    /**
     * 排序字段
     * @var string|null
     */
    protected ?string $sortField = null;

    /**
     * 排序方向
     * @var string
     */
    protected string $sortOrder = 'asc';

    /**
     * 构造函数
     * @param array $data 原始数据
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * 静态构造方法
     * @param array $data 原始数据
     * @return static
     */
    public static function make(array $data = []): static
    {
        return new static($data);
    }

    /**
     * 设置主键字段名
     * @param string $field 字段名
     * @return $this
     */
    public function setIdField(string $field): static
    {
        $this->idField = $field;
        return $this;
    }

    /**
     * 设置父级ID字段名
     * @param string $field 字段名
     * @return $this
     */
    public function setParentField(string $field): static
    {
        $this->parentField = $field;
        return $this;
    }

    /**
     * 设置子级数据字段名
     * @param string $field 字段名
     * @return $this
     */
    public function setChildrenField(string $field): static
    {
        $this->childrenField = $field;
        return $this;
    }

    /**
     * 设置根节点的父级ID值
     * @param mixed $rootId 根节点父级ID值
     * @return $this
     */
    public function setRootId(mixed $rootId): static
    {
        $this->rootId = $rootId;
        return $this;
    }

    /**
     * 添加层级字段
     * @param string $field 层级字段名,默认为 'level'
     * @return $this
     */
    public function withLevel(string $field = 'level'): static
    {
        $this->levelField = $field;
        return $this;
    }

    /**
     * 添加ID路径字段
     * @param string $field 路径字段名,默认为 'path'
     * @param string $separator 路径分隔符,默认为 '/'
     * @return $this
     */
    public function withPath(string $field = 'path', string $separator = '/'): static
    {
        $this->pathField     = $field;
        $this->pathSeparator = $separator;
        return $this;
    }

    /**
     * 添加名称路径字段
     * @param string $field 名称路径字段名
     * @param string $nameField 名称字段名
     * @param string $separator 分隔符,默认为 ' > '
     * @return $this
     */
    public function withNamePath(string $field, string $nameField, string $separator = ' > '): static
    {
        $this->namePathField     = $field;
        $this->nameField         = $nameField;
        $this->namePathSeparator = $separator;
        return $this;
    }

    /**
     * 设置排序字段
     * @param string $field 排序字段名
     * @param string $order 排序方向 'asc' 或 'desc'
     * @return $this
     */
    public function sortBy(string $field, string $order = 'asc'): static
    {
        $this->sortField = $field;
        $this->sortOrder = strtolower($order);
        return $this;
    }

    /**
     * 构建树形结构
     * @param mixed|null $parentId 父级ID,默认使用 rootId
     * @param int $level 当前层级
     * @param array $parentPath 父级路径
     * @param array $parentNamePath 父级名称路径
     * @return array
     */
    public function build(mixed $parentId = null, int $level = 1, array $parentPath = [], array $parentNamePath = []): array
    {
        if ($parentId === null) {
            $parentId = $this->rootId;
        }

        $tree = [];

        foreach ($this->data as $item) {
            // 检查是否为当前父级的子项
            if ($this->getFieldValue($item, $this->parentField) == $parentId) {
                $node = $item;

                // 添加层级信息
                if ($this->levelField !== null) {
                    $node[$this->levelField] = $level;
                }

                // 构建ID路径
                $currentPath = array_merge($parentPath, [$this->getFieldValue($item, $this->idField)]);

                // 添加路径信息
                if ($this->pathField !== null) {
                    $node[$this->pathField] = implode($this->pathSeparator, $currentPath);
                }

                // 添加名称路径信息
                if ($this->namePathField !== null && $this->nameField !== null) {
                    $currentNamePath            = array_merge(
                        $parentNamePath,
                        [$this->getFieldValue($item, $this->nameField)]
                    );
                    $node[$this->namePathField] = implode($this->namePathSeparator, $currentNamePath);
                }

                // 递归构建子树
                $children = $this->build(
                    $this->getFieldValue($item, $this->idField),
                    $level + 1,
                    $currentPath,
                    $this->namePathField !== null && $this->nameField !== null
                        ? array_merge($parentNamePath, [$this->getFieldValue($item, $this->nameField)])
                        : []
                );

                // 如果有子节点,添加到当前节点
                if (!empty($children)) {
                    $node[$this->childrenField] = $children;
                }

                $tree[] = $node;
            }
        }

        // 排序
        if ($this->sortField !== null && !empty($tree)) {
            $tree = $this->sortTree($tree);
        }

        return $tree;
    }

    /**
     * 获取扁平化的树形数据(带层级等附加信息,但不构建嵌套结构)
     * @return array
     */
    public function flatten(): array
    {
        $result = [];
        $this->flattenRecursive($this->build(), $result);
        return $result;
    }

    /**
     * 递归扁平化树形数据
     * @param array $tree 树形数据
     * @param array &$result 结果数组
     * @return void
     */
    protected function flattenRecursive(array $tree, array &$result): void
    {
        foreach ($tree as $item) {
            $children = $item[$this->childrenField] ?? [];
            // 移除children字段后添加到结果
            unset($item[$this->childrenField]);
            $result[] = $item;

            // 递归处理子节点
            if (!empty($children)) {
                $this->flattenRecursive($children, $result);
            }
        }
    }

    /**
     * 获取字段值(支持数组和对象)
     * @param mixed $item 数据项
     * @param string $field 字段名
     * @return mixed
     */
    protected function getFieldValue(mixed $item, string $field): mixed
    {
        if (is_array($item)) {
            return $item[$field] ?? null;
        }

        return null;
    }

    /**
     * 排序树形数据
     * @param array $tree 树形数据
     * @return array
     */
    protected function sortTree(array $tree): array
    {
        usort($tree, function ($a, $b) {
            $aValue = $this->getFieldValue($a, $this->sortField);
            $bValue = $this->getFieldValue($b, $this->sortField);

            if ($aValue == $bValue) {
                return 0;
            }

            if ($this->sortOrder === 'desc') {
                return $aValue < $bValue ? 1 : -1;
            }

            return $aValue < $bValue ? -1 : 1;
        });

        return $tree;
    }

    /**
     * 从树形数据中查找节点
     * @param array $tree 树形数据
     * @param mixed $id 节点ID
     * @param bool $returnPath 是否返回路径(包含所有父节点)
     * @param string $idField
     * @param string $childrenField
     * @return array|null
     */
    public static function findNode(array $tree, mixed $id, bool $returnPath = false, string $idField = 'id', string $childrenField = 'children'): ?array
    {
        foreach ($tree as $node) {
            if (($node[$idField] ?? null) == $id) {
                return $returnPath ? [$node] : $node;
            }

            if (!empty($node[$childrenField])) {
                $result = self::findNode($node[$childrenField], $id, $returnPath, $idField, $childrenField);
                if ($result !== null) {
                    return $returnPath ? array_merge([$node], $result) : $result;
                }
            }
        }

        return null;
    }

    /**
     * 获取所有叶子节点(没有子节点的节点)
     * @param array $tree 树形数据
     * @param string $childrenField 子级字段名
     * @return array
     */
    public static function getLeafNodes(array $tree, string $childrenField = 'children'): array
    {
        $leaves = [];

        foreach ($tree as $node) {
            if (empty($node[$childrenField])) {
                $leaves[] = $node;
            } else {
                $leaves = array_merge($leaves, self::getLeafNodes($node[$childrenField], $childrenField));
            }
        }

        return $leaves;
    }

    /**
     * 树形数据转换为一维数组
     * @param array $tree 树形数据
     * @param string $childrenField 子级字段名
     * @return array
     */
    public static function toList(array $tree, string $childrenField = 'children'): array
    {
        $list = [];

        foreach ($tree as $node) {
            $children = $node[$childrenField] ?? [];
            unset($node[$childrenField]);
            $list[] = $node;

            if (!empty($children)) {
                $list = array_merge($list, self::toList($children, $childrenField));
            }
        }

        return $list;
    }

    /**
     * 过滤树形数据(保留符合条件的节点及其父节点)
     * @param array $tree 树形数据
     * @param callable $callback 过滤回调函数
     * @param string $childrenField 子级字段名
     * @return array
     */
    public static function filter(array $tree, callable $callback, string $childrenField = 'children'): array
    {
        $result = [];

        foreach ($tree as $node) {
            $match    = $callback($node);
            $children = $node[$childrenField] ?? [];

            if (!empty($children)) {
                $filteredChildren = self::filter($children, $callback, $childrenField);
                if (!empty($filteredChildren)) {
                    $node[$childrenField] = $filteredChildren;
                    $result[]             = $node;
                } elseif ($match) {
                    unset($node[$childrenField]);
                    $result[] = $node;
                }
            } elseif ($match) {
                $result[] = $node;
            }
        }

        return $result;
    }
}
