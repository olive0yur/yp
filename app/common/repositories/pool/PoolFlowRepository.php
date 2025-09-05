<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolFlowDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;

/**
 * Class PoolFlowRepository
 * @package app\common\repositories\pool
 * @mixin PoolFlowDao
 */
class PoolFlowRepository extends BaseRepository
{

    public function __construct(PoolFlowDao $dao)
    {
        $this->dao = $dao;
    }
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->withAttr('nickname', function ($v, $data) {
                return mb_substr_replace($v, '****', 0, -1);
            })
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }

    public function addInfo($data)
    {
        if($data['uuid'] > 0){
            $usersRepository = app()->make(UsersRepository::class);
            $userInfo = $usersRepository->getSearch(['id'=>$data['uuid']])->field('id,nickname')->find();
            if($userInfo['id'] > 0){
                $data['nickname'] = $userInfo['nickname'];
            }
        }
        return $this->dao->create($data);
    }

    public function getApiPoolFlowList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->withAttr('nickname', function ($v, $data) {
                return mb_substr_replace($v, '****', 0, -1);
            })
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }

}