<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersFileDao;
use app\common\repositories\BaseRepository;

class UsersFileRepository extends BaseRepository
{

    public function __construct(UsersFileDao $dao)
    {
        $this->dao = $dao;
    }
}