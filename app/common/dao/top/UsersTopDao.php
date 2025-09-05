<?php

namespace app\common\dao\top;

use app\common\model\top\UsersTopModel;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class UsersTopDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId= null)
    {
        $query = UsersTopModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['top_id']) && $where['top_id'] !== '', function ($query) use ($where) {
                $query->where('top_id', $where['top_id']);
            })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->where('uuid', '=', function ($query) use ($where) {
                $query->name('users')
                    ->where('mobile|user_code', $where['keywords'])
                    ->field('id');
            });
        })
            ->when(isset($where['is_type']) && $where['is_type'] !== '', function ($query) use ($where) {
                $query->where('is_type', $where['is_type']);
            });

        return $query;
    }

    /**
     * @return UsersTopModel
     */
    protected function getModel(): string
    {
        return UsersTopModel::class;
    }

}
