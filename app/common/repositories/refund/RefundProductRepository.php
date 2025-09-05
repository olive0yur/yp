<?php

namespace app\common\repositories\refund;

use app\common\dao\refund\RefundProductDao;
use app\common\repositories\BaseRepository;

class RefundProductRepository extends BaseRepository
{

    public function __construct(RefundProductDao $dao)
    {
        $this->dao = $dao;
    }
}