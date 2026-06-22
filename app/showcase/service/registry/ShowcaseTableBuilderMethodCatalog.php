<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 表格构建器方法目录
 */
final class ShowcaseTableBuilderMethodCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'identity_notice',
                'title' => '基础标识与提示',
                'summary' => '控制表格 DOM 标识、提示条和附加 HTML 说明区。',
                'methods' => ['id', 'alert', 'html'],
            ],
            [
                'key' => 'data_crud',
                'title' => '数据与 CRUD',
                'summary' => '控制真实数据源、请求方式、CRUD 表名、主键和 quickEdit 校验。',
                'methods' => ['data', 'url', 'method', 'tableName', 'primaryKey', 'validate'],
            ],
            [
                'key' => 'columns_search',
                'title' => '列定义与搜索',
                'summary' => '控制单列、批量列和搜索区 DSL 配置。',
                'methods' => ['column', 'columns', 'search'],
            ],
            [
                'key' => 'toolbar_interaction',
                'title' => '工具栏与交互',
                'summary' => '控制工具栏、多选列、右侧操作列、分页和表格选项。',
                'methods' => ['toolbar', 'actions', 'checkbox', 'page', 'options', 'tree'],
            ],
            [
                'key' => 'advanced',
                'title' => '扩展与模板变量',
                'summary' => '控制模板变量透传，便于布局模板或扩展片段读取业务上下文。',
                'methods' => ['assign'],
            ],
        ];
    }
}
