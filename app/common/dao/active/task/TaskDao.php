<?php

namespace app\common\dao\active\task;

use app\common\dao\BaseDao;
use app\common\model\active\task\TaskModel;
use think\db\BaseQuery;

class TaskDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = TaskModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            })
        ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
        $query->where('type',$where['type']);
         })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where('buy_type',$where['buy_type']);
            })
            ->when(isset($where['godos_id']) && $where['godos_id'] !== '', function ($query) use ($where) {
                $query->where('godos_id',$where['godos_id']);
            })
            ->when(isset($where['send_type']) && $where['send_type'] !== '', function ($query) use ($where) {
                $query->where('send_type',$where['send_type']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return TaskModel::class;
    }


}
