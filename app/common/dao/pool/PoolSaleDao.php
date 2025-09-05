<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\pool\PoolSaleModel;
use app\common\repositories\users\UsersMarkRepository;
use think\db\BaseQuery;

class PoolSaleDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = PoolSaleModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['is_number']) && $where['is_number'] !== '', function ($query) use ($where) {
                $query->where('is_number', $where['is_number']);
            })
            ->when(isset($where['brand_id']) && $where['brand_id'] !== '', function ($query) use ($where) {
                $query->where('brand_id', $where['brand_id']);
            })
            ->when(isset($where['ablum_id']) && $where['ablum_id'] !== '', function ($query) use ($where) {
                $query->where('ablum_id', $where['ablum_id']);
            })
            ->when(isset($where['get_type']) && $where['get_type'] !== '', function ($query) use ($where) {
                $query->whereIn('get_type', $where['get_type']);
            })
            ->when(isset($where['open_type']) && $where['open_type'] !== '', function ($query) use ($where) {
                $query->where('open_type', $where['open_type']);
            })
            ->when(isset($where['class_id']) && $where['class_id'] !== '', function ($query) use ($where) {
                $query->where('id', $where['class_id']);
            })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {
                $query->whereLike('title', '%' . trim($where['title']) . '%');
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where('id', $where['id']);
            })
            ->when(isset($where['is_hot']) && $where['is_hot'] !== '', function ($query) use ($where) {
                $query->where('is_hot', $where['is_hot']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status',$where['status']);
             })
            ->when(isset($where['is_success']) && $where['is_success'] !== '', function ($query) use ($where) {
                $query->where('is_success',$where['is_success']);
            })
            ->when(isset($where['virtual']) && $where['virtual'] !== '', function ($query) use ($where) {
                $query->where('virtual',$where['virtual']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type',$where['type']);
            })
            ->when(isset($where['is_mark']) && $where['is_mark'] !== '', function ($query) use ($where) {
                $query->where('is_mark',$where['is_mark']);
            });
        return $query;
    }

    public function search1(array $where,int $companyId=null)
    {
        $query = PoolSaleModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('p.company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('p.title', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['is_number']) && $where['is_number'] !== '', function ($query) use ($where) {
                $query->where('p.is_number', $where['is_number']);

            })
            ->when(isset($where['brand_id']) && $where['brand_id'] !== '', function ($query) use ($where) {
                $query->where('p.brand_id', $where['brand_id']);
            })
            ->when(isset($where['class_id']) && $where['class_id'] !== '', function ($query) use ($where) {
                $query->where('p.id', $where['class_id']);
            })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {
                $query->whereLike('p.title', '%' . trim($where['title']) . '%');
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where('p.id', $where['id']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('p.status',$where['status']);
            });
        return $query;
    }


    /**
     * @return
     */
    protected function getModel(): string
    {
        return PoolSaleModel::class;
    }




}
