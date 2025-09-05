<?php

namespace app\common\repositories\game;

use app\common\dao\game\UsersRoleDao;
use app\common\repositories\BaseRepository;

/**
 * Class UsersRoleRepository
 * @package app\common\repositories\UsersRoleRepository
 * @mixin UsersRoleDao
 */
class UsersRoleRepository extends BaseRepository
{

    public function __construct(UsersRoleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }

    public function getAll($userInfo,$companyId = null)
    {
        $query = $this->dao->search(['uuid'=>$userInfo['id']], $companyId);
        $count = $query->count();
        if($count == 0){
            $arr['uuid'] = $userInfo['id'];
            $arr['role_id'] = app()->make(RoleRepository::class)->search([],$companyId)->min('id');
            $this->addInfo($companyId,$arr);
        }
        $list = $query->select();

        return compact('list');
    }


    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['create_at'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();

        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

}