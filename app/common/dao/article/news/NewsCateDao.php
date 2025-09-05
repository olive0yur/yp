<?php

namespace app\common\dao\article\news;

use app\common\dao\BaseDao;
use app\common\model\article\news\NewsCateModel;

class NewsCateDao extends BaseDao
{

    /**
     * @return NewsCateModel
     */
    protected function getModel(): string
    {
        return NewsCateModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = NewsCateModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('name|keywords|desc', '%' . trim($where['keywords']) . '%');
            });
        if (isset($where['time']) && $where['time'] !== '') {
            $times = explode(' - ', trim($where['time']));
            $query->where('add_time', ['between', [$times[0], $times[1]]]);
        }
        return $query;
    }


}