<?php

namespace app\http\response\api;

class StatusCode
{
    // 处理成功
    const SUCCESS = 200;
    // 请求参数错误
    const REQUEST_PARAM_ERROR = 400;
    // 服务器错误
    const ERROR = 500;
    // 请先登陆
    const LOGIN_CODE = 1001;
    // 微信登录失败
    const WECHAT_LOGIN_FAILED = 1105;
    // 绑定微信手机号
    const BIND_WECHAT_PHONE = 1106;
    // 微信登录失败并重试
    const WECHAT_LOGIN_FAILED_RETRY = 1107;
}