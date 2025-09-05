<?php

namespace app\common\dao\article\faq;

use app\common\dao\BaseDao;
use app\common\model\article\faq\FaqModel;

class FaqDao extends BaseDao
{

    /**
     * @return FaqModel
     */
    protected function getModel(): string
    {
        return FaqModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = FaqModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', intval($where['is_show']));
            })
            ->when(isset($where['cate_id']) && $where['cate_id'] !== '', function ($query) use ($where) {
                $query->where('cate_id', intval($where['cate_id']));
            })
            ->when(isset($where['is_top']) && $where['is_top'] !== '', function ($query) use ($where) {
                $query->where('is_top', intval($where['is_top']));
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('title|content', '%' . trim($where['keywords']) . '%');
            });
        if (isset($where['time']) && $where['time'] !== '') {
            $times = explode(' - ', trim($where['time']));
            $query->where('add_time', ['between', [$times[0], $times[1]]]);
        }
        return $query;
    }


        /**
         * 修改状态
         *
         * @param int $id
         * @param int $status
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function switchStatus(int $id, int $status)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->update([
            'is_show' => $status
        ]);
    }

    public function placedTop(int $id, int $placedTop)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->update([
            'is_top' => $placedTop
        ]);
    }

}