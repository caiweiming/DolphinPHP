<?php
declare(strict_types=1);

namespace app\admin\service;

use RuntimeException;

/**
 * 后台商店客户端服务
 */
class StoreClientService
{
    /**
     * 登录远端商店
     * @param string $baseUrl
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $baseUrl, string $email, string $password): array
    {
        $payload = $this->requestJson(
            'POST',
            rtrim(trim($baseUrl), '/') . '/store/api/login',
            [
                'email'    => trim($email),
                'password' => $password,
            ],
        );

        if ((int)($payload['code'] ?? 1) !== 0) {
            throw new RuntimeException((string)($payload['msg'] ?? '商店登录失败'));
        }

        return (array)($payload['data'] ?? []);
    }

    /**
     * 获取远端已购商品列表
     * @param string $baseUrl
     * @param string $token
     * @return array
     */
    public function purchases(string $baseUrl, string $token): array
    {
        $payload = $this->requestJson(
            'GET',
            rtrim(trim($baseUrl), '/') . '/store/api/purchases',
            [],
            $this->buildBearerHeaders($token)
        );

        if ((int)($payload['code'] ?? 1) !== 0) {
            throw new RuntimeException((string)($payload['msg'] ?? '获取已购商品失败'));
        }

        return array_values(array_filter((array)($payload['data'] ?? []), static function (mixed $row): bool {
            return is_array($row) && !empty($row['product_id']) && !empty($row['version_id']);
        }));
    }

    /**
     * 下载远端商品包到本地临时目录
     * @param string $baseUrl
     * @param string $token
     * @param int $productId
     * @param int $versionId
     * @param string $packageName
     * @return array{path:string,filename:string}
     */
    public function downloadPackage(string $baseUrl, string $token, int $productId, int $versionId, string $packageName = 'store-package'): array
    {
        $binary = $this->requestBinary(
            rtrim(trim($baseUrl), '/') . '/store/api/download?' . http_build_query([
                'product_id' => $productId,
                'version_id' => $versionId,
            ]),
            $this->buildBearerHeaders($token)
        );

        $filename = $this->normalizeFilename((string)($binary['filename'] ?? ''), $packageName);
        $directory = runtime_path() . 'store-client' . DIRECTORY_SEPARATOR;
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('创建商店临时目录失败');
        }

        $path = $directory . uniqid('store-package-', true) . '-' . $filename;
        $written = file_put_contents($path, (string)($binary['body'] ?? ''));
        if ($written === false) {
            throw new RuntimeException('写入商店安装包失败');
        }

        return ['path' => $path, 'filename' => $filename];
    }

    /**
     * 发送 JSON 请求
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    protected function requestJson(string $method, string $url, array $data = [], array $headers = []): array
    {
        $response = $this->sendRequest($method, $url, $data, array_merge(['Accept: application/json'], $headers));
        $payload = json_decode($response['body'], true);
        if (!is_array($payload)) {
            throw new RuntimeException('商店响应格式无效');
        }

        return $payload;
    }

    /**
     * 下载二进制响应
     * @param string $url
     * @param array $headers
     * @return array{body:string,filename:string}
     */
    protected function requestBinary(string $url, array $headers = []): array
    {
        $response = $this->sendRequest('GET', $url, [], array_merge(['Accept: application/octet-stream'], $headers));

        return [
            'body'     => $response['body'],
            'filename' => $this->extractFilename((array)$response['headers']),
        ];
    }

    /**
     * 执行 HTTP 请求
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array{body:string,headers:array,status:int}
     */
    private function sendRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('当前 PHP 环境未启用 curl 扩展');
        }

        $method = strtoupper($method);
        if ($method === 'GET' && $data !== []) {
            $glue = str_contains($url, '?') ? '&' : '?';
            $url .= $glue . http_build_query($data);
        }

        $responseHeaders = [];
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('初始化商店请求失败');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $headerLine = trim($headerLine);
                if ($headerLine === '' || !str_contains($headerLine, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $headerLine, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
                return $length;
            },
        ]);

        if ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException($error !== '' ? $error : '商店请求失败');
        }

        $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode >= 400) {
            throw new RuntimeException('商店服务返回异常状态：' . $statusCode);
        }

        return [
            'body'    => (string)$response,
            'headers' => $responseHeaders,
            'status'  => $statusCode,
        ];
    }

    /**
     * 构建 Bearer Token 请求头
     * @param string $token
     * @return array
     */
    private function buildBearerHeaders(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('商店访问令牌不能为空');
        }

        return ['Authorization: Bearer ' . $token];
    }

    /**
     * 规范化下载文件名
     * @param string $filename
     * @param string $packageName
     * @return string
     */
    private function normalizeFilename(string $filename, string $packageName): string
    {
        $filename = trim($filename);
        if ($filename !== '') {
            return basename($filename);
        }

        $packageName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', trim($packageName)) ?: 'store-package';
        return $packageName . '.zip';
    }

    /**
     * 从响应头提取文件名
     * @param array $headers
     * @return string
     */
    private function extractFilename(array $headers): string
    {
        $disposition = (string)($headers['content-disposition'] ?? '');
        if ($disposition === '') {
            return '';
        }

        if (preg_match('/filename\*=UTF-8\'\'([^;]+)/i', $disposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], "\"'"));
        }

        if (preg_match('/filename=([^;]+)/i', $disposition, $matches) === 1) {
            return trim($matches[1], "\"' ");
        }

        return '';
    }
}
