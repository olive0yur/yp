<?php

namespace app\validate\users;

use think\Validate;

class UsersCertValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'number' => 'require|idCard',
        'username' => ['require'],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id格式错误'],
        'number.idCard' => ['code' => -1, 'msg' => '身份证格式错误'],
        'number.require' => ['code' => -1, 'msg' => '身份证不能为空'],
        'username.require' => ['code' => -1, 'msg' => '姓名不能为空'],
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneEdit()
    {
        return $this->remove('id', 'require');
    }

    public function sceneExamine()
    {
        return $this->only(['cert_status', 'remark'])
            ->append([
                'cert_status' => 'require|in:2,3',
                'remark' => 'requireIf:cert_status,3',
            ])->message([
                'cert_status' => '审核类型错误',
                'remark.requireIf' => '拒绝原因不能为空',
            ]);
    }
}