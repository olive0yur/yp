<?php

namespace app\validate\users;

use think\Validate;

class UsersValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        // 'mobile' => ['require', 'mobile'],
        'password' => ['require', 'min' => 6, 'max' => 20],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id格式错误'],
        // 'mobile.require' => ['code' => -1, 'msg' => '登录账号手机号不能为空'],
        'mobile.mobile' => ['code' => -1, 'msg' => '登录账号手机号格式错误'],
        'password.require' => ['code' => -1, 'msg' => '密码不能为空'],
        'password.min' => ['code' => -1, 'msg' => '密码不能少于6个字符'],
        'password.max' => ['code' => -1, 'msg' => '密码不能超过20个字符'],
        'repassword' => ['code' => -1, 'msg' => '两次密码输入不一致'],
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneEdit()
    {
        return $this->remove('password', 'require');
    }

    public function sceneModifyPhone()
    {
        return $this->only(['mobile']);
    }

    public function sceneEditPassword()
    {
        return $this->only(['password', 'repassword'])->append([
            'repassword' => ['require','confirm' => 'password']
        ]);
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }

    public function sceneSelfEdit()
    {
        return $this->only(['mobile'])->append('mobile', 'require');
    }

}