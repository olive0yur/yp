<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersSecurityModel;

class UsersSecurityDao extends BaseDao
{

    /**
     * @return UsersSecurityModel
     */
    protected function getModel(): string
    {
        return UsersSecurityModel::class;
    }

}
