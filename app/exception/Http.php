<?php

namespace app\exception;

use api\sms\exception\SmsException;
use api\upload\exception\UploadException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ParseErrorException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class Http extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        ApiException::class,
        UploadException::class
    ];

    public function render($request, Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            if (is_array($e->getError())) {
                return json()->data($e->getError())->code(200);
            } else {
                return json()->data([
                    'code' => 400,
                    'msg' => $e->getMessage()
                ])->code(200);
            }
        }

        // 短信验证错误
        if ($e instanceof SmsException) {
            return json()->data([
                'code' => 400,
                'msg' => $e->getMessage()
            ])->code(200);
        }

        // 上传错误
        if ($e instanceof UploadException) {
            return json()->data([
                'code' => 400,
                'msg' => $e->getMessage()
            ])->code(200);
        }

        // api错误
        if ($e instanceof ApiException) {
            return json()->data([
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ])->code(200);
        }
        dump($e);
        exception_log('请求错误', $e);
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

}