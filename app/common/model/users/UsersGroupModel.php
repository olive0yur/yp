<?php

namespace app\common\model\users;

use app\common\model\BaseModel;

class UsersGroupModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_group';
    }

}
