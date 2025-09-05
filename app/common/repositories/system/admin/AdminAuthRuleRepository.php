<?php

namespace app\common\repositories\system\admin;

use app\common\dao\system\admin\AdminAuthRuleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\admin\AdminUserRepository;

class AdminAuthRuleRepository extends BaseRepository
{
    public function __construct(AdminAuthRuleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserMenus($id, $isMenu = true)
    {
        /** @var AdminUserRepository $repository */
        $repository = app()->make(AdminUserRepository::class);
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