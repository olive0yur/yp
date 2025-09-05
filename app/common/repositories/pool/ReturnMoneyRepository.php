<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolBrandDao;
use app\common\dao\pool\ReturnMoneyDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class ReturnMoneyRepository
 * @package app\common\repositories\pool
 * @mixin ReturnMoneyDao
 */
class ReturnMoneyRepository extends BaseRepository
{

    public function __construct(ReturnMoneyDao $dao)
    {
        $this->dao = $dao;
    }

    public function editInfo($info,$data){
        return $this->dao->update($info['id'], $data);
    }

    public function addInfo($data){
        return $this->dao->create($data);
    }
}