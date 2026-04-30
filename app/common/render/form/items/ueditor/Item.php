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

namespace app\common\render\form\items\ueditor;

use app\admin\facade\FileService;
use app\common\abstract\FormItem;
use app\common\render\Form as FormRender;
use app\common\trait\SupportsUploadDriver;
use think\Response;
use Exception;
use think\response\Json;

/**
 * ueditor编辑器
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
        try {
            // 默认上传驱动
            $this->default['driver'] = config('upload.default');

            // 合并默认参数
            $params = array_merge($this->default, $params);

            // 分析上传驱动
            $params = $this->processUploadDriver($params, $formRender);

            // 设置 serverUrl（UEditor 配置接口）
            if (!isset($params['options']['serverUrl'])) {
                $params['options']['serverUrl'] = '/_form/ueditor/config';
            }

            $params['options'] = dp_parse_options($params['options'] ?? []);
            return $params;
        } catch (Exception $e) {
            throw new Exception(lang('dp#form item rendering failed', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * 获取表单项资源
     * @return array
     */
    public function getAssets(): array
    {
        return [
            'js'   => [
                '__LIBS__/ueditor-plus/ueditor.config.js',
                '__LIBS__/ueditor-plus/ueditor.all.js'
            ],
            'init' => ['ueditor']
        ];
    }

    /**
     * 获取 UEditor 配置
     * HTTP 路由：/_form/ueditor/config
     * 各个配置项的详细含义请参考官网：https://open.modstart.com/ueditor-plus/backend.html
     * @return Response
     */
    public function config(): Response
    {
        $action = request()->param('action', 'config');

        if ($action == 'listImage') {
            return $this->listAttachments('image');
        } elseif ($action == 'listFile') {
            return $this->listAttachments('file');
        } else {
            return $this->defaultConfig();
        }
    }

    /**
     * 获取附件列表
     * @param string $type image/file
     * @return Json
     */
    private function listAttachments(string $type): Json
    {
        $start = (int)request()->param('start', 0);
        if ($start < 0) {
            $start = 0;
        }

        $size = (int)request()->param('size', 20);
        if ($size <= 0) {
            $size = 20;
        }

        $page = (int)floor($start / $size) + 1;

        $files = FileService::getList($type, [
            'per_page' => $size,
            'page'     => $page,
        ]);

        $list = [];
        foreach ($files as $item) {
            $list[] = [
                'url'   => $item['url'] ?? '',
                'mtime' => (int)($item['create_time'] ?? 0),
            ];
        }

        return json([
            'state' => 'SUCCESS',
            'list'  => $list,
            'start' => $start,
            'total' => (int)$files->total(),
        ]);
    }

    /**
     * 获取 UEditor 配置
     * @return Json
     */
    private function defaultConfig(): Json
    {
        // 这些配置主要用于编辑器初始化验证，实际上传不会使用这些配置
        return json([
            // 图片上传配置
            'imageActionName'         => 'image',
            'imageFieldName'          => 'file',
            'imageMaxSize'            => config('upload.size_limit.image'),
            'imageAllowFiles'         => $this->fixExt(config('upload.allowed_ext.image')),
            'imageCompressEnable'     => false,
            'imageCompressBorder'     => 5000,
            'imageInsertAlign'        => 'none',
            'imageUrlPrefix'          => '',

            // 涂鸦上传配置
            'scrawlActionName'        => 'scrawl',
            'scrawlFieldName'         => 'file',
            'scrawlMaxSize'           => config('upload.size_limit.image'),
            'scrawlUrlPrefix'         => '',
            'scrawlInsertAlign'       => 'none',

            // 截图上传配置
            'snapscreenActionName'    => 'snap',
            'snapscreenPathFormat'    => '',
            'snapscreenInsertAlign'   => 'none',

            // 抓取远程图片配置
            'catcherActionName'       => 'catch',
            'catcherFieldName'        => 'source',
            'catcherLocalDomain'      => [],
            'catcherUrlPrefix'        => '',
            'catcherMaxSize'          => config('upload.size_limit.image'),
            'catcherAllowFiles'       => $this->fixExt(config('upload.allowed_ext.image')),

            // 视频上传配置
            'videoActionName'         => 'video',
            'videoFieldName'          => 'file',
            'videoUrlPrefix'          => '',
            'videoMaxSize'            => config('upload.size_limit.video'),
            'videoAllowFiles'         => $this->fixExt(config('upload.allowed_ext.video')),

            // 音频上传配置
            'audioActionName'         => 'audio',
            'audioFieldName'          => 'file',
            'audioUrlPrefix'          => '',
            'audioMaxSize'            => config('upload.size_limit.audio'),
            'audioAllowFiles'         => $this->fixExt(config('upload.allowed_ext.audio')),

            // 文件上传配置
            'fileActionName'          => 'file',
            'fileFieldName'           => 'file',
            'fileUrlPrefix'           => '',
            'fileMaxSize'             => config('upload.size_limit.file'),
            'fileAllowFiles'          => $this->fixExt(config('upload.allowed_ext.file')),

            // 图片管理配置
            'imageManagerActionName'  => 'listImage',
            'imageManagerListSize'    => 20,
            'imageManagerUrlPrefix'   => '',
            'imageManagerInsertAlign' => 'none',
            'imageManagerAllowFiles'  => $this->fixExt(config('upload.allowed_ext.image')),

            // 文件管理配置
            'fileManagerActionName'   => 'listFile',
            'fileManagerUrlPrefix'    => '',
            'fileManagerListSize'     => 20,
            'fileManagerAllowFiles'   => $this->fixExt(config('upload.allowed_ext.file')),
        ]);
    }

    /**
     * 修正扩展名
     * @param array $array
     * @return array
     */
    private function fixExt(array $array): array
    {
        return array_map(function ($item) {
            return '.' . $item;
        }, $array);
    }
}
