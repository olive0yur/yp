<?php

namespace api\sms\exception;

use Throwable;

class SmsException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取错误码
     *
     * @return mixed|string
     */
    public function getErrorCode()
    {
        return ErrorCode::$code[$this->getCode()] ?? '';
    }

}