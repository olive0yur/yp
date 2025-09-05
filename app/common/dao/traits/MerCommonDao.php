<?php

namespace app\common\dao\traits;

trait MerCommonDao
{


    /**
     * 商家检测数据存不存在
     *
     * @param int $companyId
     * @param int $merId
     * @param $field
     * @param $value
     * @param $except
     * @return bool
     */
    public function merFieldExists(int $companyId, int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()
                ->when($except, function ($query, $except) use ($field) {
                    $query->where($field, '<>', $except);
                })
                ->where('company_id', $companyId)
                ->where('mer_id', $merId)
                ->where($field, $value)->count() > 0;
    }

    /**
     * @param int $companyId
     * @param int $merId
     * @param int $productId
     * @return bool
     */
    public function getDeleteExists(int $companyId, int $merId, int $productId)
    {
        return ($this->getModel())::onlyTrashed()
                ->where('company_id', $companyId)
                ->where('mer_id', $merId)
                ->where($this->getPk(), $productId)
                ->count() > 0;
    }
}