<?php

namespace app\common\dao\company;

use app\common\dao\BaseDao;
use app\common\model\company\Company;
use think\db\BaseQuery;

class CompanyDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = Company::getDB();
        if (isset($where['name']) && $where['name'] !== '') {
            $query->where('name', 'like', '%' . trim($where['name'] . '%'));
        }
        if (isset($where['keyword']) && $where['keyword'] !== '') {
            $query->where('username|mobile', 'like', '%' . trim($where['keyword'] . '%'));
        }
        if (isset($where['address']) && $where['address'] !== '') {
            $query->where('address', 'like', '%' . trim($where['address'] . '%'));
        }
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return Company::class;
    }

    /**
     * 根据账号查询管理员信息
     *
     * @param string $account 账号
     * @return Company|array|mixed|\think\Model|null
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
