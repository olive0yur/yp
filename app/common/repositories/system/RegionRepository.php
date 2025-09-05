<?php

namespace app\common\repositories\system;

use app\common\dao\system\RegionDao;
use app\common\repositories\BaseRepository;

class RegionRepository extends BaseRepository
{

    public function __construct(RegionDao $dao)
    {
        $this->dao = $dao;
    }

    public function getChildren($pid, $chooseIds = [])
    {
        $data = $this->dao->search(['pid' => $pid])->select();
        if ($chooseIds) {
            foreach ($data as $k => $v) {
                if (in_array($v['id'], $chooseIds)) {
                    $v['child'] = $this->getChildren($v['id'], $chooseIds);
                }
            }
        }
        return $data;
    }
}