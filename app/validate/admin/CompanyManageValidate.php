<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2020 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace app\validate\admin;

use think\Validate;
use app\model\system\admin\AdminUser as AdminUserModel;

class CompanyManageValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'username' => ['require'],
        'name' => ['require'],
        'password' => ['require', 'min' => 6, 'max' => 20],
        'address' => ['require'],
        'mobile' => 'mobile'
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'username.require' => ['code' => -1, 'msg' => '联系人不能为空'],
        'name.require' => ['code' => -1, 'msg' => '公司名称不能为空'],
        'password.require' => ['code' => -1,'msg' =>'密码不能为空'],
        'password.min' => ['code' => -1, 'msg' => '密码不能少于6个字符'],
        'password.max' => ['code' => -1, 'msg' => '密码不能超过20个字符'],
        'address.require' => ['code' => -1, 'msg' => '公司地址不能为空'],
        'mobile.require' => ['code' => -1, 'msg' => '手机号不能为空'],
        'mobile.mobile' => ['code' => -1, 'msg' => '手机号格式错误'],
        'ids.require' => ['code' => -1, 'msg' => 'ids不能为空'],
        'ids.array' => ['code' => -1, 'msg' => 'ids只能为数组'],
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneEdit(){
        return $this->remove('password','require|min:6|max:20');
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }

    public function sceneSelfEdit()
    {
        return $this->only(['mobile', 'address'])->append('address', 'require')->append('mobile', 'require');
    }
}