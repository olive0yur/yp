<?php

namespace app\common\dao\active\task;

use app\common\dao\BaseDao;
use app\common\model\active\task\TaskLogModel;
use think\db\BaseQuery;

class TaskLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = TaskLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid',$where['uuid']);
            })
            ->when(isset($where['task_id']) && $where['task_id'] !== '', function ($query) use ($where) {
                $query->where('task_id',$where['task_id']);
            })
      ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return TaskLogModel::class;
    }


}
