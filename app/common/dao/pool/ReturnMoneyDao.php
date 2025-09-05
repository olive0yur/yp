<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\pool\PoolBrandModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\pool\ReturnMoneyModel;
use think\db\BaseQuery;

class ReturnMoneyDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId= null)
    {
        $query = ReturnMoneyModel::getDB()
        ;
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return ReturnMoneyModel::class;
    }


}
