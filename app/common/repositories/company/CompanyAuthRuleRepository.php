<?php

namespace app\common\repositories\company;

use app\common\dao\company\CompanyAuthRuleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\company\user\CompanyUserRepository;

class CompanyAuthRuleRepository extends BaseRepository
{
    public function __construct(CompanyAuthRuleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserMenus($id, $isMenu = true)
    {
        /**
         * @var CompanyUserRepository $repository
         */
        $repository = app()->make(CompanyUserRepository::class);
        $adminUserInfo = $repository->get($id);
        if (empty($adminUserInfo)) {
            return false;
        }
        $where = [];
        $where['id'] = explode(',', $adminUserInfo['rules']);
        if ($isMenu) {
            $where['is_menu'] = 1;
        }

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