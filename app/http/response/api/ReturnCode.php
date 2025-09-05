<?php

namespace app\http\response\api;

use think\Response;

class ReturnCode
{
    /**
     * 成功返回
     *
     * @param array $data 要返回的数据
     * @param string $message 提示信息
     */
    public static function success($data = [], $message = 'ok')
    {
        $responseData = [
            'code' => StatusCode::SUCCESS,
            'msg' => $message,
            'data' => []
        ];
        if ($data) {
            $responseData['data'] = $data;
        }
        return Response::create($responseData, 'json');
    }

    /**
     * 成功返回文字
     *
     * @param string $message 提示信息
     */
    public static function successText($message = 'ok')
    {
        $responseData = [
            'code' => StatusCode::SUCCESS,
            'msg' => $message
        ];
        return Response::create($responseData, 'json');
    }

    /**
     * 错误返回
     *
     * @param string $message 错误信息
     * @param int $code 错误码
     */
    public static function error($message = '', $code = 500)
    {
        $httpCode = 200;
        if ($code == 1103 || $code == 1104) {
            $httpCode = 403;
        }
        return Response::create([
            'code' => $code,
            'msg' => $message
        ], 'json', $httpCode);
    }
}