<?php

namespace app\common\dao\system\admin;

use app\common\dao\BaseDao;
use app\common\model\system\admin\AdminShortcutsModel;
use think\db\BaseQuery;

class AdminShortcutsDao extends BaseDao
{

    /**
     * @return AdminShortcutsModel
     */
    protected function getModel(): string
    {
        return AdminShortcutsModel::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = AdminShortcutsModel::getDB()->order('sort desc');
        if (isset($where['admin_id'])) $query->where('admin_id', (int)$where['admin_id']);

        return $query;
    }

}
