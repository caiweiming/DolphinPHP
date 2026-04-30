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

namespace app\common\render\table\preview;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Exception;

/**
 * 附件预览列
 */
class Item extends TableItem
{
    /**
     * 处理列配置
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        $table->pushVar('dp_table_class', 'dp-table-fix-cell');
        $table->addJsUrl('__THEME_LIBS__/fslightbox/index.js');
        $table->addExtraJs($this->createTemplet($column, [
            'options' => $this->normalizeOptions($column),
        ]));

        return $column;
    }

    /**
     * 处理列值
     * @param mixed $data
     * @param array $column
     * @param array|object $originalData
     * @return mixed
     */
    public function handleValue(mixed $data, array $column = [], array|object $originalData = []): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $options     = $this->normalizeOptions($column);
        $ext         = strtolower((string)$this->readFieldValue($data, $originalData, $options['ext']));
        $mime        = strtolower((string)$this->readFieldValue($data, $originalData, $options['mime']));
        $url         = (string)$this->readFieldValue($data, $originalData, $options['url']);
        $previewUrl  = (string)$this->readFieldValue($data, $originalData, $options['preview_url']);
        $downloadUrl = (string)$this->readFieldValue($data, $originalData, $options['download_url']);
        $name        = (string)$this->readFieldValue($data, $originalData, $options['name']);

        $previewUrl  = $previewUrl !== '' ? $previewUrl : $url;
        $downloadUrl = $downloadUrl !== '' ? $downloadUrl : $url;

