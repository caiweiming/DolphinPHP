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

use app\admin\facade\FileService;
use Closure;
use think\response\Json;

/**
 * 上传公共类
 */
abstract class Common
{
    /**
     * 默认配置
     * @var array
     */
    protected array $config = [];

    /**
     * 抽象方法：获取驱动名称
     * @return string
     */
    abstract protected function getDriverName(): string;

    /**
     * 抽象方法：获取组件类型
     * @return string
     */
    abstract protected function getComponentType(): string;

    /**
     * 抽象方法：获取默认配置
     * @return array
     */
    abstract protected function getDefaultConfig(): array;

    /**
     * 抽象方法：处理上传项配置（UploadDriver接口必须实现）
     * @param array $item
     * @return array
     */
    abstract public function handle(array $item = []): array;

    /**
     * 统一的构造方法
     * @param array $config 驱动配置
     */
    public function __construct(array $config = [])
    {
        // 合并配置优先级：传入配置 > 系统配置 > 默认配置
        $driverName   = $this->getDriverName();
        $systemConfig = dp_get_driver_config($driverName . '.config', []);
        $this->config = array_merge($this->getDefaultConfig(), $systemConfig, $config);
    }

    /**
     * 获取上传目录
     * @param array $item
     * @return string
     */
    protected function getDir(array $item): string
    {
        $dir = $item['dir'] ?? $this->config['dir'];
        if ($dir instanceof Closure) {
            $dir = (string)call_user_func($dir);
        }
        $dir = trim($dir, '/');
        return $dir != '' ? $dir . '/' : $dir;
    }

    /**
     * 文件合法检查
     * @param array $file
     * @return Json|true
     */
    protected function fileCheck(array $file): true|Json
    {
        if (empty($file)) {
            return json([
                'code' => 0,
                'msg'  => '缺少文件信息',
            ]);
        }

        // 判断文件大小是否超过限制
        if (FileService::overSize($file)) {
            return json([
                'code' => 0,
                'msg'  => '文件过大'
            ]);
        }

        // 判断文件格式是否合法
        if (FileService::overExt($file)) {
            return json([
                'code' => 0,
                'msg'  => '文件类型不正确，或非法文件'
            ]);
        }

        return true;
    }

    /**
     * 检查文件是否已存在（基于hash/sha1）
     * @param array $file
     * @return array|null
     */
    protected function isFileExists(array $file): ?array
    {
        $hash = $file['hash'] ?? '';
        if ($hash === '') {
            return null;
        }

        $existsFile = FileService::exists($hash);
        if (!$existsFile) {
            return null;
        }

        return [
            'exists' => true,
            'file'   => [
                'id'   => $existsFile['id'] ?? 0,
                'name' => $existsFile['name'] ?? '',
                'url'  => $existsFile['url'] ?? '',
                'sha1' => $existsFile['sha1'] ?? '',
            ],
        ];
    }

    /**
     * 通用的前端配置返回
     * @return array
     */
    public function config(): array
    {
        $domain = $this->config['domain'] ?? '';
        return [
            'domain' => rtrim($domain, '/') . '/',
        ];
    }

    /**
     * 默认的CSS文件加载
     * @return array
     */
    public function css(): array
    {
        return [];
    }

    /**
     * 基于规约的JS文件路径生成
     * @return array
     */
    public function js(): array
    {
        $driverName    = $this->getDriverName();
        $componentType = $this->getComponentType();
        return ["__EXTEND__/upload/$driverName/$componentType.js"];
    }

    /**
     * 统一的驱动元信息获取
     * @return array
     */
    public function meta(): array
    {
        // 获取基础元信息
        $meta = $this->getBaseMeta();

        // 添加运行时状态信息
        $healthStatus    = $this->healthCheck();
        $meta['healthy'] = $healthStatus['healthy'];
        $meta['runtime'] = $this->getRuntimeInfo();

        return $meta;
    }

    /**
     * 通用的健康检查框架
     * @return array
     */
    public function healthCheck(): array
    {
        $errors   = [];
        $warnings = [];

        // 通用配置验证
        $this->validateCommonConfig($errors, $warnings);

        // 驱动特定验证（子类可重写）
        $this->validateDriverSpecificConfig($errors, $warnings);

        return [
            'healthy'  => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 获取基础元信息
     * @return array
     */
    protected function getBaseMeta(): array
    {
        $driverName = $this->getDriverName();
        return dp_get_driver_meta($driverName);
    }

    /**
     * 获取运行时信息
     * @return array
     */
    protected function getRuntimeInfo(): array
    {
        return [
            'loaded_at'     => date('Y-m-d H:i:s'),
            'memory_usage'  => memory_get_usage(true),
            'config_status' => $this->getConfigStatus()
        ];
    }

    /**
     * 获取配置状态信息
     * @return array
     */
    protected function getConfigStatus(): array
    {
        $status = [];
        foreach ($this->config as $key => $value) {
            if (in_array($key, ['access_key', 'secret_key'])) {
                $status[$key] = !empty($value) ? '已配置' : '未配置';
            } else {
                $status[$key] = $value ?? '未配置';
            }
        }
        return $status;
    }

    /**
     * 通用配置验证
     * @param array $errors
     * @param array $warnings
     */
    protected function validateCommonConfig(array &$errors, array &$warnings): void
    {
        // 基础验证逻辑，子类可以扩展
    }

    /**
     * 驱动特定配置验证（子类可重写）
     * @param array $errors
     * @param array $warnings
     */
    protected function validateDriverSpecificConfig(array &$errors, array &$warnings): void
    {
        // 默认为空，子类可以重写
    }

}
