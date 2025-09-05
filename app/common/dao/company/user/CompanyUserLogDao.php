<?php

namespace app\common\dao\company\user;

use app\common\dao\BaseDao;
use app\common\model\company\user\CompanyUserLog;
use think\db\BaseQuery;

class CompanyUserLogDao extends BaseDao
{

    /**
     * @return CompanyUserLog
     */
    protected function getModel(): string
    {
        return CompanyUserLog::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function searchJoin(array $where)
    {
        $query = CompanyUserLog::getDB()->withJoin([
            'adminUser' => ['username', 'account']
        ])
            ->when(isset($where['account']) && $where['account'] !== '', function ($query) use ($where) {
                $query->whereLike('account|username', "%{$where['account']}%");
            })
            ->when(isset($where['admin_id']) && $where['admin_id'] !== '', function ($query) use ($where) {
                $query->where('admin_id', (int)$where['admin_id']);
            })
            ->when(isset($where['log_type']) && $where['log_type'] !== '', function ($query) use ($where) {
                $query->where('log_type', (int)$where['log_type']);
            })
            ->when(isset($where['company_id']) && $where['company_id'] !== '', function ($query) use ($where) {
                $query->where('company_user_log.company_id', (int)$where['company_id']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('remark', "%{$where['keywords']}%");
            });
        return $query;
    }
}
