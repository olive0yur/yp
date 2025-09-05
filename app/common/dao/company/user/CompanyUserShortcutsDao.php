<?php

namespace app\common\dao\company\user;

use app\common\dao\BaseDao;
use app\common\model\company\user\CompanyUserShortcuts;
use think\db\BaseQuery;

class CompanyUserShortcutsDao extends BaseDao
{

    /**
     * @return CompanyUserShortcuts
     */
    protected function getModel(): string
    {
        return CompanyUserShortcuts::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = CompanyUserShortcuts::getDB()->order('sort desc');
        if (isset($where['admin_id'])) $query->where('admin_id', (int)$where['admin_id']);

        return $query;
    }
}
