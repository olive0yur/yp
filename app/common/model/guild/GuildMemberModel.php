<?php

namespace app\common\model\guild;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class GuildMemberModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'guild_member';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
    public function guild(){
        return $this->hasOne(GuildModel::class,'id','guild_id');
    }

}
