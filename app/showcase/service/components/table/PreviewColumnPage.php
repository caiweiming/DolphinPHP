<?php
declare(strict_types=1);

namespace app\showcase\service\components\table;

/**
 * preview 列详情页
 */
final class PreviewColumnPage extends AbstractTableColumnPage
{
    /**
     * @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function build(array $component): array
    {
        $sourceRefs = [
            ['path' => 'app/common/render/table/preview/Item.php', 'label' => 'preview 列实现', 'description' => '统一处理预览元数据和地址回填。'],
        ];

        $sections = [
            $this->makeSection(
                'basic_preview',
                '最小预览列',
                '最小用法只需要声明 `preview` 列，并保证数据里至少有 `url`。',
                $this->liveMedia([
                    ['name', '文件名', '', [], ['minWidth' => 180]],
                    ['preview', '预览', 'preview', [], ['width' => 80]],
                ], [
                    '_where_in' => ['id', [1, 2]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 2,
                ]),
                [
                    ['name' => 'type', 'value' => 'preview'],
                    ['name' => '默认字段', 'value' => 'url / ext / mime / name'],
                ],
                [
                    '如果未提供 preview_url 和 download_url，组件会回退使用 url。',
                ],
                <<<'CODE'
['preview', '预览', 'preview', [], ['width' => 64]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['preview', '预览', 'preview', [], ['width' => 64]],
        ['name', '文件名'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'field_mapping',
                '自定义字段映射',
                '如果你的数据字段不是默认命名，可通过 options 显式映射。',
                $this->liveMedia([
                    ['asset_name', '素材名', '', [], ['minWidth' => 180]],
                    ['cover', '预览', 'preview', [
                        'url' => 'file_url',
                        'preview_url' => 'file_preview_url',
                        'download_url' => 'file_download_url',
                        'ext' => 'file_ext',
                        'mime' => 'file_mime',
                        'name' => 'file_name',
                    ], ['width' => 80]],
                ], [
                    '_where_in' => ['id', [2]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => 'options.url', 'value' => 'file_url'],
                    ['name' => 'options.preview_url', 'value' => 'file_preview_url'],
                ],
                [
                    '字段命名不一致时，不要改数据库字段，直接用 options 做映射即可。',
                ],
                <<<'CODE'
[
    'url' => 'file_url',
    'preview_url' => 'file_preview_url',
    'download_url' => 'file_download_url',
    'ext' => 'file_ext',
    'mime' => 'file_mime',
    'name' => 'file_name',
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['cover', '预览', 'preview', [
            'url' => 'file_url',
            'preview_url' => 'file_preview_url',
            'download_url' => 'file_download_url',
            'ext' => 'file_ext',
            'mime' => 'file_mime',
            'name' => 'file_name',
        ], ['width' => 64]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'action_linkage',
                '与操作列联动',
                'preview 列会把补齐后的 `preview_url`、`download_url` 写回当前行数据，便于 actions 直接复用。',
                $this->liveMedia([
                    ['file_name', '文件名', '', [], ['minWidth' => 180]],
                    ['preview', '预览', 'preview', [
                        'url' => 'file_url',
                        'preview_url' => 'file_preview_url',
                        'download_url' => 'file_download_url',
                        'ext' => 'file_ext',
                        'mime' => 'file_mime',
                        'name' => 'file_name',
                    ], ['width' => 80]],
                    ['right_button', '操作', 'actions', [
                        [
                            'title' => '查看',
                            'url' => '__preview_url__',
                            'target' => '_blank',
                            'auth' => '',
                        ],
                        [
                            'title' => '下载',
                            'url' => '__download_url__',
                            'target' => '_blank',
                            'auth' => '',
                        ],
                    ], ['width' => 160]],
                ], [
                    '_where_in' => ['id', [2]],
                    '_limit' => 1,
                ]),
                [
                    ['name' => '联动占位符', 'value' => '__preview_url__ / __download_url__'],
                    ['name' => '前提', 'value' => 'preview 列先于 actions 列参与处理'],
                ],
                [
                    '这是 preview 列最实用的能力之一，可以减少右侧按钮重复拼接链接。',
                ],
                <<<'CODE'
[
    ['preview', '预览', 'preview', [], ['width' => 64]],
    ['right_button', '操作', 'actions', [
        [
            'title' => '查看',
            'url' => '__preview_url__',
            'target' => '_blank',
        ],
        [
            'title' => '下载',
            'url' => '__download_url__',
            'target' => '_blank',
        ],
    ], ['width' => 180]],
]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['preview', '预览', 'preview', [], ['width' => 64]],
        ['right_button', '操作', 'actions', [
            [
                'title' => '查看',
                'url' => '__preview_url__',
                'target' => '_blank',
            ],
            [
                'title' => '下载',
                'url' => '__download_url__',
                'target' => '_blank',
            ],
        ], ['width' => 180]],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'file_kind',
                '按文件类型渲染',
                '图片显示缩略图，PDF/视频/音频/压缩包等显示稳定图标。',
                $this->liveMedia([
                    ['file_name', '文件名', '', [], ['minWidth' => 180]],
                    ['preview', '预览', 'preview', [], ['width' => 80]],
                ], [
                    '_where_in' => ['id', [1, 2, 3]],
                    '_order' => ['id', 'asc'],
                    '_limit' => 3,
                ]),
                [
                    ['name' => '图片', 'value' => 'thumbnail + 查看'],
                    ['name' => '视频/音频', 'value' => '图标，不内嵌播放器'],
                ],
                [
                    'preview 列优先保证列表页稳定性和性能，不在列表内嵌真实播放器。',
                ],
                <<<'CODE'
['preview', '预览', 'preview']
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['preview', '预览', 'preview'],
    ]);
CODE,
                $sourceRefs
            ),
            $this->makeSection(
                'resource_boundary',
                '资源依赖与性能边界',
                '该列会自动注入 `fslightbox`，但不负责生成私有文件的签名地址。',
                $this->infoPreview('使用边界', [
                    '私有存储文件应在查询层提前生成可访问地址，preview 列不会代替你做签名 URL。',
                    '大文件列表页只适合提供预览入口，不建议在单元格内承载重型预览能力。',
                ]),
                [
                    ['name' => '资源', 'value' => '__THEME_LIBS__/fslightbox/index.js'],
                    ['name' => '边界', 'value' => '不处理签名 URL 生成'],
                ],
                [
                    '如果文件来自私有云存储，应由查询层提前生成正确地址，不要完全依赖 url 回退。',
                ],
                <<<'CODE'
['preview', '预览', 'preview', [], ['width' => 64]]
CODE,
                <<<'CODE'
$this->table
    ->columns([
        ['preview', '预览', 'preview', [], ['width' => 64]],
    ]);
CODE,
                $sourceRefs
            ),
        ];

        return $this->makePage(
            $component,
            [
                'summary' => 'preview 列用于在列表页中对附件做统一预览展示，适合附件管理、素材中心和混合文件列表场景。',
                'scenarios' => [
                    '附件管理、素材中心等需要统一预览入口的后台列表',
                    '图片、PDF、视频、压缩包等混合文件共存的列表页',
                ],
                'capabilities' => ['最小预览列', '字段映射', '和 actions 联动', '文件类型判断', '性能边界'],
                'quick_start' => [
                    'array_code' => <<<'CODE'
[
    ['preview', '预览', 'preview', [], ['width' => 64]],
]
CODE,
                    'table_code' => <<<'CODE'
$this->table
    ->columns([
        ['preview', '预览', 'preview', [], ['width' => 64]],
    ]);
CODE,
                ],
            ],
            [
                [
                    'title' => '基础参数',
                    'items' => [
                        ['name' => 'type', 'summary' => '固定写 `preview`。'],
                        ['name' => 'options.url', 'summary' => '基础地址字段，默认为 `url`。'],
                        ['name' => 'options.preview_url / download_url', 'summary' => '预览地址和下载地址字段。'],
                        ['name' => 'options.ext / mime / name', 'summary' => '文件类型判断和标题展示依赖字段。'],
                    ],
                ],
                [
                    'title' => '能力边界',
                    'items' => [
                        ['name' => '自动回填', 'summary' => '会把补齐后的 preview_url、download_url 写回当前行。'],
                        ['name' => '真实预览边界', 'summary' => '视频、音频等仅显示图标，不嵌入播放器。'],
                    ],
                ],
            ],
            $sections,
            // makePage() 会统一生成 sidebar_source_refs 和 source_refs 供详情页展示源码入口
            [
                ['key' => 'interactive.actions', 'title' => 'actions 操作列', 'status' => 'available'],
                ['key' => 'basic.image', 'title' => 'image 图片缩略图', 'status' => 'available'],
                ['key' => 'state.status', 'title' => 'status 状态标签', 'status' => 'available'],
            ]
        );
    }
}
