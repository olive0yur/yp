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
namespace app\validate\shop;

use think\Validate;

class ShopValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'title' => ['require'],
        'num' => 'require|number',
        'cover' =>  ['require'],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'title.require' => ['code' => -1, 'msg' => '卡牌名称不能为空'],
        'price.require' => ['code' => -1, 'msg' => '价格不能为空'],
        'num.require' => ['code' => -1, 'msg' => '发行量不能为空'],
        'num.number' => ['code' => -1, 'msg' => '发行量只能是数字'],
        'cover.require' => ['code' => -1, 'msg' => '封面图不能为空'],
        'ids.require' => ['code' => -1, 'msg' => 'ids不能为空'],
        'ids.array' => ['code' => -1, 'msg' => 'ids只能为数组'],
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