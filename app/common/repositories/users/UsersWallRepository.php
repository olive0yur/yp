<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersWallDao;
use app\common\dao\users\UsersPushDao;
use app\common\repositories\BaseRepository;
use think\facade\Db;

class UsersWallRepository extends BaseRepository
{

    public function __construct(UsersWallDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['user'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function addInfo(int $companyId = null,array $data = [])
    {
        return Db::transaction(function () use ($data,$companyId) {
            if($companyId) $data['company_id'] = $companyId;
              $data['add_time'] = date('Y-m-d H:i:s');
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }


    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id,$companyId = null)
    {

        $data = $this->dao->search([],$companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function getApiList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->whereIn('status',[2])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function getApiDetail(int $id,$uuid,$companyId = null)
    {

        $data = $this->dao->search(['uuid'=>$uuid],$companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }


}