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

namespace app\common\render\form\items\file;

use app\common\abstract\FormItem;
use app\common\render\Form as FormRender;
use app\common\trait\SupportsUploadDriver;
use Exception;

/**
 * 文件上传
 */
class Item extends FormItem
{
    use SupportsUploadDriver;
    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'upload'   => true,
        'browser'  => true,
        'download' => true,
        'delete'   => true,
        'clear'    => true,
        'readonly' => false,
        'driver'   => 'local',
        'options'  => [],
        'maxFiles' => 1,
    ];

    /**
     * 渲染
     * @param array $params
     * @param FormRender|null $formRender
     * @return array
     * @throws Exception
     */
    public function handle(array $params = [], FormRender $formRender = null): array
    {
        // 默认上传驱动
        $this->default['driver'] = dp_setting('upload.default_driver', config('upload.default', 'local'));

        // 合并参数
        $params = array_merge($this->default, $params);

        // 判断是否只读形式
        if ($params['readonly']) {
            $params['upload']  = false;
            $params['browser'] = false;
            $params['delete']  = false;
            $params['clear']   = false;
        }

        // 解析value
        if ($params['value'] != '') {
            $values = is_string($params['value']) ? explode(',', $params['value']) : (array)$params['value'];
            foreach ($values as $key => $value) {
                $values[$key] = dp_get_file($value);
            }
            $params['value'] = $values;
        }

        // 解析options
        $params['options'] = $this->parseOptions($params['options']);
        $params['maxFiles'] = $params['options']['maxFiles'] ?? 1;

        // 分析上传驱动
        $params = $this->processUploadDriver($params, $formRender);

        // 上传地址
        $params['options']['endpoints']['upload'] = $params['options']['url'] == ''
            ? dp_url(config('upload.url'))->build()
            : $params['options']['url'];

        if ($params['options']['multiple']) {
            $params['id']       = $params['id'] ?? $params['name'];
            $params['name']    .= '[]';
        }

        $params['options'] = dp_parse_options($params['options']);

        return $params;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'   => [
                '__LIBS__/dolphin-uploader/uploader.js',
            ],
            'init' => ['file-upload']
        ];
    }

    /**
     * parseOptions
     * @param array $options
     * @return array
     */
    private function parseOptions(array $options): array
    {
        $options = array_replace_recursive([
            'url'          => '',
            'multiple'     => false,
            'extraData'    => [
                '_ajax' => 1,
                '_from' => 'file'
            ],
            'allowedTypes' => ['*'],
            'maxFileSize'  => 0,
            'autoUpload'   => false,
        ], $options);

        // 多图上传
        $options['maxFiles']            = match (true) {
            is_numeric($options['multiple']) => $options['multiple'] < 0 ? 1 : (int)$options['multiple'],
            default => $options['multiple'] ? 0 : 1
        };

        $options['multiple'] = !($options['maxFiles'] == 1);
        $options['url'] = (string)$options['url'];

        return $options;
    }
}
