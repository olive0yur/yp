<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysGearCateModel;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysGearCateDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ToysGearCateModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
        })

       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysGearCateModel::class;
    }




}
