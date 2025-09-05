<?php

namespace app\common\dao\active\draw;

use app\common\dao\BaseDao;
use app\common\model\active\draw\ActiveDrawModel;
use think\db\BaseQuery;

class ActiveDrawDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveDrawModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveDrawModel::class;
    }


}
