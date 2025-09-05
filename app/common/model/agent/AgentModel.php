<?php

namespace app\common\model\agent;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class AgentModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'agent';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

}
