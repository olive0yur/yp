<?php

namespace app\common\dao\system\upload;

use app\common\dao\BaseDao;
use app\common\model\system\upload\UploadFileModel;

class UploadFileDao extends BaseDao
{

    /**
     * @return UploadFileModel
     */
    protected function getModel(): string
    {
        return UploadFileModel::class;
    }

    public function search(array $where, int $companyId = 0)
    {
        $query = UploadFileModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })->when(isset($where['group_id']) && $where['group_id'] >= 0, function ($query) use ($where) {
                $query->where('group_id', (int)$where['group_id']);
            })->when(isset($where['source']) && $where['source'] !== '', function ($query) use ($where) {
                $query->where('source', $where['source']);
            })->when(isset($where['storage_type']) && $where['storage_type'] !== '', function ($query) use ($where) {
                $query->where('storage_type', $where['storage_type']);
            })->when(isset($where['file_type']) && is_array($where['file_type']), function ($query) use ($where) {
                $query->where('file_type', 'in', $where['file_type']);
            })->when(isset($where['time']) && $where['time'] !== '', function ($query) use ($where) {
                $times = explode(' - ', $where['time']);
                $startTime = strtotime($times[0]);
                $endTime = strtotime($times[1]);
                $query->whereBetween('add_time', [$startTime, $endTime]);
            });

        return $query;
    }

    public function clearTemplate($data)
    {
        ($this->getModel())::getDB()->where($this->getPk(), $data['front_file_id'])->delete();
        return ($this->getModel())::getDB()->where($this->getPk(), $data['back_file_id'])->delete();
    }
}
