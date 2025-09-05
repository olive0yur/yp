<?php

namespace app\common\dao\system\upload;

use app\traits\CategoryDao;
use think\db\BaseQuery;
use app\common\dao\BaseDao;
use app\common\model\system\upload\UploadFileGroupModel;

class UploadFileGroupDao extends BaseDao
{
    use CategoryDao;

    /**
     * @return UploadFileGroupModel
     */
    protected function getModel(): string
    {
        return UploadFileGroupModel::class;
    }

    /**
     * @param int|null $companyId 企业ID
     * @return BaseQuery
     */
    public function search(int $companyId = null)
    {
        $query = UploadFileGroupModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', (int)$companyId);
            });

        return $query;
    }


}
