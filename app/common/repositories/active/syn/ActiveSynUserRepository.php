<?php

namespace app\common\repositories\active\syn;

use app\common\repositories\BaseRepository;
use app\common\dao\active\syn\ActiveSynUserDao;
use app\common\repositories\users\UsersRepository;

/**
 * Class ActiveSynUserRepository
 * @package app\common\repositories\active
 * @mixin ActiveSynUserDao
 */
class ActiveSynUserRepository extends BaseRepository
{


    public function __construct(ActiveSynUserDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'user'=>function($query){
                    $query->field('id,mobile,nickname');
                    $query->bind(['mobile' => 'mobile','nickname' => 'nickname']);
                }
            ])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }

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

    public function addAll($data)
    {
        return $this->dao->insertAll($data);
    }

    public function addInfo($companyId, $data)
    {
        if($data['mobile']){
            $usersRepository = app()->make(UsersRepository::class);
            $userInfo = $usersRepository->search([],$companyId) ->where('mobile', $data['mobile'])->find();
            $data['uuid'] = $userInfo['id'];
        }
        unset($data['mobile']);
        return $this->dao->create($data);
    }

}