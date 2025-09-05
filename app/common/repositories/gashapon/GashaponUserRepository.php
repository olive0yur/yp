<?php

namespace app\common\repositories\gashapon;

use app\common\dao\gashapon\GashaponDao;
use app\common\dao\gashapon\GashaponUserDao;
use app\common\dao\givLog\GivLogDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class GashaponUserRepository
 * @package app\common\repositories\gashapon
 * @mixin GashaponUserDao
 */
class GashaponUserRepository extends BaseRepository
{

    public function __construct(GashaponUserDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $query->with(['user'=>function($query){
            $query->field('id,mobile,nickname');
            $query->bind(['mobile' => 'mobile', 'nickname' => 'nickname']);
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
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


    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $count = $this->dao->search($where,$companyId)->where('status',3)->sum('num');
        $where['status'] = 2;
        $query = $this->dao->search($where, $companyId);
        $list = $query->page($page, $limit)->order('id desc')
            ->page($page,$limit)
            ->select();
        return compact('count', 'list');
    }

    public function open($userInfo,$companyId = null)
    {
        if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');
        $count = $this->dao->search(['status'=>1,'uuid'=>$userInfo['id']],$companyId)->count('id');
        if($count <= 0) throw new ValidateException('请先领取扭蛋机!');

        $res2 = Cache::store('redis')->setnx('open_key' .$userInfo['id'],$userInfo['id']);
        Cache::store('redis')->expire('open_key' .$userInfo['id'], 1);
        if (!$res2) throw new ValidateException('操作频繁');

        return  Db::transaction(function () use ($userInfo,$companyId) {

            $op = $this->dao->search(['status'=>1,'uuid'=>$userInfo['id']],$companyId)->select();
            foreach ($op as $v){
                $list = app()->make(GashaponRepository::class)->search([],$companyId)->select();
                $item = [];
                foreach ($list as $key => $value){
                    $item[$value['id']] = $value['key'];
                }
                if (!$item) throw new ValidateException('人数过多，请稍后重试');
                $prize_id = getRand($item);
                $info = app()->make(GashaponRepository::class)->search([],$companyId)->where('id',$prize_id)->find();
                if($info['mode'] == 1){
                    $num = $info['num'];
                }else{
                    $number = explode(',', $info['num']);
                    $num = rand($number[0],$number[1]);
                }
                $this->dao->search(['status'=>1,'uuid'=>$userInfo['id']],$companyId)->where('id',$v['id'])
                    ->update(['status'=>2,'num'=>$num]);
            }
        });

    }

    public function create($id,$userInfo,$companyId = null)
    {
        if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');
        $find = $this->dao->search(['status'=>2,'uuid'=>$userInfo['id']],$companyId)->where('id',$id)->find();
        if(!$find) throw new ValidateException('已领取!');

        $res2 = Cache::store('redis')->setnx('create_key' .$userInfo['id'],$userInfo['id']);
        Cache::store('redis')->expire('create_key' .$userInfo['id'], 1);
        if (!$res2) throw new ValidateException('操作频繁');

        return  Db::transaction(function () use ($userInfo,$companyId,$id,$find) {
            $res = $this->dao->search(['status'=>2,'uuid'=>$userInfo['id']],$companyId)->where('id',$id)->update([
                'status'=>3,'edit_time'=>date("Y-m-d H:i:s")
            ]);
            if($res){
                $num = $find['num'];
                app()->make(UsersRepository::class)->batchFoodChange($userInfo['id'],4,$num,['remark'=>'扭蛋机领取']);
                return $num;
            }else{
                throw new ValidateException('请稍后重试');
            }
        });

    }

    public function ofList($userInfo,$companyId = null)
    {
        $list = $this->dao->search([],$companyId)
            ->where('uuid','<>',$userInfo['id'])
            ->where('status',3)
            ->with(['user'=>function($query){
                $query->field('id,mobile,nickname')->withAttr('mobile', function ($v, $data) {
                    return mb_substr_replace($v, '****', 3, 4);
                });
                $query->bind(['mobile' => 'mobile', 'nickname' => 'nickname']);
            }])
            ->select();
        return $list;
    }

    public function apifox($userInfo,$companyId = null)
    {
        $count = $this->dao->search(['uuid' => $userInfo['id'],'type'=>1], $companyId)->whereTime('add_time', 'today')->count('id');
        if($count > 0) throw new ValidateException('已领取');

        $res2 = Cache::store('redis')->setnx('apifox_key' .$userInfo['id'],$userInfo['id']);
        Cache::store('redis')->expire('apifox_key' .$userInfo['id'], 1);
        if (!$res2) throw new ValidateException('操作频繁');

        return  Db::transaction(function () use ($userInfo,$companyId) {
            $info = app()->make(GashaponToRepository::class)->search(['type'=>1])->find();
            if($info['count'] > 0){
                for ($i = 1; $i <= $info['count']; $i++) {
                    $givBoxData['type'] = 1;
                    $givBoxData['status'] = 1;
                    $givBoxData['uuid'] = $userInfo['id'];
                    $this->addInfo($userInfo['company_id'], $givBoxData);
                }
            }
            return true;
        });
    }

    public function batchGiveUser(array $userId, array $data)
    {

        $usersRepository = app()->make(UsersRepository::class);
        $list = $usersRepository->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->giveUserInfo($v, $data);
            }
            return $list;
        }
        return [];
    }


    public function giveUserInfo($userInfo, $data)
    {

        Db::transaction(function () use ($userInfo, $data) {
            for ($i = 1; $i <= $data['num']; $i++) {
                $givBoxData['type'] = 2;
                $givBoxData['status'] = 1;
                $givBoxData['uuid'] = $userInfo['id'];
                $this->addInfo($userInfo['company_id'], $givBoxData);
            }
        });

    }
}