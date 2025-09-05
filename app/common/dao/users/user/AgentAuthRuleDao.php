<?php

namespace app\common\dao\users\user;

use app\common\dao\BaseDao;
use app\common\model\users\user\AgentAuthRule;
use think\db\BaseQuery;

class AgentAuthRuleDao extends BaseDao
{
    /**
     * @return AgentAuthRule
     */
    protected function getModel(): string
    {
        return AgentAuthRule::class;
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
    {        $query = AgentAuthRule::getDB()->order('sort desc')
             ->when(isset($where['is_menu']) && $where['is_menu'] !== '', function ($query) use ($where) {
                $query->where('is_menu', $where['is_menu']);
             });
        return $query;
    }

    /**
     * 获取菜单列表
     *
     * @param $where
     * @param $field
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