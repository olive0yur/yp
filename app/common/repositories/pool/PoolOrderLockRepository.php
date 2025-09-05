<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolOrderLockDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolOrderLockRepository
 * @package app\common\repositories\pool
 * @mixin PoolOrderLockDao
 */
class PoolOrderLockRepository extends BaseRepository
{

    public function __construct(PoolOrderLockDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'user' => function ($query) {
                    $query->field('id,mobile,nickname');
                    $query->bind(['mobile', 'nickname']);
                }
            ])
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $userInfo = $usersRepository->search([], $companyId)->where('mobile', $data['username'])->find();
        if (!$userInfo) {
            throw new ValidateException($data['username'] . '用户不存在!');
        }
        if (isset($data['username'])) {
            unset($data['username']);
        }
        $data['uuid'] = $userInfo['id'];
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {

        return $this->dao->update($info['id'], $data);
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
                Cache::store('redis')->delete('mark_num_'.$v['uuid']);
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

}