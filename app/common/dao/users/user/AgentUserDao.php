<?php

namespace app\common\dao\users\user;

use app\common\dao\BaseDao;
use app\common\model\users\UsersModel;
use think\db\BaseQuery;

class AgentUserDao extends BaseDao
{
     /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId = null)
    {
        $query =  UsersModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        if (isset($where['mobile']) && $where['mobile'] !== '') {
            $query->where('mobile', $where['mobile']);
        }
        return $query;
    }
    
    /**
     * @return UsersModel
     */
    protected function getModel(): string
    {
        return UsersModel::class;
    }

    /**
     * 根据账号查询管理员信息
     *
     * @param string $account 账号
     * @return UsersModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfoByAccount($account,$companyId)
    {
        return ($this->getModel())::getDB()->where('mobile', $account)->where('company_id',$companyId)
            ->find();

    }

}
