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

namespace app\admin\controller;

use app\common\service\UploadDriverManager;
use Exception;
use ReflectionMethod;
use think\response\Json;

/**
 * 上传驱动请求控制器
 */
class Uploader extends Auth
{
    /**
     * 处理上传驱动请求
     *
     * @param string $driver 驱动名称
     * @param string $action 动作名称
     * @param string $type 驱动类型（可选）
     * @return Json
     */
    public function index(string $driver, string $action, string $type = ''): Json
    {
        // 安全检查：禁止访问 local 驱动
        if ($driver === 'local') {
            $this->error('不允许访问本地驱动');
        }

        // 安全检查：防止调用受保护的方法
        if ($this->isProtectedMethod($action)) {
            $this->error("不允许访问的方法: $action");
        }

        try {
            // 使用 UploadDriverManager 统一管理驱动
            $driverName = $type === '' ? $driver : $driver . '.' . $type;
            $object = UploadDriverManager::driver($driverName, [
                'context' => 'uploader',
                'action'  => $action
            ]);

            // 安全检查：方法是否存在
            if (!method_exists($object, $action)) {
                $this->error("不支持的操作: $action");
            }

            // 安全检查：确保方法是公共的
            $reflection = new ReflectionMethod($object, $action);
            if (!$reflection->isPublic()) {
                $this->error("方法不可访问: $action");
            }

            // 执行方法并返回结果
            $result = $object->$action();

            // 确保返回的是有效的响应
            if ($result instanceof Json) {
                return $result;
            }

            return json($result);

        } catch (Exception) {
            $this->error("请求处理失败" );
        }
    }

    /**
     * 检查是否为受保护的方法（不允许外部调用）
     *
     * @param string $method 方法名
     * @return bool
     */
    private function isProtectedMethod(string $method): bool
    {
        // 黑名单：不允许通过 HTTP 调用的方法
        $blacklist = [
            // 魔术方法
            '__construct', '__destruct', '__call', '__callStatic',
            '__get', '__set', '__isset', '__unset', '__toString',
            '__invoke', '__clone', '__sleep', '__wakeup', '__serialize',
            '__unserialize', '__set_state', '__debugInfo',

            // 上传驱动的内部方法
            'initialize', 'setConfig', 'getConfig', 'setDriver',
            'getDriver', 'setContext', 'getContext',

            // 其他敏感方法
            'delete', 'deleteFile', 'deleteDir', 'move', 'rename',
        ];

        // 检查是否在黑名单中
        if (in_array($method, $blacklist)) {
            return true;
        }

        // 检查是否以下划线开头（约定：下划线开头的方法为内部方法）
        if (str_starts_with($method, '_')) {
            return true;
        }

        return false;
    }
}
