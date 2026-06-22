<?php
declare(strict_types=1);

namespace app\cms\service;

/**
 * CMS 输入安全服务
 *
 * 统一负责后台提交数据的字段白名单收敛与基础文本净化，
 * 避免示例应用把原始请求数据直接批量写入模型。
 */
class CmsInputSecurityService
{
    /**
     * 净化文章提交数据
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitizeArticlePayload(array $data): array
    {
        return [
            'category_id'    => max(0, (int)($data['category_id'] ?? 0)),
            'title'          => $this->sanitizePlainText($data['title'] ?? '', 150),
            'slug'           => $this->sanitizePlainText($data['slug'] ?? '', 150),
            'summary'        => $this->sanitizePlainText($data['summary'] ?? ''),
            'content'        => $this->sanitizePlainText($data['content'] ?? ''),
            'cover'          => trim((string)($data['cover'] ?? '')),
            'source'         => $this->sanitizePlainText($data['source'] ?? '', 120),
            'status'         => isset($data['status']) ? 1 : 0,
            'sort'           => (int)($data['sort'] ?? 0),
            'published_time' => trim((string)($data['published_time'] ?? '')),
        ];
    }

    /**
     * 净化分类提交数据
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitizeCategoryPayload(array $data): array
    {
        return [
            'name'   => $this->sanitizePlainText($data['name'] ?? '', 120),
            'slug'   => $this->sanitizePlainText($data['slug'] ?? '', 120),
            'status' => isset($data['status']) ? 1 : 0,
            'sort'   => (int)($data['sort'] ?? 0),
            'remark' => $this->sanitizePlainText($data['remark'] ?? '', 255),
        ];
    }

    /**
     * 纯文本净化
     * @param mixed $value
     * @param int $maxLength
     * @return string
     */
    private function sanitizePlainText(mixed $value, int $maxLength = 0): string
    {
        $text = trim(strip_tags((string)$value));
        if ($maxLength > 0) {
            return mb_substr($text, 0, $maxLength);
        }

        return $text;
    }
}
