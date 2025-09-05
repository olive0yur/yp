<?php

namespace app\common\dao\gashapon;

use app\common\model\gashapon\GashaponModel;
use app\common\model\gashapon\GashaponToModel;
use app\common\model\gashapon\GashaponUserModel;
use app\common\repositories\users\UsersRepository;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class GashaponUserDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = GashaponUserModel::getDB()
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where,$companyId)  {
                $uuid = app()->make(UsersRepository::class)->search([],$companyId)->whereLike('mobile|nickname','%'.$where['mobile'].'%')->column('id');
                if($uuid) $query->whereIn('uuid', $uuid);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['add_time']) && $where['add_time'] !== '', function ($query) use ($where) {
                $this->timeSearchBuild($query, $where['add_time'], 'add_time');
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return GashaponUserModel::class;
    }
}
