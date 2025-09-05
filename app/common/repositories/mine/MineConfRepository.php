<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineConfDao;
use app\common\dao\mine\MineDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;

/**
 * Class MineRepository
 * @package app\common\repositories\MineRepository
 * @mixin MineDao
 */
class MineConfRepository extends BaseRepository
{

    public function __construct(MineConfDao $dao)
    {
        $this->dao = $dao;
    }





    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }
}