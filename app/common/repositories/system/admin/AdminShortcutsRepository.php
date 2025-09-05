<?php

namespace app\common\repositories\system\admin;

use app\common\dao\system\admin\AdminShortcutsDao;
use app\common\repositories\BaseRepository;

class AdminShortcutsRepository extends BaseRepository
{

    public function __construct(AdminShortcutsDao $dao)
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