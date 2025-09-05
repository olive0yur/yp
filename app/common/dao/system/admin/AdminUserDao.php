<?php

namespace app\common\dao\system\admin;

use app\common\dao\BaseDao;
use app\common\model\system\admin\AdminUserModel;
use think\db\BaseQuery;

class AdminUserDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = AdminUserModel::getDB();
        if (isset($where['keywords']) && $where['keywords'] !== '') {
            $query->where('account|email|mobile', 'like', '%' . trim($where['keywords'] . '%'));
        }
        if (isset($where['username']) && $where['username'] !== '') {
            $query->where('username', 'like', '%' . trim($where['username'] . '%'));
        }
        if (isset($where['add_admin_id']) && $where['add_admin_id'] !== '') {
            $query->where('add_admin_id', (int)$where['add_admin_id']);
        }

        return $query;
    }

    /**
     * @return AdminUserModel
     */
    protected function getModel(): string
    {
        return AdminUserModel::class;
    }

    /**
     * 根据账号查询管理员信息
     *
     * @param string $account 账号
     * @return AdminUserModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfoByAccount($account)
    {
        return ($this->getModel())::getDB()->where('account', $account)->find();
    }

}
