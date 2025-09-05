<?php

namespace app\common\dao\system\affiche;

use app\common\dao\BaseDao;
use app\common\model\system\affiche\Affiche;

class AfficheDao extends BaseDao
{
    /**
     * @return Affiche
     */
    protected function getModel(): string
    {
        return Affiche::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = Affiche::getDB();
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if (isset($where['is_top']) && $where['is_top'] !== '') {
            $query->where('is_top', (int)$where['is_top']);
        }
         if (isset($where['cate_id']) && $where['cate_id'] !== '') {
             $query->where('cate_id', (int)$where['cate_id']);
         }
        if (isset($where['is_show']) && $where['is_show'] !== '') {
            $query->where('is_show', (int)$where['is_show']);
        }
        if (isset($where['keywords']) && $where['keywords'] !== '') {
            $query->where('title|content', 'like', '%' . trim($where['keywords']) . '%');
        }
        return $query;
    }

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
