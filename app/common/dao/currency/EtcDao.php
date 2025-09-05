<?php

namespace app\common\dao\currency;

use app\common\dao\BaseDao;
use app\common\model\currency\EtcModel;
use think\db\BaseQuery;

class EtcDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = EtcModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
 ;

        return $query;
    }

    /**
     * @return EtcModel
     */
    protected function getModel(): string
    {
        return EtcModel::class;
    }



}
