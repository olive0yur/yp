<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersRepository;

class UsersPoolDao extends BaseDao
{

    /**
     * @return UsersPoolModel
     */
    protected function getModel(): string
    {
        return UsersPoolModel::class;
    }


    public function search(array $where, int $companyId = null)
    {
        return UsersPoolModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where,$companyId)  {
                   $uuid = app()->make(UsersRepository::class)->search([],$companyId)->whereLike('mobile|nickname','%'.$where['mobile'].'%')->column('id');
                   if($uuid) $query->whereIn('uuid', $uuid);
                })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where,$companyId)  {
                $ids = app()->make(PoolSaleRepository::class)->search(['title'=>$where['title']],$companyId)->column('id');
                $query->whereIn('pool_id', $ids);
            })

            ->when(isset($where['no']) && $where['no'] !== '', function ($query) use ($where) {
                $query->where(['no'=>$where['no']]);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where(['uuid'=>$where['uuid']]);
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where(['id'=>$where['id']]);
            })
            ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
                $query->where(['pool_id'=>$where['pool_id']]);
            })
            ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
                $query->where(['order_id'=>$where['order_id']]);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', (int)$where['type']);
            })
            ;
    }
}
