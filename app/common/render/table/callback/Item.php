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

namespace app\common\render\table\callback;

use app\common\abstract\TableItem;
use app\common\render\Table as TableRender;
use Closure;
use Exception;
use think\facade\Config;
use think\facade\Log;

/**
 * 回调处理
 */
class Item extends TableItem
{
    /**
     * handle
     * @param array $column
     * @param TableRender $table
     * @return array
     * @throws Exception
     */
    public function handle(array $column, TableRender $table): array
    {
        return $column;
    }

    /**
     * 处理值
     * @param mixed $data
     * @param array $column
     * @param array|object $originalData
     * @return mixed
     */
    public function handleValue(mixed $data, array $column = [], array|object $originalData = []): mixed
    {
        $column['callback'] ??= $column['options'];

        if ($column['callback'] instanceof Closure) {
            $data[$column['field']] = call_user_func_array($column['callback'], [$data[$column['field']] ?? '', $data, $originalData]);
        } elseif (is_string($column['callback'])) {
            $data[$column['field']] = $this->handleStrFun($column['callback'], $data[$column['field']]);
        }

        return $data;
    }

    /**
     * 处理字符串函数
     * @param string $content
     * @param mixed $data
     * @return string
     */
    private function handleStrFun(string $content, mixed $data): string
    {
        $content  = explode(':', $content);
        $callback = $content[0];
        $params   = isset($content[1]) ? explode(',', $content[1]) : [];
        $params   = array_merge([$data], $params);

        if (!$this->isCallbackAllowed($callback)) {
            Log::warning('table callback function blocked', [
                'callback' => $callback,
            ]);
            return (string)$data;
        }

        if (!is_callable($callback)) {
            Log::warning('table callback function not callable', [
                'callback' => $callback,
            ]);
            return (string)$data;
        }

        return call_user_func_array($callback, $params);
    }

    /**
     * 判断 callback 函数是否允许调用
     * @param string $callback
     * @return bool
     */
    private function isCallbackAllowed(string $callback): bool
    {
        $allowedFunctions = Config::get('table.security.callback_allowed_functions', ['dp_join_array_column']);
        if (in_array($callback, $allowedFunctions, true)) {
            return true;
        }

        $patterns = Config::get('table.security.callback_allowed_patterns', ['dp_*']);
        foreach ($patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }

            // 正则模式：/pattern/flags
            if ($pattern[0] === '/' && strrpos($pattern, '/') !== 0) {
                if (@preg_match($pattern, $callback) === 1) {
                    return true;
                }
                continue;
            }

            // fnmatch 通配模式
            if (fnmatch($pattern, $callback)) {
                return true;
            }
        }

        return false;
    }
}
