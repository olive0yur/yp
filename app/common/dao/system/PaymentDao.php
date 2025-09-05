<?php

namespace app\common\dao\system;

use app\common\dao\BaseDao;
use app\common\model\system\PaymentModel;
use app\common\model\system\SystemPact;

class PaymentDao extends BaseDao
{

    /**
     * @return PaymentModel
     */
    protected function getModel(): string
    {
        return PaymentModel::class;
    }

    public function search(array $where, int $companyId = null)
    {

        $query = PaymentModel::getDB();
        $query->when($companyId !== null, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            });
        return $query;
    }

    public function getByType(string $type, int $companyId)
    {
        return $this->search([], $companyId)
            ->where('type', $type)
            ->find();
    }

}
