<?php

namespace app\common\dao\article\faq;

use app\common\dao\BaseDao;
use app\common\model\article\faq\FaqCateModel;

class FaqCateDao extends BaseDao
{

    /**
     * @return FaqCateModel
     */
    protected function getModel(): string
    {
        return FaqCateModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = FaqCateModel::getDB()
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