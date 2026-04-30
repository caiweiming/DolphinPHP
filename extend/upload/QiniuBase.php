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
use Qiniu\Auth;
use think\response\Json;

/**
 * 七牛云上传驱动抽象基类
 */
abstract class QiniuBase extends Common implements UploadDriver
{
    /**
     * 获取驱动名称
     * @return string
     */
    protected function getDriverName(): string
    {
        return 'qiniu';
    }

    /**
     * 获取默认配置
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            // 七牛云上传url, 空间存储区域不同, url可能不一样
            'url'        => '',
            // 密钥AK
            'access_key' => '',
            // 密钥SK
            'secret_key' => '',
            // 空间名称
            'bucket'     => '',
            // 空间域名
            'domain'     => '',
            // 上传目录
            'dir'        => ''
        ];
    }

    /**
     * 七牛云通用的处理方法
     * @param array $item
     * @return array
     */
    public function handle(array $item = []): array
    {
        $item['options']['url'] = $this->config['url'];
        $item['dir']            = $this->getDir($item);
        return $item;
    }

    /**
     * 七牛云通用的上传凭证获取
     * @return Json|true
     */
    public function getToken(): Json|true
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

        // 生成上传凭证
        $policy = $this->createTokenPolicy($file);

        $auth = new Auth($this->config['access_key'], $this->config['secret_key']);
        return json([
            'code' => 1,
            'msg'  => '获取上传凭证成功',
            'data' => [
                'exists' => false,
                'token'  => $auth->uploadToken($this->config['bucket'], null, 3600, $policy)
            ],
        ]);
    }

    /**
     * 创建上传策略（子类可重写以定制策略）
     * @param array $file
     * @return array
     */
    protected function createTokenPolicy(array $file): array
    {
        return [
            'mimeLimit'    => $file['mime'],
            'forceSaveKey' => true,
            'saveKey'      => $file['url'],
            'fsizeLimit'   => intval($file['size'])
        ];
    }

    /**
     * 七牛云特定的配置验证
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

        // 检查可选配置项
        if (empty($this->config['domain'])) {
            $warnings[] = "未配置 domain，将无法生成访问URL";
        }
    }
}
