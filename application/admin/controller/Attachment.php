<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\admin\model\Attachment as AttachmentModel;
use think\Image;
use think\File;
use think\Hook;
use think\Db;

/**
 * 附件控制器
 * @package app\admin\controller
 */
class Attachment extends Admin
{
    /**
     * 附件列表
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();

        // 数据列表
        $data_list = AttachmentModel::where($map)->order('sort asc,id desc')->paginate();
        foreach ($data_list as $key => &$value) {
            if (in_array(strtolower($value['ext']), ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                if ($value['driver'] == 'local') {
                    $thumb = $value['thumb'] != '' ? $value['thumb'] : $value['path'];
                    $value['type'] = '<img class="image" title="点击查看大图" data-original="'. PUBLIC_PATH . $value['path'].'" src="'. PUBLIC_PATH . $thumb.'">';
                } else {
                    $value['type'] = '<img class="image" title="点击查看大图" data-original="'. $value['path'].'" src="'. $value['path'].'">';
                }
            } else {
                if ($value['driver'] == 'local') {
                    $path = PUBLIC_PATH. $value['path'];
                } else {
                    $path = $value['path'];
                }
                if (is_file('.'.config('public_static_path').'admin/img/files/'.$value['ext'].'.png')) {
                    $value['type'] = '<a href="'. $path.'"
                        data-toggle="tooltip" title="点击下载">
                        <img class="image" src="'.config('public_static_path').'admin/img/files/'.$value['ext'].'.png"></a>';
                } else {
                    $value['type'] = '<a href="'. $path.'"
                        data-toggle="tooltip" title="点击下载">
                        <img class="image" src="'.config('public_static_path').'admin/img/files/file.png"></a>';
                }
            }
        }

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['type', '类型', '', '', '', 'js-gallery'],
                ['name', '名称'],
                ['size', '大小', 'byte'],
                ['driver', '上传驱动', parse_attr(Db::name('admin_config')->where('name', 'upload_driver')->value('options'))],
                ['create_time', '上传时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 上传附件
     * @param string $dir 保存的目录:images,files,videos,voices
     * @param string $from 来源，wangeditor：wangEditor编辑器, ueditor:ueditor编辑器, editormd:editormd编辑器等
     * @param string $module 来自哪个模块
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function upload($dir = '', $from = '', $module = '')
    {
        // 临时取消执行时间限制
        set_time_limit(0);
        if ($dir == '') $this->error('没有指定上传目录');
        if ($from == 'ueditor') return $this->ueditor();
        if ($from == 'jcrop') return $this->jcrop();
        return $this->saveFile($dir, $from, $module);
    }

    /**
     * 保存附件
     * @param string $dir 附件存放的目录
     * @param string $from 来源
     * @param string $module 来自哪个模块
     * @author 蔡伟明 <314013107@qq.com>
     * @return string|\think\response\Json
     */
    private function saveFile($dir = '', $from = '', $module = '')
    {
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';
        // 缩略图参数
        $thumb = $this->request->post('thumb', '');
        // 水印参数
        $watermark = $this->request->post('watermark', '');

        // 获取附件数据
        $callback = '';
        switch ($from) {
            case 'editormd':
                $file_input_name = 'editormd-image-file';
                break;
            case 'ckeditor':
                $file_input_name = 'upload';
                $callback = $this->request->get('CKEditorFuncNum');
                break;
            case 'ueditor_scrawl':
                return $this->saveScrawl();
                break;
            default:
                $file_input_name = 'file';
        }
        $file = $this->request->file($file_input_name);

        // 判断附件是否已存在
        if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
            if ($file_exists['driver'] == 'local') {
                $file_path = PUBLIC_PATH. $file_exists['path'];
            } else {
                $file_path = $file_exists['path'];
            }
            switch ($from) {
                case 'wangeditor':
                    return $file_path;
                    break;
                case 'ueditor':
                    return json([
                        "state" => "SUCCESS",          // 上传状态，上传成功时必须返回"SUCCESS"
                        "url"   => $file_path, // 返回的地址
                        "title" => $file_exists['name'], // 附件名
                    ]);
                    break;
                case 'editormd':
                    return json([
                        "success" => 1,
                        "message" => '上传成功',
                        "url"     => $file_path,
                    ]);
                    break;
                case 'ckeditor':
                    return ck_js($callback, $file_path);
                    break;
                default:
                    return json([
                        'code'   => 1,
                        'info'   => '上传成功',
                        'class'  => 'success',
                        'id'     => $file_exists['id'],
                        'path'   => $file_path
                    ]);
            }
        }

        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
            switch ($from) {
                case 'wangeditor':
                    return "error|附件过大";
                    break;
                case 'ueditor':
                    return json(['state' => '附件过大']);
                    break;
                case 'editormd':
                    return json(["success" => 0, "message" => '附件过大']);
                    break;
                case 'ckeditor':
                    return ck_js($callback, '', '附件过大');
                    break;
                default:
                    return json([
                        'code'   => 0,
                        'class'  => 'danger',
                        'info'   => '附件过大'
                    ]);
            }
        }

        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext  = strtolower(substr($file_name, strrpos($file_name, '.')+1));
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = '获取文件信息失败！';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = '禁止上传非法文件！';
        }
        if (preg_grep("/php/i", $ext_limit)) {
            $error_msg = '禁止上传非法文件！';
        }
        if (!preg_grep("/$file_ext/i", $ext_limit)) {
            $error_msg = '附件类型不正确！';
        }

        if ($error_msg != '') {
            switch ($from) {
                case 'wangeditor':
                    return "error|{$error_msg}";
                    break;
                case 'ueditor':
                    return json(['state' => $error_msg]);
                    break;
                case 'editormd':
                    return json(["success" => 0, "message" => $error_msg]);
                    break;
                case 'ckeditor':
                    return ck_js($callback, '', $error_msg);
                    break;
                default:
                    return json([
                        'code'   => 0,
                        'class'  => 'danger',
                        'info'   => $error_msg
                    ]);
            }
        }

        // 附件上传钩子，用于第三方文件上传扩展
        if (config('upload_driver') != 'local') {
            $hook_result = Hook::listen('upload_attachment', $file, ['from' => $from, 'module' => $module], true);
            if (false !== $hook_result) {
                return $hook_result;
            }
        }

        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move(config('upload_path') . DS . $dir);
        if($info){
            // 缩略图路径
            $thumb_path_name = '';
            // 图片宽度
            $img_width = '';
            // 图片高度
            $img_height = '';
            if ($dir == 'images') {
                $img = Image::open($info);
                $img_width  = $img->width();
                $img_height = $img->height();
                // 水印功能
                if ($watermark == '') {
                    if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                        $this->create_water($info->getRealPath(), config('upload_thumb_water_pic'));
                    }
                } else {
                    if (strtolower($watermark) != 'close') {
                        list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
                        $this->create_water($info->getRealPath(), $watermark_img, $watermark_pos, $watermark_alpha);
                    }
                }

                // 生成缩略图
                if ($thumb == '') {
                    if (config('upload_image_thumb') != '') {
                        $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
                    }
                } else {
                    if (strtolower($thumb) != 'close') {
                        list($thumb_size, $thumb_type) = explode('|', $thumb);
                        $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename(), $thumb_size, $thumb_type);
                    }
                }
            }

            // 获取附件信息
            $file_info = [
                'uid'    => session('user_auth.uid'),
                'name'   => $file->getInfo('name'),
                'mime'   => $file->getInfo('type'),
                'path'   => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
                'ext'    => $info->getExtension(),
                'size'   => $info->getSize(),
                'md5'    => $info->hash('md5'),
                'sha1'   => $info->hash('sha1'),
                'thumb'  => $thumb_path_name,
                'module' => $module,
                'width'  => $img_width,
                'height' => $img_height,
            ];

            // 写入数据库
            if ($file_add = AttachmentModel::create($file_info)) {
                $file_path = PUBLIC_PATH. $file_info['path'];
                switch ($from) {
                    case 'wangeditor':
                        return $file_path;
                        break;
                    case 'ueditor':
                        return json([
                            "state" => "SUCCESS",          // 上传状态，上传成功时必须返回"SUCCESS"
                            "url"   => $file_path, // 返回的地址
                            "title" => $file_info['name'], // 附件名
                        ]);
                        break;
                    case 'editormd':
                        return json([
                            "success" => 1,
                            "message" => '上传成功',
                            "url"     => $file_path,
                        ]);
                        break;
                    case 'ckeditor':
                        return ck_js($callback, $file_path);
                        break;
                    default:
                        return json([
                            'code'   => 1,
                            'info'   => '上传成功',
                            'class'  => 'success',
                            'id'     => $file_add['id'],
                            'path'   => $file_path
                        ]);
                }
            } else {
                switch ($from) {
                    case 'wangeditor':
                        return "error|上传失败";
                        break;
                    case 'ueditor':
                        return json(['state' => '上传失败']);
                        break;
                    case 'editormd':
                        return json(["success" => 0, "message" => '上传失败']);
                        break;
                    case 'ckeditor':
                        return ck_js($callback, '', '上传失败');
                        break;
                    default:
                        return json(['code' => 0, 'class' => 'danger', 'info' => '上传失败']);
                }
            }
        }else{
            switch ($from) {
                case 'wangeditor':
                    return "error|".$file->getError();
                    break;
                case 'ueditor':
                    return json(['state' => $file->getError()]);
                    break;
                case 'editormd':
                    return json(["success" => 0, "message" => $file->getError()]);
                    break;
                case 'ckeditor':
                    return ck_js($callback, '', $file->getError());
                    break;
                default:
                    return json(['code' => 0, 'class' => 'danger', 'info' => $file->getError()]);
            }
        }
    }

    /**
     * 处理ueditor上传
     * @author 蔡伟明 <314013107@qq.com>
     * @return string|\think\response\Json
     */
    private function ueditor(){
        $action      = $this->request->get('action');
        $config_file = './static/libs/ueditor/php/config.json';
        $config      = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($config_file)), true);
        switch ($action) {
            /* 获取配置信息 */
            case 'config':
                $result = $config;
                break;

            /* 上传图片 */
            case 'uploadimage':
                return $this->saveFile('images', 'ueditor');
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                return $this->saveFile('images', 'ueditor_scrawl');
                break;

            /* 上传视频 */
            case 'uploadvideo':
                return $this->saveFile('videos', 'ueditor');
                break;

            /* 上传附件 */
            case 'uploadfile':
                return $this->saveFile('files', 'ueditor');
                break;

            /* 列出图片 */
            case 'listimage':
                return $this->showFile('listimage', $config);
                break;

            /* 列出附件 */
            case 'listfile':
                return $this->showFile('listfile', $config);
                break;

            /* 抓取远程附件 */
//            case 'catchimage':
//                $result = include("action_crawler.php");
//                break;

            default:
                $result = ['state' => '请求地址出错'];
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                return htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                return json(['state' => 'callback参数不合法']);
            }
        } else {
            return json($result);
        }
    }

    /**
     * 保存涂鸦（ueditor）
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    private function saveScrawl()
    {
        $file         = $this->request->post('file');
        $file_content = base64_decode($file);
        $file_name    = md5($file) . '.jpg';
        $dir          = config('upload_path') . DS . 'images' . DS . date('Ymd', $this->request->time());
        $file_path    = $dir . DS . $file_name;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (false === file_put_contents($file_path, $file_content)) {
            return json(['state' => '涂鸦上传出错']);
        }

        $file = new File($file_path);
        $img  = Image::open($file);
        $file_info = [
            'uid'    => session('user_auth.uid'),
            'name'   => $file_name,
            'mime'   => 'image/png',
            'path'   => 'uploads/images/' . date('Ymd', $this->request->time()) . '/' . $file_name,
            'ext'    => 'png',
            'size'   => $file->getSize(),
            'md5'    => $file->hash('md5'),
            'sha1'   => $file->hash('sha1'),
            'module' => $this->request->module(),
            'width'  => $img->width(),
            'height' => $img->height()
        ];

        if ($file_add = AttachmentModel::create($file_info)) {
            // 返回成功信息
            return json([
                "state" => "SUCCESS",          // 上传状态，上传成功时必须返回"SUCCESS"
                "url"   => PUBLIC_PATH. $file_info['path'], // 返回的地址
                "title" => $file_info['name'], // 附件名
            ]);
        } else {
            return json(['state' => '涂鸦上传出错']);
        }
    }

    /**
     * 显示附件列表（ueditor）
     * @param string $type 类型
     * @param $config
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    private function showFile($type = '', $config){
        /* 判断类型 */
        switch ($type) {
            /* 列出附件 */
            case 'listfile':
                $allowFiles = $config['fileManagerAllowFiles'];
                $listSize = $config['fileManagerListSize'];
                $path = realpath(config('upload_path') .'/files/');
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $config['imageManagerAllowFiles'];
                $listSize = $config['imageManagerListSize'];
                $path = realpath(config('upload_path') .'/images/');
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取附件列表 */
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ));
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }
        //倒序
        //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        //    $list[] = $files[$i];
        //}

        /* 返回数据 */
        $result = array(
            "state" => "SUCCESS",
            "list"  => $list,
            "start" => $start,
            "total" => count($files)
        );

        return json($result);
    }

    /**
     * 处理Jcrop图片裁剪
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function jcrop()
    {
        $file_path = $this->request->post('path', '');
        $cut_info  = $this->request->post('cut', '');
        $thumb     = $this->request->post('thumb', '');
        $watermark = $this->request->post('watermark', '');
        $module    = $this->request->param('module', '');

        // 上传图片
        if ($file_path == '') {
            $file = $this->request->file('file');
            if (!is_dir(config('upload_temp_path'))) {
                mkdir(config('upload_temp_path'), 0766, true);
            }
            $info = $file->move(config('upload_temp_path'), $file->hash('md5'));
            if ($info) {
                return json(['code' => 1, 'src' => PUBLIC_PATH. 'uploads/temp/'. $info->getFilename()]);
            } else {
                $this->error('上传失败');
            }
        }

        $file_path = config('upload_temp_path') . str_replace(PUBLIC_PATH. 'uploads/temp/', '', $file_path);

        if (is_file($file_path)) {
            // 获取裁剪信息
            $cut_info  = explode(',', $cut_info);

            // 读取图片
            $image = Image::open($file_path);

            $dir_name = date('Ymd');
            $file_dir = config('upload_path') . DS . 'images/' . $dir_name . '/';
            if (!is_dir($file_dir)) {
                mkdir($file_dir, 0766, true);
            }
            $file_name     = md5(microtime(true)) . '.' . $image->type();
            $new_file_path = $file_dir . $file_name;

            // 裁剪图片
            $image->crop($cut_info[0], $cut_info[1], $cut_info[2], $cut_info[3], $cut_info[4], $cut_info[5])->save($new_file_path);

            // 水印功能
            if ($watermark == '') {
                if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                    $this->create_water($new_file_path, config('upload_thumb_water_pic'));
                }
            } else {
                if (strtolower($watermark) != 'close') {
                    list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
                    $this->create_water($new_file_path, $watermark_img, $watermark_pos, $watermark_alpha);
                }
            }

            // 是否创建缩略图
            $thumb_path_name = '';
            if ($thumb == '') {
                if (config('upload_image_thumb') != '') {
                    $thumb_path_name = $this->create_thumb($new_file_path, $dir_name, $file_name);
                }
            } else {
                if (strtolower($thumb) != 'close') {
                    list($thumb_size, $thumb_type) = explode('|', $thumb);
                    $thumb_path_name = $this->create_thumb($new_file_path, $dir_name, $file_name, $thumb_size, $thumb_type);
                }
            }

            // 保存图片
            $file = new File($new_file_path);
            $file_info = [
                'uid'    => session('user_auth.uid'),
                'name'   => $file_name,
                'mime'   => $image->mime(),
                'path'   => 'uploads/images/' . $dir_name . '/' . $file_name,
                'ext'    => $image->type(),
                'size'   => $file->getSize(),
                'md5'    => $file->hash('md5'),
                'sha1'   => $file->hash('sha1'),
                'thumb'  => $thumb_path_name,
                'module' => $module,
                'width'  => $image->width(),
                'height' => $image->height()
            ];

            if ($file_add = AttachmentModel::create($file_info)) {
                // 删除临时图片
                unlink($file_path);
                // 返回成功信息
                return json([
                    'code'  => 1,
                    'id'    => $file_add['id'],
                    'src'   => PUBLIC_PATH . $file_info['path'],
                    'thumb' => $thumb_path_name == '' ? '' : PUBLIC_PATH . $thumb_path_name,
                ]);
            } else {
                $this->error('上传失败');
            }
        }
        $this->error('文件不存在');
    }

    /**
     * 创建缩略图
     * @param string $file 目标文件，可以是文件对象或文件路径
     * @param string $dir 保存目录，即目标文件所在的目录名
     * @param string $save_name 缩略图名
     * @param string $thumb_size 尺寸
     * @param string $thumb_type 裁剪类型
     * @author 蔡伟明 <314013107@qq.com>
     * @return string 缩略图路径
     */
    private function create_thumb($file = '', $dir = '', $save_name = '', $thumb_size = '', $thumb_type = '')
    {
        // 获取要生成的缩略图最大宽度和高度
        $thumb_size = $thumb_size == '' ? config('upload_image_thumb') : $thumb_size;
        list($thumb_max_width, $thumb_max_height) = explode(',', $thumb_size);
        // 读取图片
        $image = Image::open($file);
        // 生成缩略图
        $thumb_type = $thumb_type == '' ? config('upload_image_thumb_type') : $thumb_type;
        $image->thumb($thumb_max_width, $thumb_max_height, $thumb_type);
        // 保存缩略图
        $thumb_path = config('upload_path') . DS . 'images/' . $dir . '/thumb/';
        if (!is_dir($thumb_path)) {
            mkdir($thumb_path, 0766, true);
        }
        $thumb_path_name = $thumb_path. $save_name;
        $image->save($thumb_path_name);
        $thumb_path_name = 'uploads/images/' . $dir . '/thumb/' . $save_name;
        return $thumb_path_name;
    }

    /**
     * 添加水印
     * @param string $file 要添加水印的文件路径
     * @param string $watermark_img 水印图片id
     * @param string $watermark_pos 水印位置
     * @param string $watermark_alpha 水印透明度
     * @author 蔡伟明 <314013107@qq.com>
     */
    private function create_water($file = '', $watermark_img = '', $watermark_pos = '', $watermark_alpha = '')
    {
        $path = model('admin/attachment')->getFilePath($watermark_img, 1);
        $thumb_water_pic = realpath(ROOT_PATH . 'public/' . $path);
        if (is_file($thumb_water_pic)) {
            // 读取图片
            $image = Image::open($file);
            // 添加水印
            $watermark_pos   = $watermark_pos   == '' ? config('upload_thumb_water_position') : $watermark_pos;
            $watermark_alpha = $watermark_alpha == '' ? config('upload_thumb_water_alpha') : $watermark_alpha;
            $image->water($thumb_water_pic, $watermark_pos, $watermark_alpha);
            // 保存水印图片，覆盖原图
            $image->save($file);
        }
    }

    /**
     * 遍历获取目录下的指定类型的附件
     * @param string $path 路径
     * @param string $allowFiles 允许查看的类型
     * @param array $files 文件列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|null
     */
    public function getfiles($path = '', $allowFiles = '', &$files = array())
    {
        if (!is_dir($path)) return null;
        if(substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                        $files[] = array(
                            'url'=> str_replace("\\", "/", substr($path2, strlen($_SERVER['DOCUMENT_ROOT']))),
                            'mtime'=> filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 启用附件
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用附件
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置附件状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = is_array($ids) ? implode(',', $ids) : $ids;
        return parent::setStatus($type, ['attachment_'.$type, 'admin_attachment', 0, UID, $ids]);
    }

    /**
     * 删除附件
     * @param string $ids 附件id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function delete($ids = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids)) $this->error('缺少主键');

        $files_path = AttachmentModel::where('id', 'in', $ids)->column('path,thumb', 'id');

        foreach ($files_path as $value) {
            $real_path       = realpath(config('upload_path').'/../'.$value['path']);
            $real_path_thumb = realpath(config('upload_path').'/../'.$value['thumb']);

            if (is_file($real_path) && !unlink($real_path)) {
                $this->error('删除失败');
            }
            if (is_file($real_path_thumb) && !unlink($real_path_thumb)) {
                $this->error('删除缩略图失败');
            }
        }
        if (AttachmentModel::where('id', 'in', $ids)->delete()) {
            // 记录行为
            $ids = is_array($ids) ? implode(',', $ids) : $ids;
            action_log('attachment_delete', 'admin_attachment', 0, UID, $ids);
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id = input('post.pk', '');
        return parent::quickEdit(['attachment_edit', 'admin_attachment', 0, UID, $id]);
    }
}