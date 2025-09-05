<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersBoxModel;
use app\common\model\users\UsersMessageModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersRepository;

class UsersMessageDao extends BaseDao
{

    /**
     * @return UsersMessageModel
     */
    protected function getModel(): string
    {
        return UsersMessageModel::class;
    }


    public function search(array $where, int $companyId = null)
    {
        return UsersMessageModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where,$companyId)  {
                $ids = app()->make(UsersRepository::class)->search(['keyword'=>$where['mobile']],$companyId)->column('id');
                $query->whereIn('uuid', $ids);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('uuid', $where['uuid']);
            })
            ;
    }
}
