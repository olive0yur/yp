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
namespace app\validate\system\affiche;

use think\Validate;

class AfficheCateValidate extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'name' => ['require'],
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'name.require' => ['code' => -1, 'msg' => '名称不能为空'],
        'ids.require' => ['code' => -1, 'msg' => 'ids不能为空'],
        'ids.array' => ['code' => -1, 'msg' => 'ids只能为数组'],
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

    public function sceneEdit()
    {
        return $this->remove('id', 'require');
    }

    public function sceneDel()
    {
        return $this->only(['ids'])->append('ids', 'require|array');
    }

}