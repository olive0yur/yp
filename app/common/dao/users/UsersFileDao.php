<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersFileModel;

class UsersFileDao extends BaseDao
{

    /**
     * @return UsersFileModel
     */
    protected function getModel(): string
    {
        return UsersFileModel::class;
    }
}
