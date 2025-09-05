<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersSecurityDao;
use app\common\repositories\BaseRepository;

class UsersSecurityRepository extends BaseRepository
{

    public function __construct(UsersSecurityDao $dao)
    {
        $this->dao = $dao;
    }
}