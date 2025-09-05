<?php

namespace app\validate\admin;

use think\Validate;
use app\model\system\admin\AdminUser as AdminUserModel;

class AdminUserValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'account' => ['require'],
        'password' => ['require', 'min' => 6, 'max' => 20],
        'email' => 'email',
        'mobile' => 'mobile'
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'account.require' => ['code' => -1, 'msg' => '登录账号不能为空'],
        'password.require' => ['code' => -1, 'msg' => '登录密码不能为空'],
        'password.min' => ['code' => -1, 'msg' => '登录密码不能少于6个字符'],
        'password.max' => ['code' => -1, 'msg' => '登录密码不能超过20个字符'],
        'email.require' => ['code' => -1, 'msg' => '邮箱不能为空'],
        'email.email' => ['code' => -1, 'msg' => '邮箱格式错误'],
        'mobile.require' => ['code' => -1, 'msg' => '手机号不能为空'],
        'mobile.mobile' => ['code' => -1, 'msg' => '手机号格式错误'],
        'ids.require' => ['code' => -1, 'msg' => 'ids不能为空'],
        'ids.array' => ['code' => -1, 'msg' => 'ids只能为数组'],
        'old_password.require' => ['code' => -1, 'msg' => '请输入原密码'],
        're_password.require' => ['code' => -1, 'msg' => '请再次输入新密码'],
        're_password.confirm' => ['code' => -1, 'msg' => '两次新密码输入不一致']
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneEdit()
    {
        return $this->remove('password', 'require|min:6|max:20');
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }

    public function sceneSelfEdit()
    {
        return $this->only(['mobile', 'email'])->append('email', 'require')->append('mobile', 'require');
    }

    public function sceneEditPassword()
    {
        return $this->only(['password', 'old_password', 're_password'])->append('old_password', 'require')->append('re_password', 'require|confirm:password');
    }

}