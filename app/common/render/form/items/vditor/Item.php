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

namespace app\common\render\form\items\vditor;

use app\common\abstract\FormItem;
use app\common\render\Form as FormRender;
use app\common\trait\SupportsUploadDriver;
use Exception;

/**
 * vditor编辑器
 */
class Item extends FormItem
{
    use SupportsUploadDriver;

    /**
     * 默认参数配置
     * @var array
     */
    protected array $default = [
        'driver'  => 'local',
        'options' => [],
        'dir'     => '',
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
        $this->default['driver'] = config('upload.default');

        // 合并参数
        $params = array_merge($this->default, $params);

        // 解析options
        $params['options'] = $this->parseOptions($params['options']);

        // 分析上传驱动
        $params = $this->processUploadDriver($params, $formRender);

        // 上传地址
        $params['options']['upload']['url'] = $params['options']['upload']['url'] == ''
            ? dp_url(config('upload.url'))->build()
            : $params['options']['upload']['url'];

        $params['options'] = dp_parse_options($params['options'] ?? []);
        return $params;
    }

    /**
     * parseOptions
     * @param array $options
     * @return array
     */
    private function parseOptions(array $options): array
    {
        $options = array_replace_recursive([
            'upload' => [
                'url'       => dp_url(config('upload.url')),
                'extraData' => [
                    '_from' => 'vditor',
                    '_ajax' => 1
                ]
            ],
        ], $options);

        // 处理一些特殊参数
        $options['upload']['url'] = (string)$options['upload']['url'];

        return $options;
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'   => [
                '__LIBS__/vditor/dist/index.min.js'
            ],
            'css'  => [
                '__LIBS__/vditor/dist/index.css'
            ],
            'init' => ['vditor']
        ];
    }
}
