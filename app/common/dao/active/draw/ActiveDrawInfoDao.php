<?php

namespace app\common\dao\active\draw;

use app\common\dao\BaseDao;
use app\common\model\active\draw\ActiveDrawInfoModel;
use think\db\BaseQuery;

class ActiveDrawInfoDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveDrawInfoModel::getDB()

        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })

        ->when(isset($where['draw_id']) && $where['draw_id'] !== '', function ($query) use ($where) {
            $query->where(['draw_id'=>$where['draw_id']]);
        })
        ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
            $query->where(['goods_id '=>$where['goods_id']]);
         })
        ->when(isset($where['goods_type']) && $where['goods_type'] !== '', function ($query) use ($where) {
                $query->where(['goods_type '=>$where['goods_type']]);
        })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveDrawInfoModel::class;
    }


}
