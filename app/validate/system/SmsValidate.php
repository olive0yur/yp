<?php

namespace app\validate\system;

use think\Validate;

class SmsValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'sms_type' => ['require', 'number'],
        'template_id' => 'require',
        'content' => 'require'
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'sms_type.require' => ['code' => -1, 'msg' => '请选择短信类型'],
        'sms_type.number' => ['code' => -1, 'msg' => '短信类型格式错误'],
        'template_id.require' => ['code' => -1, 'msg' => '模板ID不能为空'],
        'content.require' => ['code' => -1, 'msg' => '短信内容不能为空'],
        'ids.require' => ['code' => -1, 'msg' => 'ids不能为空'],
        'ids.array' => ['code' => -1, 'msg' => 'ids只能为数组']
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }

    public function sceneTest()
    {
        return $this->only(['id'])->append('id', 'require|number');
    }
}