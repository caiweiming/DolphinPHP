<?php
declare(strict_types=1);

namespace app\common\trait;

use think\exception\ValidateException;
use think\Validate;
use Throwable;

/**
 * 控制器请求验证能力复用 trait
 *
 * @since 1.0.0
 */
trait ValidatesRequest
{
    /**
     * 验证数据
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false): bool|array|string
    {
        if (is_array($validate)) {
            $validator = new Validate();
            $validator->rule($validate);
        } else {
            $scene = '';
            if (str_contains($validate, '.')) {
                [$validate, $scene] = explode('.', $validate, 2);
            }

            $class = str_contains($validate, '\\')
                ? $validate
                : $this->app->parseClass('validate', $validate);
            $validator = new $class();
            if ($scene !== '') {
                $validator->scene($scene);
            }
        }

        $validator->message($message);

        if ($batch || $this->batchValidate) {
            $validator->batch();
        }

        return $validator->failException()->check($data);
    }

    /**
     * 自动验证
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $data 数据
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return true
     * @throws Throwable
     */
    protected function autoValidate(string|array $validate = '', array $data = [], array $message = [], bool $batch = false): bool
    {
        $resolvedValidate = $validate === '' ? $this->request->controller() : $validate;
        $resolvedData = $data === [] ? $this->request->param() : $data;

        try {
            $this->validate($resolvedData, $resolvedValidate, $message, $batch);
        } catch (ValidateException $e) {
            $this->handleValidateException($e, $resolvedValidate, $resolvedData);
        }

        return true;
    }

    /**
     * 处理验证异常，默认直接走错误响应
     * @param ValidateException $exception
     * @param string|array $validate
     * @param array $data
     * @return void
     */
    protected function handleValidateException(ValidateException $exception, string|array $validate, array $data): void
    {
        $this->error($exception->getError());
    }
}
