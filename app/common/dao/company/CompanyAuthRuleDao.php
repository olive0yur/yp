<?php

namespace app\common\dao\company;

use app\common\dao\BaseDao;
use app\common\model\company\CompanyAuthRule;
use think\db\BaseQuery;

class CompanyAuthRuleDao extends BaseDao
{
    /**
     * @return CompanyAuthRule
     */
    protected function getModel(): string
    {
        return CompanyAuthRule::class;
    }

    /**
     * 查询某个字段
     *
     * @param $where
     * @param $field
     * @return array
     */
    public function getColumn($where, $field)
    {
        return $this->search($where)
            ->column($field);
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = CompanyAuthRule::getDB()->order('sort desc');
        if (isset($where['is_menu'])) $query->where('is_menu', (int)$where['is_menu']);
        if (isset($where['id'])) {
            if (is_array($where['id'])) {
                $query->where('id', 'in', $where['id']);
            } else {
                $query->where('id', (int)$where['id']);
            }
        }

        return $query;
    }

    /**
     * 获取菜单列表
     *
     * @param $where
     * @param $field
     * @return CompanyAuthRule[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMenuList($where = [], $field = '*')
    {
        return $this->search($where)
            ->field($field)
            ->order('sort desc')
            ->select();
    }
}