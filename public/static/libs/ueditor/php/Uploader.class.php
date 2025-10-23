<?php

/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 12-7-18
 * Time: 上午11: 32
 * UEditor编辑器通用上传类
 */
class Uploader
{
    private $fileField; //文件域名
    private $file; //文件上传对象
    private $base64; //文件上传对象
    private $config; //配置信息
    private $oriName; //原始文件名
    private $fileName; //新文件名
    private $fullName; //完整文件名,即从当前配置目录开始的URL
    private $filePath; //完整文件名,即从当前配置目录开始的URL
    private $fileSize; //文件大小
    private $fileType; //文件类型
    private $stateInfo; //上传状态信息,
    //上传状态映射表，国际化用户需考虑此处数据的国际化
    private $stateMap = array(
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_TMP_FILE"           => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED"        => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED"   => "文件类型不允许",
        "ERROR_CREATE_DIR"         => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE"  => "目录没有写权限",
        "ERROR_FILE_MOVE"          => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND"     => "找不到上传文件",
        "ERROR_WRITE_CONTENT"      => "写入文件内容错误",
        "ERROR_UNKNOWN"            => "未知错误",
        "ERROR_DEAD_LINK"          => "链接不可用",
        "ERROR_HTTP_LINK"          => "链接不是http链接",
        "ERROR_HTTP_CONTENTTYPE"   => "链接contentType不正确",
        "INVALID_URL"              => "非法 URL",
        "INVALID_IP"               => "非法 IP"
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config 配置项
     * @param bool $base64 是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($fileField, $config, $type = "upload")
    {
        $this->fileField = $fileField;
        $this->config    = $config;
        $this->type      = $type;
        if ($type == "remote") {
            $this->saveRemote();
        } else if ($type == "base64") {
            $this->upBase64();
        } else {
            $this->upFile();
        }

        // Fix PHP 7.1 compatibility - remove iconv call that may cause issues
        $this->stateMap['ERROR_TYPE_NOT_ALLOWED'] = $this->stateMap['ERROR_TYPE_NOT_ALLOWED'];
    }

    /**
     * 上传文件的主处理方法
     * @return mixed
     */
    private function upFile()
    {
        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo($file['error']);
            return;
        } else if (!file_exists($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMPFILE");
            return;
        }

        $this->oriName  = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname        = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        //创建目录失败 - 使用更安全的权限
        if (!file_exists($dirname) && !mkdir($dirname, 0755, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        //移动文件
        if (!(move_uploaded_file($file["tmp_name"], $this->filePath) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
        } else { //移动成功
            // 二次验证文件大小 - 防止上传过程中的篡改
            $actualSize = filesize($this->filePath);
            if ($actualSize !== $this->fileSize || $actualSize > $this->config["maxSize"]) {
                unlink($this->filePath); // 删除不符合要求的文件
                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
                return;
            }
            $this->stateInfo = $this->stateMap[0];
        }
    }

    /**
     * 处理base64编码的图片上传 - 增强安全验证
     * @return mixed
     */
    private function upBase64()
    {
        $base64Data = $_POST[$this->fileField];

        // 验证Base64数据格式
        if (empty($base64Data)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }

        $img = base64_decode($base64Data);

        // 验证解码是否成功
        if ($img === false) {
            $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN");
            return;
        }

        // 基于实际内容确定文件类型，而非配置文件
        $actualType = $this->detectFileTypeFromContent($img);
        if (!$actualType) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        $this->oriName  = $this->config['oriName'];
        $this->fileSize = strlen($img);

        // 强制使用检测到的实际文件类型
        $this->fileType = $actualType;

        // 验证Base64解码后的文件内容
        if (!$this->validateBase64Content($img, $this->fileType)) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname        = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //检查文件类型
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        //创建目录失败 - 使用更安全的权限
        if (!file_exists($dirname) && !mkdir($dirname, 0755, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            // 二次验证文件大小 - Base64上传
            $actualSize = filesize($this->filePath);
            if ($actualSize !== $this->fileSize || $actualSize > $this->config["maxSize"]) {
                unlink($this->filePath); // 删除不符合要求的文件
                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
                return;
            }
            $this->stateInfo = $this->stateMap[0];
        }

    }

    /**
     * 拉取远程图片
     * @return mixed
     */
    private function saveRemote()
    {
        $imgUrl = htmlspecialchars($this->fileField);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);

        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return;
        }

        preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
        $host_with_protocol = count($matches) > 1 ? $matches[1] : '';

        // 判断是否是合法 url
        if (!filter_var($host_with_protocol, FILTER_VALIDATE_URL)) {
            $this->stateInfo = $this->getStateInfo("INVALID_URL");
            return;
        }

        preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
        $host_without_protocol = count($matches) > 1 ? $matches[1] : '';

        // 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
        $ip = gethostbyname($host_without_protocol);
        // 判断是否是私有 ip
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            $this->stateInfo = $this->getStateInfo("INVALID_IP");
            return;
        }

        //获取请求头并检测死链
        $heads = get_headers($imgUrl, 1);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
            return;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) || !isset($heads['Content-Type']) || !stristr($heads['Content-Type'], "image")) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
            return;
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $this->oriName  = $m ? $m[1] : "";
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname        = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //创建目录失败 - 使用更安全的权限
        if (!file_exists($dirname) && !mkdir($dirname, 0755, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            // 二次验证文件大小 - 远程文件上传
            $actualSize = filesize($this->filePath);
            if ($actualSize !== $this->fileSize || $actualSize > $this->config["maxSize"]) {
                unlink($this->filePath); // 删除不符合要求的文件
                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
                return;
            }
            $this->stateInfo = $this->stateMap[0];
        }

    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 获取文件扩展名 - 增强安全过滤
     * @return string
     */
    private function getFileExt()
    {
        $fileName = basename($this->oriName);
        $ext      = strtolower(strrchr($fileName, '.'));

        // 防止双扩展名攻击，如 .php.jpg
        $parts = explode('.', $fileName);
        if (count($parts) > 2) {
            // 检查是否包含危险扩展名
            $dangerousExts = ['.php', '.php3', '.php4', '.php5', '.phtml', '.asp', '.aspx', '.jsp', '.jspx', '.cgi', '.pl', '.py', '.sh', '.exe', '.bat', '.com', '.scr', '.vbs', '.js', '.jar', '.war'];
            for ($i = 0; $i < count($parts) - 1; $i++) {
                if (in_array('.' . strtolower($parts[$i]), $dangerousExts)) {
                    return '.dangerous';
                }
            }
        }

        return $ext;
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getFullName()
    {
        //替换日期事件
        $t      = time();
        $d      = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法字符,并替换文件名 - 增强安全过滤
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        // 增强的文件名过滤，防止路径遍历和脚本注入
        $oriName = preg_replace('/[^\w\-_\(\)\[\]\s\u4e00-\u9fa5]/u', '', $oriName);
        $oriName = str_replace(['../', './', '../', '.\\', '..\\'], '', $oriName);
        $oriName = trim($oriName);
        if (empty($oriName)) {
            $oriName = 'file';
        }
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();

        // 最终保存文件的后缀名安全验证
        $safeExt = $this->getSafeFileExtension($ext);
        return $format . $safeExt;
    }

    /**
     * 获取文件名
     * @return string
     */
    private function getFileName()
    {
        return substr($this->filePath, strrpos($this->filePath, '/') + 1);
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath()
    {
        $fullname = $this->fullName;
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        if (substr($fullname, 0, 1) != '/') {
            $fullname = '/' . $fullname;
        }

        return $rootPath . $fullname;
    }

    /**
     * 文件类型检测 - 增强安全验证
     * @return bool
     */
    private function checkType()
    {
        $ext = $this->getFileExt();

        // 检查扩展名是否在白名单中
        if (!in_array($ext, $this->config["allowFiles"])) {
            return false;
        }

        // 检查文件内容类型 (Magic Bytes 验证)
        if ($this->type == "upload" && $this->file) {
            $filePath = $this->file['tmp_name'];
        } else if ($this->type == "base64") {
            // For base64, we'll validate after decode
            return true;
        } else {
            return true;
        }

        if (!file_exists($filePath)) {
            return false;
        }

        return $this->validateFileContent($filePath, $ext);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return array(
            "state"    => $this->stateInfo,
            "url"      => $this->fullName,
            "title"    => $this->fileName,
            "original" => $this->oriName,
            "type"     => $this->fileType,
            "size"     => $this->fileSize
        );
    }

    /**
     * 验证文件内容类型 - Magic Bytes 验证
     * @param string $filePath 文件路径
     * @param string $ext 文件扩展名
     * @return bool
     */
    private function validateFileContent($filePath, $ext)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // 根据扩展名验证MIME类型
        $allowedMimes = array(
            '.jpg'  => array('image/jpeg', 'image/pjpeg'),
            '.jpeg' => array('image/jpeg', 'image/pjpeg'),
            '.png'  => array('image/png'),
            '.gif'  => array('image/gif'),
            '.bmp'  => array('image/bmp', 'image/x-ms-bmp'),
            '.mp4'  => array('video/mp4'),
            '.webm' => array('video/webm'),
            '.mov'  => array('video/quicktime'),
            '.avi'  => array('video/x-msvideo', 'video/avi'),
            '.mp3'  => array('audio/mpeg', 'audio/mp3'),
            '.wav'  => array('audio/wav', 'audio/x-wav'),
            '.pdf'  => array('application/pdf'),
            '.txt'  => array('text/plain'),
            '.doc'  => array('application/msword'),
            '.docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            '.xls'  => array('application/vnd.ms-excel'),
            '.xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            '.ppt'  => array('application/vnd.ms-powerpoint'),
            '.pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation'),
            '.rar'  => array('application/x-rar-compressed'),
            '.zip'  => array('application/zip', 'application/x-zip-compressed'),
            '.7z'   => array('application/x-7z-compressed')
        );

        if (isset($allowedMimes[$ext])) {
            return in_array($mimeType, $allowedMimes[$ext]);
        }

        return false;
    }

    /**
     * 验证Base64内容
     * @param string $content Base64解码后的内容
     * @param string $ext 预期的文件扩展名
     * @return bool
     */
    private function validateBase64Content($content, $ext)
    {
        // 创建临时文件进行验证
        $tmpFile = tempnam(sys_get_temp_dir(), 'ueditor_validation_');
        if (!$tmpFile) {
            return false;
        }

        file_put_contents($tmpFile, $content);
        $result = $this->validateFileContent($tmpFile, $ext);
        unlink($tmpFile);

        return $result;
    }

    /**
     * 根据文件内容检测真实文件类型
     * @param string $content 文件内容
     * @return string|false 返回文件扩展名或false
     */
    private function detectFileTypeFromContent($content)
    {
        // 创建临时文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'ueditor_detect_');
        if (!$tmpFile) {
            return false;
        }

        file_put_contents($tmpFile, $content);

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpFile);
        finfo_close($finfo);

        unlink($tmpFile);

        // MIME类型到扩展名的映射
        $mimeToExt = array(
            'image/jpeg'      => '.jpg',
            'image/png'       => '.png',
            'image/gif'       => '.gif',
            'image/bmp'       => '.bmp',
            'video/mp4'       => '.mp4',
            'video/webm'      => '.webm',
            'audio/mpeg'      => '.mp3',
            'audio/wav'       => '.wav',
            'application/pdf' => '.pdf',
            'text/plain'      => '.txt'
        );

        // 仅返回Base64上传允许的安全类型
        $allowedForBase64 = array('.jpg', '.png', '.gif', '.bmp');

        if (isset($mimeToExt[$mimeType]) && in_array($mimeToExt[$mimeType], $allowedForBase64)) {
            return $mimeToExt[$mimeType];
        }

        return false;
    }

    /**
     * 获取安全的文件扩展名 - 最终保存验证
     * @param string $ext 原始扩展名
     * @return string 安全的扩展名
     */
    private function getSafeFileExtension($ext)
    {
        // 定义安全的扩展名白名单
        $safeExtensions = array(
            // 图片
            '.jpg', '.jpeg', '.png', '.gif', '.bmp',
            // 视频
            '.mp4', '.webm', '.mov', '.avi',
            // 音频
            '.mp3', '.wav',
            // 文档
            '.pdf', '.txt', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
            // 压缩文件
            '.rar', '.zip', '.7z'
        );

        $ext = strtolower(trim($ext));

        // 检查是否在安全列表中
        if (!in_array($ext, $safeExtensions)) {
            // 如果不安全，根据配置返回默认安全扩展名
            if (isset($this->config['allowFiles']) && !empty($this->config['allowFiles'])) {
                return $this->config['allowFiles'][0]; // 返回配置中第一个允许的扩展名
            }
            return '.txt'; // 默认返回安全的文本扩展名
        }

        // 再次检查是否在当前配置的allowFiles中
        if (isset($this->config['allowFiles']) && !in_array($ext, $this->config['allowFiles'])) {
            return $this->config['allowFiles'][0];
        }

        return $ext;
    }

}