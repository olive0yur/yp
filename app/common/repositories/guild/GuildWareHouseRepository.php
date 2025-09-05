<?php

namespace app\common\repositories\guild;

use app\common\dao\guild\GuildWareHouseDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class GuildWareHouseRepository
 * @package app\common\repositories\GuildWareHouseRepository
 * @mixin GuildWareHouseDao
 */
class GuildWareHouseRepository extends BaseRepository
{

    public function __construct(GuildWareHouseDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,mobile');
            $query->bind(['mobile'=>'mobile']);
        },'pool'=>function($query){
            $query->bind(['title']);
        },'guild'=>function($query){
            $query->bind(['guild_name']);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            if($data['pool_id'] && $data['poolNum'] > 0){
                /** @var PoolSaleRepository $poolSaleRepository */
                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                $pool = $poolSaleRepository->search([],$companyId)->where(['id'=>$data['pool_id']])->find();
                if(!$pool) throw new ValidateException('卡牌不存在!');
                if($pool['stock'] < $data['poolNum']) throw new ValidateException('库存不足!');
                $pool->stock = $pool['stock'] - $data['poolNum'];
                if($pool->save()){
                    $ware['guild_id'] = $data['guild_id'];
                    $ware['type'] = 1;
                    $ware['pool_id'] = $data['pool_id'];
                    $ware['num'] = $data['poolNum'];
                    $ware['company_id'] = $companyId;
                    $ware['create_time'] = date('Y-m-d H:i:s');

                    $this->dao->create($ware);
                }
            }
            if($data['num'] > 0){
                $ware1['guild_id'] = $data['guild_id'];
                $ware1['type'] = 2;
                $ware1['num'] = $data['num'];
                $ware1['company_id'] = $companyId;
                $ware1['create_time'] = date('Y-m-d H:i:s');
                $this->dao->create($ware1);
            }
            return true;
        });

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

    public function getApiList($page,$limit,$userInfo,$companyId){
         /** @var GuildRepository $guildRepository */
         $guildRepository=app()->make(GuildRepository::class);
         $guide = $guildRepository->search(['uuid'=>$userInfo['id']])->find();
         if(!$guide) throw new ValidateException('只有会长可以查看公会仓库!');
            $query = $this->dao->search(['guild_id'=>$guide['id'],'status'=>1], $companyId);
            $count = $query->count();
            $list = $query->with(['user'=>function($query){
                $query->field('id,mobile');
                $query->bind(['mobile'=>'mobile']);
            },'pool'=>function($query){
                $query->with(['cover'=>function($query){
                    $query->bind(['picture'=>'show_src']);
                }
                ]);
                $query->bind(['title','picture']);
            }])->page($page, $limit)
                ->select();
            return compact('count', 'list');
    }

    public function send($data,$userInfo,$companyId){
        $res1 = Cache::store('redis')->setnx('ware_' . $data['ware_id'], $userInfo['id']);
        Cache::store('redis')->expire('ware_' . $data['ware_id'], 1);
        if (!$res1) throw new ValidateException('禁止同时转增!!');

        /** @var GuildRepository $guildRepository */
        $guildRepository=app()->make(GuildRepository::class);
        $guide = $guildRepository->search(['uuid'=>$userInfo['id']])->find();
        if(!$guide) throw new ValidateException('只有会长可以进行此操作!');

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $getUser = $usersRepository->search(['user_code' => $data['user_code']], $companyId)->field('id,mobile,cert_id')->find();

        if (!$getUser) throw new ValidateException('接收方账号不存在！');
        if ($getUser['cert_id'] == 0) throw new ValidateException('接收方未实名认证!');
        /** @var GuildMemberRepository $guildMemberRepository */
        $guildMemberRepository = app()->make(GuildMemberRepository::class);
        $isM = $guildMemberRepository->search(['uuid'=>$getUser['id'],'guild_id'=>$guide['id']])->find();
        if(!$isM) throw new ValidateException('禁止转给非本公会成员!');
        if($companyId != 28){
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }

        $ware = $this->dao->search(['guild_id'=>$guide['id']],$companyId)->where(['id'=>$data['ware_id'],'status'=>1])->find();
        if(!$data) throw new ValidateException('此物品不存在');
        if($ware['num'] < $data['num']) throw new ValidateException('仓库剩余数量不足');

        return Db::transaction(function () use ($data, $ware,$companyId,$getUser) {
            switch ($ware['type']) {
                case 1:
                    $no = app()->make(PoolOrderNoRepository::class);
                    $userPool = app()->make(UsersPoolRepository::class);
                    $pool = app()->make(PoolSaleRepository::class)->search([], $companyId)
                        ->where(['id' => $ware['pool_id']])->find();
                    if(!$pool) throw new ValidateException('转赠卡牌不存在!');
                    $ware->num = $ware['num'] - $data['num'];
                    if( $ware->num <= 0) $ware->status = 2;
                    if($ware->save()){
                        for ($i=0;$i<$data['num'];$i++){
                            $arr['uuid'] = $getUser['id'];
                            $arr['pool_id'] = $ware['pool_id'];
                            $arr['no'] =  $no->getNo($ware['pool_id'],$getUser['id']);
                            $arr['add_time'] = date('Y-m-d H:i:s');
                            $arr['status'] = 1;
                            $arr['type'] = 3;
                            $arr['company_id'] = $companyId;
                            $userPool->addInfo($companyId,$arr);
                            /** @var MineUserRepository $mineUserRepository */
                            $mineUserRepository = app()->make(MineUserRepository::class);
                            $mineUser = $mineUserRepository->search(['uuid'=>$getUser['id'],'level'=>1])->find();
                            $mineUserRepository->incField($mineUser['id'],'dispatch_count',1);
                        }

                        $arr1['guild_id'] = $ware['guild_id'];
                        $arr1['uuid'] = $getUser['id'];
                        $arr1['ware_id'] = $ware['id'];
                        $arr1['type'] = 1;
                        $arr1['num'] = $data['num'];
                        $arr1['pool_id'] = $ware['pool_id'];
                        app()->make(GuildWareLogRepository::class)->addInfo($companyId,$arr1);
                    }
                    break;
                case 2:
                     /** @var UsersRepository $usersRepository */
                     $usersRepository = app()->make(UsersRepository::class);
                    $usersRepository->batchFoodChange($getUser['id'],4,$data['num'],['remark'=>'公会福利']);
                    $arr1['guild_id'] = $ware['guild_id'];
                    $arr1['uuid'] = $getUser['id'];
                    $arr1['ware_id'] = $ware['id'];
                    $arr1['type'] = 2;
                    $arr1['num'] = $data['num'];
                    $arr1['pool_id'] =0;
                    app()->make(GuildWareLogRepository::class)->addInfo($companyId,$arr1);
                    break;
            }
        });
    }

}