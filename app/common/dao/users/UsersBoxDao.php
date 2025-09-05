<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersBoxModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersRepository;

class UsersBoxDao extends BaseDao
{

    /**
     * @return UsersBoxModel
     */
    protected function getModel(): string
    {
        return UsersBoxModel::class;
    }


    public function search(array $where, int $companyId = null)
    {
        return UsersBoxModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where,$companyId)  {
                $ids = app()->make(UsersRepository::class)->search(['keyword'=>$where['mobile']],$companyId)->column('id');
                $query->whereIn('uuid', $ids);
            })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where,$companyId)  {
                $ids = app()->make(BoxSaleRepository::class)->search(['keywords'=>$where['title']],$companyId)->column('id');
                $query->whereIn('box_id', $ids);
            })
            ->when(isset($where['box_id']) && $where['box_id'] !== '', function ($query) use ($where) {
                $query->where(['box_id'=>$where['box_id']]);
            })
            ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
                $query->where(['order_id'=>$where['order_id']]);
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where(['id'=>$where['id']]);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where(['uuid'=>$where['uuid']]);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', (int)$where['status']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', (int)$where['type']);
            })
            ->when(isset($where['open_no']) && $where['open_no'] !== '', function ($query) use ($where) {
                $query->where('open_no', $where['open_no']);
            })
            ;
    }
}
