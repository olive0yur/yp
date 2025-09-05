<?php

namespace app\common\repositories\vanity;

use app\common\dao\vanity\VanityDao;
use app\common\dao\users\UsersPushDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

class VanityRepository extends BaseRepository
{

    public function __construct(VanityDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['user' => function ($query) {
                $query->field(['id','nickname','mobile'])->bind(['mobile','nickname']);
            }])
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

    public function getApiList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where,$companyId)->where(['status' => 1]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    //购买靓号
    public function buyVanityCode($id,$user,$companyId = null)
    {
        $uuid = $user['id'];
        //查询是否唯一，更新用户 user_code 更新靓号状态 扣除用户代币
        $vanity = $this->getDetail($id,$companyId);
        if (!$vanity || $vanity['status'] != 1) throw new ValidateException('靓号已售出');

        $userRepository = app()->make(UsersRepository::class);
        $isExists = $userRepository->search([],$companyId)->where('user_code',$vanity['code'])->find();
        if ($isExists) {
            throw new ValidateException('靓号已售出');
        }

        if ($user['food'] < $vanity['price']) {
            throw new ValidateException('余额不足');
        }

        Db::startTrans();
        try {
            //减余额
            $userRepository->batchFoodChange($uuid,3, - $vanity['price'],[
                'remark' => '购买靓号'
            ],4);

            //更新用户user_code
            $originUserCode = $user['user_code'];
            $userRepository->editInfo($user,['user_code' => $vanity['code']]);

            //更新靓号状态 时间 原code
            $this->editInfo($vanity,['uuid' => $uuid,'status' => 2,'sale_time' => date('Y-m-d H:i:s',time()),'user_code' => $originUserCode]);

        } catch (\Exception $e) {
            Db::rollback();
            // 处理异常
            throw new ValidateException('失败：' . $e->getMessage());
        }
        Db::commit();
        return true;


    }

    public function getApiDetail(int $id,$uuid,$companyId = null)
    {

        $data = $this->dao->search(['uuid'=>$uuid],$companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }


}