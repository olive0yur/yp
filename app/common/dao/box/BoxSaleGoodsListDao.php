<?php

namespace app\common\dao\box;

use app\common\dao\BaseDao;
use app\common\model\box\BoxSaleGoodsListModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolSaleModel;
use think\db\BaseQuery;

class BoxSaleGoodsListDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = BoxSaleGoodsListModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })

        ->when(isset($where['goods_type']) && $where['goods_type'] !== '', function ($query) use ($where) {
        $query->where('goods_type',$where['goods_type']);
       })
            ->when(isset($where['box_id']) && $where['box_id'] !== '', function ($query) use ($where) {
                $query->where('box_id',$where['box_id']);
            })
        ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where('goods_id',$where['goods_id']);
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return BoxSaleGoodsListModel::class;
    }


}
