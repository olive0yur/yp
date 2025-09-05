<?php

namespace app\common\repositories\game;

use app\common\dao\game\UsersKnspDao;
use app\common\repositories\BaseRepository;

/**
 * Class UsersKnspRepository
 * @package app\common\repositories\UsersKnspRepository
 * @mixin UsersKnspDao
 */
class UsersKnspRepository extends BaseRepository
{

    public function __construct(UsersKnspDao $dao)
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


    public function getBack($where,$page,$limit,$userInfo,$companyId){
            $query = $this->dao->search($where, $companyId);
            $count = $query->count();
            $list = $query->page($page, $limit)
                ->select();
            return compact('count', 'list');
    }

}