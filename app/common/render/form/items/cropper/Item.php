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

namespace app\common\render\form\items\cropper;

use app\common\abstract\FormItem;
use app\common\trait\SupportsUploadDriver;
use app\common\render\Form as FormRender;
use Exception;

/**
 * 图片裁剪器
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
        'buttons' => ['upload', 'url', 'select', 'delete'],
        'actions' => ['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right', 'scale-x', 'scale-y']
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

        // 默认上传路径
        $this->default['url'] = config('upload.url');

        // 合并参数
        $params = array_merge($this->default, $params);

        // 分析上传驱动
        if ($formRender) {
            $params = $this->processUploadDriver($params, $formRender);
        }

        if (is_string($params['buttons'])) {
            $params['buttons'] = explode(',', $params['buttons']);
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
                '__LIBS__/cropperjs/cropper.min.js',
                '__THEME_LIBS__/fslightbox/index.js',
                '__LIBS__/crypto-js/crypto-js.js',
            ],
            'init' => ['cropper']
        ];
    }
}
