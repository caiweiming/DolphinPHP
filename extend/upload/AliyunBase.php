<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace upload;

use app\common\interface\UploadDriver;
use think\response\Json;

/**
 * 阿里云上传驱动抽象基类
 */
abstract class AliyunBase extends Common implements UploadDriver
{
    /**
     * 获取驱动名称
     * @return string
     */
    protected function getDriverName(): string
    {
        return 'aliyun';
    }

    /**
     * 获取默认配置
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            // Bucket空间名称
            'bucket'     => '',
            // Bucket所在地域
            'region'     => '',
            // 地域节点(用于上传资源)
            'endpoint'   => '',
            // Bucket域名(用于访问资源)
            'domain'     => '',
            // AccessKey ID
            'access_key' => '',
            // AccessKey Secret
            'secret_key' => '',
            // 上传目录(不能以/开头)
            'dir'        => '',
            // 凭证过期时间, 单位秒
            'expire'     => 3600
        ];
    }

    /**
     * 阿里云通用的处理方法
     * @param array $item
     * @return array
     */
    public function handle(array $item = []): array
    {
        $item        = $this->setUploadUrl($item);
        $item['dir'] = $this->getDir($item);
        return $item;
    }

    /**
     * 设置上传URL（子类可重写以支持不同的URL结构）
     * @param array $item
     * @return array
     */
    protected function setUploadUrl(array $item): array
    {
        $item['options']['url'] = 'https://' . $this->config['bucket'] . '.' . $this->config['endpoint'];
        return $item;
    }

    /**
     * 阿里云通用的上传策略获取
     * @return Json|true
     */
    public function getPolicy(): Json|true
    {
        $file = request()->post('file', []);

        // 进行安全预检，确保要上传的文件符合要求
        $result   = $this->fileCheck($file);
        if (true !== $result) {
            return $result;
        }

        // 判断文件是否已存在（基于hash/sha1）
        $exists = $this->isFileExists($file);
        if ($exists) {
            return json([
                'code' => 1,
                'msg'  => '文件已存在',
                'data' => $exists,
            ]);
        }

        $policy = [
            // 凭证过期时间
            'expiration' => str_replace('+00:00', '.000Z', gmdate('c', time() + $this->config['expire'])),
            // 指定Post请求的表单域的合法值
            'conditions' => $this->createPolicyConditions($file)
        ];
        $policy = base64_encode(json_encode($policy));

        $signature = base64_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));

        return json([
            'code' => 1,
            'msg'  => '获取上传凭证成功',
            'data' => [
                'exists'         => false,
                'policy'         => $policy,
                'OSSAccessKeyId' => $this->config['access_key'],
                'Signature'      => $signature,
            ],
        ]);
    }

    /**
     * 创建策略条件（子类可重写以定制文件检查目录）
     * @param array $file
     * @return array
     */
    protected function createPolicyConditions(array $file): array
    {
        return [
            // 指定上传的bucket
            ["bucket" => $this->config['bucket']],
            // 上传Object的最小和最大允许大小，单位为字节
            ['content-length-range', 1, intval($file['size'])],
            // 指定上传路径
            ['eq', '$key', $file['url']],
        ];
    }

    /**
     * 阿里云特定的配置验证
     * @param array $errors
     * @param array $warnings
     */
    protected function validateDriverSpecificConfig(array &$errors, array &$warnings): void
    {
        // 检查必需的配置项
        if (empty($this->config['access_key'])) {
            $errors[] = "缺少 access_key 配置";
        }

        if (empty($this->config['secret_key'])) {
            $errors[] = "缺少 secret_key 配置";
        }

        if (empty($this->config['bucket'])) {
            $errors[] = "缺少 bucket 配置";
        }

        if (empty($this->config['endpoint'])) {
            $errors[] = "缺少 endpoint 配置";
        }

        // 检查可选配置项
        if (empty($this->config['domain'])) {
            $warnings[] = "未配置 domain，将使用默认访问域名";
        }
    }
}
