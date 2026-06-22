<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * image 列详情页
 */
final class ImageColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/image/Item.php', 'label' => 'image 列实现', 'description' => '处理图片数组化和 lightbox 资源注入。'],
        ];

        $sections = [
            $this->makeSection(
                'single_image',
                '单图缩略图',
                '最常见的场景是封面图、头像等单图字段。',
                $this->liveMedia([
                    ['asset_name', '素材名', '', [], ['minWidth' => 160]],
                    ['cover', '封面图', 'image', [], ['width' => 120]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'image'],
                    ['name' => '字段值', 'value' => '单个图片 ID 或 URL'],
                ],
                [
                    'image 列会把单个 ID 或 URL 统一处理成可展示的图片数组。',
                ],
                <<<'CODE'
['cover', '封面图', 'image', [], ['width' => 90]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['name', '商品'],
        ['cover', '封面图', 'image', [], ['width' => 90]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'multiple_image',
                '多图字段',
                '当字段里存的是逗号分隔图片列表时，image 列会自动拆分。',
                $this->liveMedia([
                    ['asset_name', '相册名', '', [], ['minWidth' => 160]],
                    ['gallery', '相册', 'image', [], ['minWidth' => 140]],
                ], [
                    '_where_in' => ['id', [1, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => '字段值', 'value' => '1,2,3 或 url1,url2,url3'],
                    ['name' => '处理行为', 'value' => 'explode 后转数组'],
                ],
                [
                    '列表里存多图时，后端可直接返回逗号字符串，image 列会拆分后展示。',
                ],
                <<<'CODE'
['gallery', '相册', 'image', [], ['minWidth' => 120]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['album_name', '相册'],
        ['gallery', '相册', 'image', [], ['minWidth' => 120]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'resource_hint',
                '资源依赖提示',
                'image 列会自动引入 `fslightbox`，适合点击查看大图。',
                $this->infoPreview('资源注入', [
                    'image 列会自动引入 fslightbox，用于点击缩略图后查看大图。',
                    '同页已经存在 image 或 preview 列时，不需要页面手动重复注入这份依赖。',
                ]),
                [
                    ['name' => '资源', 'value' => '__THEME_LIBS__/fslightbox/index.js'],
                    ['name' => '表格 class', 'value' => 'dp-table-fix-cell'],
                ],
                [
                    '该列会自动注入 lightbox 资源，不需要页面手动重复引入。',
                ],
                <<<'CODE'
['banner', 'Banner', 'image']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['banner', 'Banner', 'image'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'business_snippet',
                '业务列片段',
                '通常会和名称、分类、操作列一起组合出现。',
                $this->liveMedia([
                    ['id', 'ID', '', [], ['width' => 70]],
                    ['asset_name', '商品名', '', [], ['minWidth' => 160]],
                    ['cover', '主图', 'image', [], ['width' => 96]],
                    ['status', '状态', 'status', ['禁用', '启用:green', '归档:red'], ['width' => 100]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '组合列', 'value' => 'image + status + actions'],
                    ['name' => '建议宽度', 'value' => '90 ~ 120'],
                ],
                [
                    '图片列不要过宽，否则会挤压文本列的阅读空间。',
                ],
                <<<'CODE'
[
    ['id', 'ID', '', [], ['width' => 70]],
    ['name', '商品名'],
    ['cover', '主图', 'image', [], ['width' => 96]],
    ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['id', 'ID', '', [], ['width' => 70]],
        ['name', '商品名'],
        ['cover', '主图', 'image', [], ['width' => 96]],
        ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'image 列用于展示单图、多图缩略图和基础图片列样式，适合商品主图、头像、Banner 等列表场景。',
                'scenarios' => [
                    '商品主图、头像、封面图等单图列表展示',
                    '相册、图集等逗号分隔多图字段的后台列表',
                ],
                'capabilities' => ['单图展示', '多图拆分', '大图预览依赖', '组合列建议'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['cover', '封面图', 'image', [], ['width' => 90]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['cover', '封面图', 'image', [], ['width' => 90]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `image`。'],
                        ['name' => 'field', 'summary' => '支持单图 ID/URL，也支持逗号分隔多图。'],
                        ['name' => 'cols.width', 'summary' => '建议 90 到 120。'],
                    ],
                ],
                [
                    'title' => '行为说明',
                    'items' => [
                        ['name' => '自动数组化', 'summary' => '会把逗号字符串拆成数组并转真实路径。'],
                        ['name' => '资源注入', 'summary' => '会自动引入 fslightbox 资源。'],
                    ],
                ],
            ],
            $sections,
            [
                ['key' => 'media.preview', 'title' => 'preview 预览列', 'status' => 'available'],
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
            ]
        );
    }
}
