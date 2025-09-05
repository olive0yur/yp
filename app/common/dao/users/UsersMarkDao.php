<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersMarkModel;
use app\common\repositories\box\BoxSaleRepository;

class UsersMarkDao extends BaseDao
{

    /**
     * @return UsersMarkModel
     */
    protected function getModel(): string
    {
        return UsersMarkModel::class;
    }


    public function search(array $where, int $companyId = null)
    {
        return UsersMarkModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where(['buy_type' => $where['buy_type']]);
            })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where, $companyId) {
                $ids = app()->make(BoxSaleRepository::class)->search([], $companyId)->whereLike('title', '%' . $where['title'] . '%')->column('id');
                $query->whereIn('goods_id', $ids);
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where(['id' => $where['id']]);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where(['uuid' => $where['uuid']]);
            })
            ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where(['goods_id' => $where['goods_id']]);
            })
            ->when(isset($where['class_id']) && $where['class_id'] !== '', function ($query) use ($where) {
                $query->where(['goods_id' => $where['class_id']]);
            })
            ->when(isset($where['sell_id']) && $where['sell_id'] !== '', function ($query) use ($where) {
                $query->where('sell_id', (int)$where['sell_id']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['is_type']) && $where['is_type'] !== '', function ($query) use ($where) {
                $query->whereIn('status', [1, 2, 3]);
            })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where('buy_type', (int)$where['buy_type']);
            })
            ->when(isset($where['time']) && $where['time'] !== '', function ($query) use ($where) {
                $this->timeSearchBuild($query, $where['time'], 'add_time');
            })
            ->when(isset($where['is_main']) && $where['is_main'] !== '', function ($query) use ($where, $companyId) {
                $query->where('is_main', (int)$where['is_main']);
            })
            ;

    }
}
