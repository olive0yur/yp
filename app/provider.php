<?php

use app\ExceptionHandle;
use app\Request;

// 容器Provider定义文件
return [
    'think\Request' => Request::class,
    'think\exception\Handle' => \app\exception\Http::class,
    'api_return' => app\http\response\api\ReturnCode::class,
    'think\middleware\SessionInit' => \app\http\middleware\SessionInit::class
];
