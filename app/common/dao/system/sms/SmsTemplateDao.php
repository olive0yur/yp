<?php

namespace app\common\dao\system\sms;

use app\common\dao\BaseDao;
use app\common\model\system\sms\SmsTemplateModel;

class SmsTemplateDao extends BaseDao
{

    /**
     * @return SmsTemplateModel
     */
    protected function getModel(): string
    {
        return SmsTemplateModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = SmsTemplateModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', (int)$where['is_show']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('content', 'like', '%' . trim($where['keywords']) . '%');
            });
        return $query;
    }

}
