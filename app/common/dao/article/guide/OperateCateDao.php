<?php

namespace app\common\dao\article\guide;

use app\common\dao\BaseDao;
use app\common\model\article\guide\OperateCateModel;

class OperateCateDao extends BaseDao
{

    /**
     * @return OperateCateModel
     */
    protected function getModel(): string
    {
        return OperateCateModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = OperateCateModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('name', '%' . trim($where['keywords']) . '%');
            });
        if (isset($where['time']) && $where['time'] !== '') {
            $times = explode(' - ', trim($where['time']));
            $query->where('add_time', ['between', [$times[0], $times[1]]]);
        }
        return $query;
    }


}