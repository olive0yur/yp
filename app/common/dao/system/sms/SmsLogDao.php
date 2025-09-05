<?php

namespace app\common\dao\system\sms;

use app\common\dao\BaseDao;
use app\common\model\system\sms\SmsLogModel;

class SmsLogDao extends BaseDao
{

    /**
     * @return SmsLogModel
     */
    protected function getModel(): string
    {
        return SmsLogModel::class;
    }

    public function search(int $companyId = null, array $where = [])
    {
        $query = SmsLogModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', (int)$companyId);
            })->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where) {
                $query->whereLike('mobile', '%' . trim($where['mobile']) . '%');
            })->when(isset($where['time']) && $where['time'] !== '', function ($query) use ($where) {
                $times = explode(' - ', $where['time']);
                $query->whereTime('add_time', 'between', $times);
            })->when(isset($where['is_verify']) && $where['is_verify'] !== '', function ($query) use ($where) {
                $query->where('is_verify', (int)$where['is_verify']);
            })->when(isset($where['code']) && $where['code'] !== '', function ($query) use ($where) {
                $query->where('code', $where['code']);
            });

        return $query;
    }
}
