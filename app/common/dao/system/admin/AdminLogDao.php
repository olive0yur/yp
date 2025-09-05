<?php

namespace app\common\dao\system\admin;

use app\common\dao\BaseDao;
use app\common\model\system\admin\AdminLogModel;
use think\db\BaseQuery;

class AdminLogDao extends BaseDao
{

    /**
     * @return AdminLogModel
     */
    protected function getModel(): string
    {
        return AdminLogModel::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function searchJoin(array $where)
    {
        $query = AdminLogModel::getDB()->withJoin([
            'adminUser' => ['username', 'account']
        ])
            ->when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where) {
                $query->whereLike('account|username', "%{$where['username']}%");
            })
            ->when(isset($where['account']) && $where['account'] !== '', function ($query) use ($where) {
                $query->whereLike('account', "%{$where['account']}%");
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('remark', "%{$where['keyword']}%");
            });

        return $query;
    }

}
