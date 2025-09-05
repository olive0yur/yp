<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersPushModel;
use app\common\repositories\users\UsersRepository;

class UsersPushDao extends BaseDao
{

    public function search(array $where, int $companyId = null)
    {
        return UsersPushModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use ($where) {
                $query->where('user_id', (int)$where['user_id']);
            })
            ->when(isset($where['user_code']) && $where['user_code'] !== '', function ($query) use ($where) {
                $ids = app()->make(UsersRepository::class)->search(['user_code' => $where['user_code']])->value('id');
                $query->where('parent_id', $ids);
            })
            ->when(isset($where['parent_id']) && $where['parent_id'] !== '', function ($query) use ($where) {
                $query->where('parent_id', (int)$where['parent_id']);
            })
            ->when(isset($where['user_mobile']) && $where['user_mobile'] !== '', function ($query) use ($where) {
                $query->where('user_mobile', (int)$where['user_mobile']);
            })
            ->when(isset($where['levels']) && $where['levels'] !== '', function ($query) use ($where) {
                $query->where('levels', (int)$where['levels']);
            })
            ->when(isset($where['level_code']) && $where['level_code'] !== '', function ($query) use ($where) {
                $query->whereIn('levels', [2, 3]);
            });
    }

    /**
     * @return UsersPushModel
     */
    protected function getModel(): string
    {
        return UsersPushModel::class;
    }

    public function getByUserId($userId)
    {
        return ($this->getModel())::getDB()->where('user_id', $userId)->select();
    }

    public function getUserId($id)
    {
        return ($this->getModel())::getDB()->where('parent_id', $id)->column('user_id');
    }
}
