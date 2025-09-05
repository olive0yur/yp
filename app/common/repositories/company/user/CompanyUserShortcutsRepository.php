<?php

namespace app\common\repositories\company\user;

use app\common\dao\company\user\CompanyUserShortcutsDao;
use app\common\repositories\BaseRepository;

class CompanyUserShortcutsRepository extends BaseRepository
{

    public function __construct(CompanyUserShortcutsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 查询管理员快捷操作
     *
     * @param int $adminId 管理员ID
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdminShortcuts($adminId)
    {
        $query = $this->dao->search([
            'admin_id' => $adminId
        ]);

        $list = $query->field('name,url,icon,open_type,url_type,id')
            ->select();

        return $list;
    }
}