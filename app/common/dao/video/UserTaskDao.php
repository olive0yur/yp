<?php

namespace app\common\dao\video;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildModel;
use app\common\model\video\UserTaskMode;
use app\common\model\video\VideoTaskModel;
use think\db\BaseQuery;

class UserTaskDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = UserTaskMode::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['name']) && $where['guild_name'] !== '', function ($query) use ($where) {
                $query->whereLike('name', $where['name']);
            })

            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['task_id']) && $where['task_id'] !== '', function ($query) use ($where) {
                $query->whereLike('task_id', $where['task_id']);
            })
        ;
        return $query;
    }

    /**
     * @return UserTaskMode
     */
    protected function getModel(): string
    {
        return UserTaskMode::class;
    }



}
