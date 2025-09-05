<?php

namespace app\common\dao\system\admin;

use app\common\dao\BaseDao;
use app\common\model\system\admin\AdminAuthRuleModel;
use think\db\BaseQuery;

class AdminAuthRuleDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = AdminAuthRuleModel::getDB()->order('sort desc');
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
     * @return AdminAuthRuleModel
     */
    protected function getModel(): string
    {
        return AdminAuthRuleModel::class;
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
     * 获取菜单列表
     *
     * @param $where
     * @param $field
     * @return AdminAuthRuleModel[]|array|\think\Collection
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