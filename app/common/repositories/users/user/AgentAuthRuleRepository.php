<?php

namespace app\common\repositories\users\user;

use app\common\dao\company\CompanyAuthRuleDao;
use app\common\dao\users\user\AgentAuthRuleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\company\user\CompanyUserRepository;

class AgentAuthRuleRepository extends BaseRepository
{
    public function __construct(AgentAuthRuleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserMenus($id, $isMenu = true)
    {
        $where['is_menu'] = 1;
        return $this->dao->getMenuList($where);
    }




    /**
     * 获取权限地址
     *
     * @param $ids
     * @return array
     */
    public function getRules($ids)
    {
        return $this->dao->getColumn([
            'id' => $ids
        ], 'rule');
    }

}