<?php

namespace app\common\dao\system\affiche;

use think\db\BaseQuery;
use app\common\dao\BaseDao;
use app\common\model\system\affiche\AfficheCate;

class AfficheCateDao extends BaseDao
{
    /**
     * @return AfficheCate
     */
    protected function getModel(): string
    {
        return AfficheCate::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = AfficheCate::getDB();
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
        if (isset($where['name']) && $where['name'] !== '') {
            $query->where('name', 'like', '%' . trim($where['name']) . '%');
        }
        if (isset($where['keywords']) && $where['keywords'] !== '') {
            $query->where('name|desc|keywords', 'like', '%' . trim($where['keywords']) . '%');
        }
        return $query;
    }

    public function faqCategoryList($companyId = null, $status = '', $field = '*')
    {
        $query = $this->search([
            'is_show' => $status
        ], $companyId);

        return $query->field($field)->order('sort DESC,' . $this->getPk() . ' DESC')->select();
    }

    public function switchStatus(int $id, int $status)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->update([
            'is_show' => $status
        ]);
    }

    public function getAll($companyId = null, $status = '', $field = 'id,name,keywords')
    {
        $query = $this->search([
            'is_show' => $status
        ], $companyId);

        return $query
            ->field($field)
            ->order('sort DESC,' . $this->getPk() . ' DESC')->select();
    }
}
