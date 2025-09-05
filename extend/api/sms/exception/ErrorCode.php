<?php

namespace api\sms\exception;

class ErrorCode
{
    // 错误码
    public static $code = [
        '1001' => 'Parameter error',
        '1002' => 'Failed to send',
        '1003' => 'Verification failed'
    ];

    // 参数错误码
    const PARAM_ERROR = 1001;
    // 发送失败
    const SEND_FAILED = 1002;
    // 验证失败
    const VERIFY_FAILED = 1003;
}
