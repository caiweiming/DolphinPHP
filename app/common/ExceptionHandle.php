<?php
namespace app\common;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\db\exception\PDOException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use think\Request;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * http 错误码
     * @var int
     */
    private int $status = 200;

    /**
     * 自定义错误码
     * @var int
     */
    private int $code = 0;

    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render(Request $request, Throwable $e): Response
    {
        // 不开启调试模式的情况下，返回json格式的报错信息
        if (!env('app_debug')) {
            // PDO异常
            if ($e instanceof PDOException) {
                return json([
                    'code' => $this->code,
                    'msg'  => $e->getMessage(),
                ], 500);
            }

            // 验证器错误
            if ($e instanceof ValidateException) {
                return json([
                    'code' => $this->code,
                    'msg'  => lang($e->getError()),
                    'data' => []
                ], $this->status);
            }
        }

        // 请求异常
        if ($e instanceof HttpException && $request->isAjax()) {
            return response($e->getMessage(), $e->getStatusCode());
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
