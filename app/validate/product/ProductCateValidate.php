<?php

namespace app\validate\product;

use think\Validate;

class ProductCateValidate extends Validate
{
    protected $rule = [
        'id' => ['require', 'number'],
        'cate_name' => ['require'],
        'pid' => ['require', 'number', 'different:id']
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'pid' => ['code' => -1, 'msg' => '请选择上级分类'],
        'pid.different' => ['code' => -1, 'msg' => '上级分类不能选择自己'],
        'cate_name.require' => ['code' => -1, 'msg' => '分类名称不能为空']
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require')->remove('pid', 'different');
    }

    public function sceneDel()
    {
        return $this->only(['id'])->append('id', 'require|number');
    }

}