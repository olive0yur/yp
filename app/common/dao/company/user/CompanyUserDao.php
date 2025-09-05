<?php

namespace app\common\dao\company\user;

use app\common\dao\BaseDao;
use app\common\model\company\user\CompanyUser;
use think\db\BaseQuery;

class CompanyUserDao extends BaseDao
{
     /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = CompanyUser::getDB();
        if (isset($where['username']) && $where['username'] !== '') {
            $query->where('username', 'like', '%' . trim($where['username'] . '%'));
        }
        if (isset($where['keyword']) && $where['keyword'] !== '') {
            $query->where('account|mobile|email', 'like', '%' . trim($where['keyword'] . '%'));
        }
        if (isset($where['company_id']) && $where['company_id'] !== '') {
            $query->where('company_id', (int)$where['company_id']);
        }
        return $query;
    }
    
    /**
     * @return CompanyUser
     */
    protected function getModel(): string
    {
        return CompanyUser::class;
    }

    /**
     * 根据账号查询管理员信息
     *
     * @param string $account 账号
     * @return CompanyUser|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfoByAccount($account)
    {
        return ($this->getModel())::getDB()->where('account', $account)
            ->find();

    }

}
