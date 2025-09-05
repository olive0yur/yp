<?php

namespace app\common\dao\active\draw;

use app\common\dao\BaseDao;
use app\common\model\active\draw\ActiveDrawLogModel;
use think\db\BaseQuery;

class ActiveDrawLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveDrawLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid',$where['uuid']);
            })
            ->when(isset($where['draw_id']) && $where['draw_id'] !== '', function ($query) use ($where) {
                $query->where('draw_id',$where['draw_id']);
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
        return ActiveDrawLogModel::class;
    }


}
