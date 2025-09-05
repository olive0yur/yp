<?php

namespace app\validate\product;

use think\Validate;

class ProductLabelValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'label_name' => ['require'],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'label_name.require' => ['code' => -1, 'msg' => '标签名称不能为空'],
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }
}