<?php
declare(strict_types=1);

namespace app\showcase\service\demo;

use InvalidArgumentException;

/**
 * 表单示例提交回显服务
 *
 * 对 Showcase 示例提交做最小校验并返回回显结果。
 */
final class FormDemoSubmitService
{
    /**
     * 支持的示例 Key。
     *
     * @var array<int, string>
     */
    private const SUPPORTED_EXAMPLES = [
        'basic.text',
        'component.preview',
        'choice.linkage',
        'datetime.range',
        'media.upload',
        'rich.structure',
    ];

    /**
     * 处理示例提交。
     *
     * @param string $exampleKey 示例 Key
     * @param array<string, mixed> $payload 提交数据
     * @return array<string, mixed>
     */
    public function handle(string $exampleKey, array $payload): array
    {
        if (!in_array($exampleKey, self::SUPPORTED_EXAMPLES, true)) {
            throw new InvalidArgumentException(sprintf('未知示例: %s', $exampleKey));
        }

        $title = trim((string) ($payload['demo_title'] ?? ''));
        $payload['demo_title'] = $title;

        if ($title == '') {
            return $this->response(
                0,
                '标题不能为空',
                $exampleKey,
                'demo_title',
                $payload
            );
        }

        return $this->response(1, '提交成功', $exampleKey, '', $payload);
    }

    /**
     * 构建统一返回结果。
     *
     * @param int $code 状态码
     * @param string $msg 提示消息
     * @param string $exampleKey 示例 Key
     * @param string|null $field 校验字段
     * @param array<string, mixed> $payload 提交数据
     * @return array<string, mixed>
     */
    private function response(int $code, string $msg, string $exampleKey, ?string $field, array $payload): array
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => [
                'example_key' => $exampleKey,
                'field' => $field,
                'payload' => $payload,
            ],
        ];
    }
}
