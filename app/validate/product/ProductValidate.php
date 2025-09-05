<?php

namespace app\validate\product;

use think\Validate;

class ProductValidate extends Validate
{
    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require'],
//        'product_type' => ['require', 'number']
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'title.require' => ['code' => -1, 'msg' => '商品名称不能为空'],
        'product_type.require' => ['code' => -1, 'msg' => '请选择商品类型'],
//        'product_type.number' => ['code' => -1, 'msg' => '商家分类格式错误'],
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

}