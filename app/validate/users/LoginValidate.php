<?php

namespace app\validate\users;

use app\http\response\api\StatusCode;
use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        'mobile' => 'require',
        'password' => 'require',
        'pay_password' => 'require|number|length:6',
        'verify_code' => 'require|captcha',
        'sms_code' => 'require',
        'code' => 'require',
        'iv' => 'require',
        'encryptedData' => 'require'
    ];
    protected $message = [
        'mobile.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '手机号不能为空'],
        'password.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '密码不能为空'],
        'verify_code.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '验证码不能为空'],
        'verify_code.captcha' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '验证码错误'],

        'pay_password.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '交易密码不能为空'],
        'pay_password.number' => ['code' => -1, 'msg' => '交易密码只能是数字'],
        'pay_password.length' => ['code' => -1, 'msg' => '交易密码必须是6位数'],
        'sms_code.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '验证码不能为空'],

        'code.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '微信参数错误'],
        'iv.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '微信参数错误'],
        'encryptedData.require' => ['code' => StatusCode::REQUEST_PARAM_ERROR, 'msg' => '微信参数错误']
    ];
    protected $scene = [
        'captchaRegister' => ['mobile', 'password', 'pay_password','sms_code','user_code'],
        'captchaLogin' => ['mobile', 'password'],
        'wechatLogin' => ['code', 'iv', 'encryptedData', 'signature'],
        'smsLogin' => ['mobile', 'sms_code'],
        'transfer' => ['mobile'],
        'nosendRegister' => ['mobile', 'password','verify_code','user_code'],
        'register' => ['mobile', 'password','pay_password'],
    ];
}