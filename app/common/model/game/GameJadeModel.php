<?php

namespace app\common\model\game;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class GameJadeModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'game_jade_log';
    }

    public function userInfo(){
        return $this->hasOne(UsersModel::class,'id','user_id');
    }

}
