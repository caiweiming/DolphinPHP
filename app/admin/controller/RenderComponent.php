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

use app\common\plugin\PluginRegistry;
use Exception;
use ReflectionMethod;
use think\Response;
use think\response\Json;

/**
 * 渲染组件 HTTP 请求控制器
 *
 * 用于处理来自前端的组件 HTTP 请求
 * 路由格式：/_:type/:component/:action
 *
 * 示例：
 * - /_form/ueditor/config        # 表单组件 UEditor 的配置接口
 * - /_form/select2/search        # 表单组件 Select2 的搜索接口
 * - /_table/image/preview        # 表格组件 Image 的预览接口
 * - /_table/switch/toggle        # 表格组件 Switch 的切换接口
 */
class RenderComponent extends Auth
{
    /**
     * 处理组件 HTTP 请求
     *
     * @param string $type 组件类型（form、table、page 等）
     * @param string $component 组件名称（ueditor、select2、image 等）
     * @param string $action 动作名称（config、search、preview 等）
     * @return Json|Response
     */
    public function handle(string $type, string $component, string $action): Json|Response
    {
        // 构建组件 Item 类名
        $itemClass = $this->buildItemClass($type, $component);

        // 检查类是否存在
        if (!class_exists($itemClass)) {
            return json([
                'error' => "组件不存在: $type/$component"
            ], 404);
        }

        try {
            // 实例化组件
            $item = new $itemClass();

            // 安全检查：方法是否存在
            if (!method_exists($item, $action)) {
                return json([
                    'error' => "不支持的操作: $action"
                ], 400);
            }

            // 安全检查：防止调用受保护的方法
            if ($this->isProtectedMethod($action)) {
                return json([
                    'error' => "不允许访问的方法: $action"
                ], 403);
            }

            // 安全检查：确保方法是公共的
            $reflection = new ReflectionMethod($item, $action);
            if (!$reflection->isPublic()) {
                return json([
                    'error' => "方法不可访问: $action"
                ], 403);
            }

            // 直接调用方法
            $result = $item->$action();

            // 确保返回的是 Response 对象
            if (!($result instanceof Response)) {
                return json($result);
            }

            return $result;

        } catch (Exception $e) {
            return json([
                'error' => "请求处理失败: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 构建组件 Item 类名
     *
     * @param string $type 组件类型
     * @param string $component 组件名称
     * @return string
     */
    private function buildItemClass(string $type, string $component): string
    {
        $pluginClass = app(PluginRegistry::class)->getComponentHandlerClass($type, $component);
        if ($pluginClass !== '') {
            return $pluginClass;
        }

        $component          = dp_normalize_extension_path($component);
        $componentNamespace = str_replace('/', '\\', $component);

        // 根据类型构建不同的命名空间
        return match ($type) {
            'form' => "\\app\\common\\render\\form\\items\\$componentNamespace\\Item",
            'table' => "\\app\\common\\render\\table\\$componentNamespace\\Item",
            'page' => "\\app\\common\\render\\page\\$componentNamespace\\Item",
            default => "\\app\\common\\render\\$type\\$componentNamespace\\Item",
        };
    }

    /**
     * 检查是否为受保护的方法
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

            // FormItem/TableItem 的内部方法
            'handle', 'handleValue', 'getAssets', 'getTemplate',
            'getParams', 'createTemplet',

            // 其他敏感方法
            'processUploadDriver', 'setParams', 'setValue',
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
