<?php

namespace app\validate\product;

use think\Validate;

class ProductSpecTemplateValidate extends Validate
{
    protected $rule = [
        'id' => ['require', 'number'],
        'spec_name' => ['require'],
        'spec_value' => ['require'],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'spec_name.require' => ['code' => -1, 'msg' => '规格名称不能为空'],
        'spec_value.require' => ['code' => -1, 'msg' => '规格值不能为空']
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneDel()
    {
        return $this->only(['id'])->append('id', 'require|number');
    }

}