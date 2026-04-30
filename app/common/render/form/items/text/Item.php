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

namespace app\common\render\form\items\text;

use app\common\abstract\FormItem;
use Exception;

/**
 * 文本框组件
 */
class Item extends FormItem
{
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'class'       => '',
        'span_class'  => 'input-group-text',
        'input_class' => [],
        'float'       => false,
    ];

    /**
     * 渲染
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function handle(array $params = []): array
    {
        try {
            // 合并参数
            $params = array_merge($this->default, $params);
            // 处理class
            $params['class'] = $this->formatClass($params['class']);
            // 处理验证状态
            $this->handleValidation($params);
            // 处理输入组
            $this->handleInputGroup($params);
            // 处理重复
            $params['input_class'] = $this->formatClass($params['input_class']);

            return $params;
        } catch (Exception $e) {
            throw new Exception(lang('dp#form item rendering failed', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * 处理标签组
     * @param array $params
     * @throws Exception
     */
    private function handleInputGroup(array &$params): void
    {
        // 处理 prefix
        $params['prefix'] = $this->processGroupItem($params, 'prefix');

        // 处理 suffix
        $params['suffix'] = $this->processGroupItem($params, 'suffix');

        // 如果有 prefix 或 suffix，添加 input-group 类
        if ($params['prefix'] !== '' || $params['suffix'] !== '') {
            $params['input_class'][] = 'input-group';
        }

        if (!empty($params['rounded'])) {
            $params['input_class'][] = ' input-group-rounded';
        }

        // 处理特殊group类型
        if (!empty($params['group_type'])) {
            $this->handleSpecialGroup($params);
        }

        // 添加额外class
        if (!empty($params['extra_class'])) {
            $params['class'] .= ' ' . $params['extra_class'];
        }
    }

    /**
     * 处理输入组项（prefix 或 suffix）
     *
     * @param array $params 参数数组
     * @param string $key 键名（prefix 或 suffix）
     * @return string 处理后的值
     * @throws Exception
     */
    private function processGroupItem(array $params, string $key): string
    {
        // 如果未设置，返回空字符串
        if (!isset($params[$key])) {
            return '';
        }

        $value = $params[$key];

        // 如果是对象，调用其 handle 方法
        if (is_object($value)) {
            if (!method_exists($value, 'handle')) {
                throw new Exception(
                    lang('dp#undefined handle method', ['class' => get_class($value)])
                );
            }
            return (string)$value->handle();
        }

        // 否则格式化为字符串
        return $this->formatClass($value);
    }

    /**
     * 处理特殊group类型
     * @param array $params
     */
    private function handleSpecialGroup(array &$params): void
    {
        switch ($params['group_type']) {
            case 'text':
                $params['input_class'][] = ' input-group-flat';
                $params['prefix'] != '' && $params['class'] .= ' ps-0';
                $params['suffix'] != '' && $params['class'] .= ' pe-0';
                break;
            case 'icon':
                $params['icon']          = true;
                $params['span_class']    = 'input-icon-addon';
                $params['input_class'][] = 'input-icon';
                break;
            case 'button':
                $params['span_class'] = '';
                break;
        }
    }

    /**
     * 处理验证状态
     * @param array $params
     */
    private function handleValidation(array &$params): void
    {
        if (isset($params['valid']) && is_bool($params['valid'])) {
            $validClass               = $params['valid'] ? 'is-valid' : 'is-invalid';
            $params['class']          .= " $validClass";
            $params['feedback_class'] = $params['valid'] ? 'valid-feedback' : 'invalid-feedback';
        }
    }

    /**
     * 格式化class
     * @param $class
     * @return string
     */
    private function formatClass($class): string
    {
        return is_array($class) ? implode(' ', array_unique($class)) : $class;
    }
}
