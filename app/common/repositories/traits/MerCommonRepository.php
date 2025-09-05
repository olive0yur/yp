<?php

namespace app\common\repositories\traits;

trait MerCommonRepository
{

    public function merExists(int $companyId, $merId = 0, int $id, $except = null)
    {
        return true;
        return $this->dao->merFieldExists($companyId, $merId, $this->getPk(), $id, $except);
    }

    public function merDeleteExists(int $companyId, int $merId, int $id)
    {
        return $this->dao->getDeleteExists($companyId, $merId, $id);
    }
}