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

namespace app\common\trait;

use think\App;
use think\Response;
use think\Request;
use think\exception\HttpResponseException;

/**
 * 跳转类
 * 代码来源：https://github.com/liliuwei/thinkphp-jump
 * 代码修改：黑白蓝
 */
trait Jump
{
    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * Request实例
     * @var Request
     */
    protected Request $request;

    /**
     * 默认状态码
     * @var int
     */
    private int $httpCode = 200;

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param mixed $msg 提示信息
     * @param mixed $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param integer|null $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function success(mixed $msg = '', mixed $url = null, mixed $data = '', int $wait = null, array $header = []): void
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || str_starts_with($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }

        $result = [
            'code' => $this->app->config->get('jump.default_success_code', 1),
            'msg'  => lang($msg),
            'data' => $data,
            'url'  => $url,
            'wait' => null === $wait ? $this->app->config->get('jump.default_success_wait', 3) : $wait,
        ];

        $type = $this->getResponseType();
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $response = Response::create($this->app->config->get('jump.dispatch_success_tmpl'), 'view')
                ->code($this->httpCode)
                ->assign($result)
                ->header($header);
        } else {
            $response = Response::create($result, $type)->code($this->httpCode)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param mixed $msg 提示信息
     * @param mixed $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param integer|null $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function error(mixed $msg = '', mixed $url = null, mixed $data = '', int $wait = null, array $header = []): void
    {
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || str_starts_with($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }

        $result = [
            'code' => $this->app->config->get('jump.default_error_code', 0),
            'msg'  => lang($msg),
            'data' => $data,
            'url'  => $url,
            'wait' => null === $wait ? $this->app->config->get('jump.default_error_wait', 5) : $wait,
        ];

        $type = $this->getResponseType();

        if ('html' == strtolower($type)) {
            $response = Response::create($this->app->config->get('jump.dispatch_error_tmpl'), 'view')
                ->code($this->httpCode)
                ->assign($result)
                ->header($header);
        } else {
            $response = Response::create($result, $type)->code($this->httpCode)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 表格错误提示
     * @param string $msg
     * @return mixed
     */
    protected function tableError(string $msg = ''): mixed
    {
        $response = Response::create([
            'code' => 1,
            'msg'  => $msg ?: '操作失败',
        ], 'json');
        throw new HttpResponseException($response);
    }

    /**
     * 弹出模态框
     * @access protected
     * @param array $options Modal 配置选项
     *   - title: string 标题（可选）
     *   - content: string 内容（HTML 字符串或纯文本）
     *   - size: string 尺寸：'sm'（小）、'lg'（大）、'xl'（超大）、'full'（全宽）、''（默认）
     *   - status: string 状态颜色：'success'、'danger'、'warning'、'info'
     *   - footer: string 底部按钮 HTML
     *   - scrollable: bool 是否可滚动
     *   - centered: bool 是否垂直居中
     *   - blur: bool 是否显示模糊背景
     *   - closeButton: bool 是否显示关闭按钮
     *   - backdrop: bool|string 背景遮罩（true/false/'static'）
     *   - keyboard: bool 是否允许 ESC 键关闭
     * @param int $code 返回的code（1=成功，0=失败）
     * @param string $msg 提示信息（可选，会在 modal 显示前显示 toast）
     * @param int|null $wait Toast 显示时间
     * @return void
     */
    protected function modal(array $options, int $code = 1, string $msg = '', int $wait = null): void
    {
        $result = [
            'code' => $code,
            'msg'  => lang($msg),
            'data' => [
                'modal' => $options
            ],
            'wait' => null === $wait ? 0 : $wait,
        ];

        $type     = $this->getResponseType();
        $response = Response::create($result, $type)->code($this->httpCode);

        throw new HttpResponseException($response);
    }

    /**
     * 快捷方法：状态提示 Modal（简化版，HTML 由前端生成）
     * @access protected
     * @param string $title 标题
     * @param string $content 内容
     * @param string $status 状态：'success'、'danger'、'warning'、'info'；
     * @param string $size 尺寸：'sm'、''、'lg'、'xl'、'full'，默认 'sm'
     * @param string $buttonText 按钮文字，默认根据状态自动设置
     * @param string $msg Toast 提示消息（可选）
     * @param int|null $wait Toast 显示时间
     * @return void
     */
    protected function modalAlert(string $title, string $content, string $status = 'info', string $size = 'sm', string $buttonText = '', string $msg = '', int $wait = null): void
    {
        $code = $status === 'danger' ? 0 : 1;
        $this->_sendModalResponse('modalAlert', [$title, $content, $status, $size, $buttonText], $code, $msg, $wait);
    }

    /**
     * 快捷方法：成功提示 Modal（简化版，HTML 由前端生成）
     * @access protected
     * @param string $title 标题
     * @param string $content 内容描述
     * @param string $buttonText 按钮文字，默认"确定"
     * @param string $msg Toast 提示消息
     * @param int $wait Toast 显示时间
     * @return void
     */
    protected function modalSuccess(string $title, string $content = '', string $buttonText = '确定', string $msg = '', int $wait = 2): void
    {
        $this->_sendModalResponse('modalSuccess', [$title, $content, $buttonText], 1, $msg, $wait);
    }

    /**
     * 快捷方法：错误提示 Modal（简化版，HTML 由前端生成）
     * @access protected
     * @param string $title 标题
     * @param string $content 内容描述
     * @param string $buttonText 按钮文字，默认"知道了"
     * @param string $msg Toast 提示消息
     * @param int $wait Toast 显示时间
     * @return void
     */
    protected function modalError(string $title, string $content = '', string $buttonText = '知道了', string $msg = '', int $wait = 2): void
    {
        $this->_sendModalResponse('modalError', [$title, $content, $buttonText], 0, $msg, $wait);
    }

    /**
     * 快捷方法：警告提示 Modal（简化版，HTML 由前端生成）
     * @access protected
     * @param string $title 标题
     * @param string $content 内容描述
     * @param string $buttonText 按钮文字，默认"我知道了"
     * @param string $msg Toast 提示消息
     * @param int $wait Toast 显示时间
     * @return void
     */
    protected function modalWarning(string $title, string $content = '', string $buttonText = '我知道了', string $msg = '', int $wait = 0): void
    {
        $this->_sendModalResponse('modalWarning', [$title, $content, $buttonText], 1, $msg, $wait);
    }

    /**
     * 快捷方法：信息提示 Modal（简化版，HTML 由前端生成）
     * @access protected
     * @param string $title 标题
     * @param string $content 内容描述
     * @param string $buttonText 按钮文字，默认"确定"
     * @param string $msg Toast 提示消息
     * @param int $wait Toast 显示时间
     * @return void
     */
    protected function modalInfo(string $title, string $content = '', string $buttonText = '确定', string $msg = '', int $wait = 0): void
    {
        $this->_sendModalResponse('modalInfo', [$title, $content, $buttonText], 1, $msg, $wait);
    }

    /**
     * 快捷方法：详细信息展示 Modal（支持自定义 HTML）
     * @access protected
     * @param string $title 标题
     * @param string $content HTML 内容
     * @param string $size 尺寸：'sm'、''、'lg'、'xl'、'full'，默认 'lg'
     * @param string $footer 底部按钮 HTML（可选）
     * @param bool $scrollable 是否可滚动，默认 false
     * @return void
     */
    protected function modalDetail(string $title, string $content, string $size = 'lg', string $footer = '', bool $scrollable = false): void
    {
        $this->_sendModalResponse('modalDetail', [$title, $content, $size, $footer, $scrollable], 1, '', 0);
    }

    /**
     * 快捷方法：确认对话框 Modal
     * @access protected
     * @param string $title 标题
     * @param string $content 内容描述
     * @param string $confirmUrl 确认按钮的 URL（前端会将此 URL 包装成 AJAX 调用）
     * @param string $confirmText 确认按钮文字，默认"确认"
     * @param string $cancelText 取消按钮文字，默认"取消"
     * @param string $status 状态：'danger'、'warning'、'info'，默认 'warning'
     * @param string $msg Toast 提示消息（可选）
     * @param int $wait Toast 显示时间
     * @return void
     */
    protected function modalConfirm(string $title, string $content, string $confirmUrl, string $confirmText = '确认', string $cancelText = '取消', string $status = 'warning', string $msg = '', int $wait = 0): void
    {
        $code = $status === 'danger' ? 0 : 1;
        $this->_sendModalResponse('modalConfirm', [$title, $content, $confirmUrl, $confirmText, $cancelText, $status], $code, $msg, $wait);
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param integer $code 返回的code
     * @param mixed $msg 提示信息
     * @param string $type 返回数据格式
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function result(mixed $data, int $code = 0, mixed $msg = '', string $type = '', array $header = []): void
    {
        $result = [
            'code' => $code,
            'msg'  => lang($msg),
            'time' => time(),
            'data' => $data,
        ];

        $type     = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->code($this->httpCode)->header($header);

        throw new HttpResponseException($response);
    }

    /**
     * URL重定向
     * @access protected
     * @param mixed $url 跳转的URL表达式
     * @param integer $code http code
     * @param array $with 隐式传参
     * @return void
     */
    protected function redirect(mixed $url, int $code = 302, array $with = []): void
    {
        $response = Response::create($url, 'redirect');

        $response->code($code)->with($with);

        throw new HttpResponseException($response);
    }

    /**
     * 指定HTTP状态码
     * @param int $code
     * @return $this
     */
    protected function withCode(int $code = 200): static
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * 发送 Modal 响应（私有方法，用于减少重复代码）
     * @access private
     * @param string $method 前端调用的方法名
     * @param array $params 方法参数
     * @param int $code 返回码（1=成功，0=失败）
     * @param string $msg Toast 提示消息
     * @param int|null $wait Toast 显示时间
     * @return void
     */
    private function _sendModalResponse(string $method, array $params, int $code, string $msg, int|null $wait): void
    {
        $type = $this->getResponseType();

        // 非 AJAX 场景降级为普通跳转提示，避免 HTML 响应承载数组数据时报错
        if ('html' === strtolower($type)) {
            $title   = trim(strip_tags((string)($params[0] ?? '')));
            $content = trim(strip_tags((string)($params[1] ?? '')));
            $message = trim($msg);

            if ($message === '') {
                $message = $title !== '' ? $title : ($code === 1 ? '操作成功' : '操作失败');
            }

            if ($content !== '') {
                $message .= '：' . $content;
            }

            if ($code === 1) {
                $this->success($message, null, '', $wait);
            }

            $this->error($message, null, '', $wait);
        }

        $result = [
            'code' => $code,
            'msg'  => lang($msg),
            'data' => [
                'modal' => [
                    'method' => $method,
                    'params' => $params
                ]
            ],
            'wait' => null === $wait ? 0 : $wait,
        ];

        $response = Response::create($result, $type)->code($this->httpCode);

        throw new HttpResponseException($response);
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType(): string
    {
        if ($this->request->isJson() || $this->request->isAjax()) {
            return $this->app->config->get('jump.default_ajax_return', 'json');
        } else {
            return $this->app->config->get('jump.default_return_type', 'html');
        }
    }
}