        $data['preview_url']    = $previewUrl;
        $data['download_url']   = $downloadUrl;
        $data                   = $this->syncRawDataLinks($data, $previewUrl, $downloadUrl);
        $data[$column['field']] = $this->buildPreviewMeta($ext, $mime, $previewUrl, $downloadUrl, $name);
        return $data;
    }

    /**
     * 归一化选项
     * @param array $column
     * @return array
     */
    private function normalizeOptions(array $column): array
    {
        $options = $column['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        return [
            'url'          => (string)($options['url'] ?? 'url'),
            'ext'          => (string)($options['ext'] ?? 'ext'),
            'mime'         => (string)($options['mime'] ?? 'mime'),
            'preview_url'  => (string)($options['preview_url'] ?? 'preview_url'),
            'download_url' => (string)($options['download_url'] ?? 'download_url'),
            'name'         => (string)($options['name'] ?? 'name'),
        ];
    }

    /**
     * 读取字段值
     * @param array $data
     * @param array|object $originalData
     * @param string $field
     * @return mixed
     */
    private function readFieldValue(array $data, array|object $originalData, string $field): mixed
    {
        if ($field === '') {
            return null;
        }

        if (array_key_exists($field, $data)) {
            return $data[$field];
        }

        if (is_array($originalData) && array_key_exists($field, $originalData)) {
            return $originalData[$field];
        }

        if (is_object($originalData)) {
            if (method_exists($originalData, 'getAttr')) {
                return $originalData->getAttr($field);
            }

            if (isset($originalData->{$field})) {
                return $originalData->{$field};
            }
        }

        return null;
    }

    /**
     * 同步原始行数据中的预览/下载地址，供 actions 列直接使用
     * @param array $data
     * @param string $previewUrl
     * @param string $downloadUrl
     * @return array
     */
    private function syncRawDataLinks(array $data, string $previewUrl, string $downloadUrl): array
    {
        if (!array_key_exists('_data', $data)) {
            return $data;
        }

        if (is_array($data['_data'])) {
            $data['_data']['preview_url']  = $previewUrl;
            $data['_data']['download_url'] = $downloadUrl;
            return $data;
        }

        if (is_object($data['_data'])) {
            if (method_exists($data['_data'], 'setAttr')) {
                $data['_data']->setAttr('preview_url', $previewUrl);
                $data['_data']->setAttr('download_url', $downloadUrl);
                return $data;
            }

            $data['_data']->preview_url  = $previewUrl;
            $data['_data']->download_url = $downloadUrl;
        }

        return $data;
    }

    /**
     * 构建预览元信息
     * @param string $ext
     * @param string $mime
     * @param string $previewUrl
     * @param string $downloadUrl
     * @param string $name
     * @return array
     */
    private function buildPreviewMeta(
        string $ext,
        string $mime,
        string $previewUrl,
        string $downloadUrl,
        string $name
    ): array
    {
        $label = strtoupper(substr($ext !== '' ? $ext : 'FILE', 0, 6));
        $meta  = [
            'kind'        => 'file',
            'thumbnail'   => '',
            'icon'        => 'ti ti-file',
            'label'       => $label,
            'tone'        => 'slate',
            'action_url'  => $downloadUrl !== '' ? $downloadUrl : $previewUrl,
            'action_text' => $downloadUrl !== '' || $previewUrl !== '' ? '下载' : '',
            'title'       => $name,
        ];

        if ($this->isImage($ext, $mime) && $previewUrl !== '') {
            return [
                ...$meta,
                'kind'        => 'image',
                'thumbnail'   => $previewUrl,
                'icon'        => 'ti ti-photo',
                'label'       => 'IMG',
                'tone'        => 'sky',
                'action_url'  => $previewUrl,
                'action_text' => '查看',
            ];
        }

        if ($this->isPdf($ext, $mime)) {
            return [
                ...$meta,
                'icon'        => 'ti ti-file-type-pdf',
                'label'       => 'PDF',
                'tone'        => 'danger',
                'action_url'  => $previewUrl !== '' ? $previewUrl : $downloadUrl,
                'action_text' => $previewUrl !== '' || $downloadUrl !== '' ? '查看' : '',
            ];
        }

        if ($this->isVideo($ext, $mime)) {
            return [
                ...$meta,
                'icon'        => 'ti ti-video',
                'label'       => $label !== '' ? $label : 'VIDEO',
                'tone'        => 'violet',
                'action_url'  => '',
                'action_text' => '',
            ];
        }

        if ($this->isAudio($ext, $mime)) {
            return [
                ...$meta,
                'icon'        => 'ti ti-music',
                'label'       => $label !== '' ? $label : 'AUDIO',
                'tone'        => 'emerald',
                'action_url'  => '',
                'action_text' => '',
            ];
        }

        if ($this->isArchive($ext)) {
            return [
                ...$meta,
                'icon'  => 'ti ti-zip',
                'label' => $label,
                'tone'  => 'orange',
            ];
        }

        if ($this->isOffice($ext)) {
            return [
                ...$meta,
                'icon'  => 'ti ti-file-description',
                'label' => $label,
                'tone'  => 'blue',
            ];
        }

        return $meta;
    }

    /**
     * 是否图片
     * @param string $ext
     * @param string $mime
     * @return bool
     */
    private function isImage(string $ext, string $mime): bool
    {
        return in_array($ext, array_map('strtolower', (array)config('upload.allowed_ext.image', [])), true)
            || str_starts_with($mime, 'image/');
    }

    /**
     * 是否 PDF
     * @param string $ext
     * @param string $mime
     * @return bool
     */
    private function isPdf(string $ext, string $mime): bool
    {
        return $ext === 'pdf' || $mime === 'application/pdf';
    }

    /**
     * 是否视频
     * @param string $ext
     * @param string $mime
     * @return bool
     */
    private function isVideo(string $ext, string $mime): bool
    {
        return in_array($ext, array_map('strtolower', (array)config('upload.allowed_ext.video', [])), true)
            || str_starts_with($mime, 'video/');
    }

    /**
     * 是否音频
     * @param string $ext
     * @param string $mime
     * @return bool
     */
    private function isAudio(string $ext, string $mime): bool
    {
        return in_array($ext, array_map('strtolower', (array)config('upload.allowed_ext.audio', [])), true)
            || str_starts_with($mime, 'audio/');
    }

    /**
     * 是否压缩包
     * @param string $ext
     * @return bool
     */
    private function isArchive(string $ext): bool
    {
        return in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true);
    }

    /**
     * 是否 Office/文档文件
     * @param string $ext
     * @return bool
     */
    private function isOffice(string $ext): bool
    {
        return in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md'], true);
    }
}
