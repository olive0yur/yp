<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\pool\PoolSaleModel;
use app\common\model\pool\PoolShopOrderModel;
use app\common\model\pool\ShopOrderListModel;
use think\db\BaseQuery;
use think\facade\Db;

class ShopOrderListDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ShopOrderListModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
      ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
            $query->where('order_id',$where['order_id']);})
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid',$where['uuid']);})
      ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where('goods_id',$where['goods_id']);})
      ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
            $query->where('buy_type',$where['buy_type']);})
        ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
        $query->where('status',$where['status']);
    }) ->when(isset($where['sale_uuid']) && $where['sale_uuid'] !== '', function ($query) use ($where) {
                $query->where('sale_uuid',$where['sale_uuid']);
            })
        ;
        return $query;
    }





    /**
     * @return
     */
    protected function getModel(): string
    {
        return ShopOrderListModel::class;
    }


}
