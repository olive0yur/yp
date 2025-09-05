<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class MineRepository
 * @package app\common\repositories\MineRepository
 * @mixin MineDao
 */
class MineRepository extends BaseRepository
{

    public function __construct(MineDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                if($fileInfo['id'] != $info['id']){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['cover']);
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->append(['source_info'])
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

    public function getAll(int $company_id = null){
        return  $this->dao->selectWhere(['company_id'=>$company_id],'id,name');
    }

    public function getCascaderData($companyId = 0,$status = '')
    {
        $list = $this->getAll($companyId,$status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'name', 0, 'pid', 0, 1);
    }


    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->where('level','>',1);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->field('id,name,output,day_output,day_output_gold,people,price,file_id,is_use,level,max_num,num')
            ->withAttr('use_number', function ($v, $data){
                return app()->make(MineUserRepository::class)
                    ->search(['level'=>$data['level']])
                    ->whereTime('add_time', 'today')
                    ->count('id');
            })
            ->withAttr('people',function ($v,$data){
                switch ($data['is_use']){
                    case 1:
                        return $data['people'];
                    case 2:
                        return 0;
                }
            })
            ->append(['use_number'])
            ->order('level asc')
            ->select();
        return compact('count', 'list');
    }

    public function getApiDetail(int $id)
    {
        $query = $this->dao->search([])->where('id',$id);
        $info = $query
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->field('id,name,file_id')
            ->find();
        return $info;
    }


    public function develop($data,$userInfo,$companyId){

            $res1 = Cache::store('redis')->setnx('develop_' . $userInfo['id'], $userInfo['id']);
            Cache::store('redis')->expire('develop_' . $userInfo['id'], 1);
            if (!$res1) throw new ValidateException('操作频繁!!');
            $mine = $this->dao->search(['status'=>1],$companyId)->where(['id'=>$data['mine_id']])->find();
            if(!$mine) throw new ValidateException('您选择的矿场不存在');
            if($mine['level'] == 1) throw new ValidateException('当前矿场无法通过开矿获取!');
            if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');
            if ($userInfo['gold'] < 0 || !$userInfo['gold'] || $userInfo['gold'] < $mine['price']){
                throw new ValidateException('您的金币余额不足');
            }
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = app()->make(MineUserRepository::class);

            $mineUserCount = $mineUserRepository->search(['uuid'=>$userInfo['id'],'mine_id'=>$data['mine_id'],'status'=>1],$companyId)->count('id');
            if($mineUserCount >= $mine['num']) throw new ValidateException('当前矿场开启数量已达到最大');

             $mineCount = $mineUserRepository->search(['level'=>$mine['level']],$companyId)
                 ->whereTime('add_time', 'today')
                 ->count('id');
             if($mine['max_number']>0 &&  $mineCount >= $mine['max_number'])  throw new ValidateException('当前矿场今日开启数量已达到最大');
            return Db::transaction(function () use ($data,$mine,$userInfo,$companyId,$mineUserRepository) {
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchGoldChange($userInfo['id'], 3,  '-'.$mine['price'], [
                    'remark' => '开启矿场'
                ],4);
                $add['mine_id'] = $data['mine_id'];
                $add['uuid'] = $userInfo['id'];
                $add['unquide'] = $companyId.$userInfo['id'].$data['mine_id'];
                $add['total'] = $mine['output'];
                $add['level'] = $mine['level'];
                $add['rate'] = $mine['rate'];
                $add['dispatch_count'] = $mine['people'];
                $re = $mineUserRepository->addInfo($companyId,$add);
                return $re;
            });
     }
     public function getIntroduce($id,$companyId){
        return $this->dao->search([],$companyId)->where(['id'=>$id])->field('id,content')->find();
     }
     public function pledge($userInfo,$companyId){
         try {
             $dragon = web_config($companyId,'program.colors','');
             if(!$dragon['num']) throw new ValidateException('质押参数未设置');
             /** @var UsersRepository $usersRepository */
             $usersRepository = app()->make(UsersRepository::class);
             return Db::transaction(function () use ($usersRepository,$userInfo,$companyId,$dragon) {
                     $userInfo =$usersRepository->search([], $companyId)->where('id', $userInfo['id'])->where('food','>=',$dragon['num'])->lock(true)->find();
                     if (!$userInfo) throw new ValidateException('余额不足!');
                     if($userInfo['pledge'] == 1) throw new ValidateException('禁止重复质押!');
                     $beforeChange = $userInfo->food;
                     $change = bcsub($userInfo->food,$dragon['num'],2);
                     $afterChange = $change;
                     $usersRepository->editInfo($userInfo,['food'=>$change,'pledge'=>1,'pledge_num'=>$dragon['num']]);
                     /**
                      * @var UsersBalanceLogRepository $balanceLogRepository
                      */
                     $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
                     return $balanceLogRepository->addLog($userInfo['id'], $dragon['num'], 3, array_merge(['remark'=>'质押'], [
                         'before_change' => $beforeChange,
                         'after_change' => $afterChange,
                         'track_port' => 4,
                     ]));
                     return true;
             });
         }catch (Exception $e){
             throw new ValidateException($e->getMessage());
         }
    }
     public function cannelPledge($userInfo,$companyId){
         try {
             $dragon = web_config($companyId,'program.colors','');
             if(!$dragon['num']) throw new ValidateException('质押参数未设置');
             /** @var UsersRepository $usersRepository */
             $usersRepository = app()->make(UsersRepository::class);
             return Db::transaction(function () use ($usersRepository,$userInfo,$companyId,$dragon) {
                     $userInfo =$usersRepository->search([], $companyId)->where('id', $userInfo['id'])->lock(true)->find();
                     if ($userInfo['pledge'] != 1) throw new ValidateException('暂未质押，无法取消!');
                     /** @var DragonCannelRepository $dragonCannelRepository */
                     $dragonCannelRepository = app()->make(DragonCannelRepository::class);
                     $info = $dragonCannelRepository->search(['uuid'=>$userInfo['id'],'status'=>1])->find();
                     if($info && $info['status'] == 1) throw new ValidateException('申请中，请勿重复提交申请!');
                     $beforeChange = $userInfo->food;
                 /**
                  * @var UsersFoodLogRepository $usersFoodLogRepository
                  */
                 $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
                 $usersFoodLogRepository->addLog($userInfo['id'], 0, 4, array_merge(['remark'=>'质押取消-申请中'], [
                         'before_change' => $beforeChange,
                         'after_change' => $beforeChange,
                         'track_port' => 4,
                     ]));
                      return $dragonCannelRepository->addInfo($companyId,['uuid'=>$userInfo['id'],'num'=>$userInfo['pledge_num'],'status'=>1]);
                      return true;
             });
         }catch (Exception $e){
             throw new ValidateException($e->getMessage());
         }
    }


    public function syn($num,$userInfo,$companyId){
        try {
            $dragon = web_config($companyId,'program.colors','');
            if(!$dragon['num']) throw new ValidateException('合成参数未设置');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            if($userInfo['pledge'] != 1) throw new ValidateException('请先完成质押');
            return Db::transaction(function () use ($usersRepository,$num,$userInfo,$companyId,$dragon) {
                $money = $dragon['unset'] * $num;
                $userInfo =$usersRepository->search([], $companyId)->where('id', $userInfo['id'])->where('food','>=',$money)->lock(true)->find();
                if (!$userInfo) throw new ValidateException('余额不足!');
                $beforeChange = $userInfo->food;
                $userInfo->food = bcsub($userInfo->food,$money,2);
                $userInfo->dragon =$userInfo->dragon+$num;
                $userInfo->save();
                $afterChange = $userInfo->food;
                /**
                 * @var UsersBalanceLogRepository $balanceLogRepository
                 */
                $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
                return $balanceLogRepository->addLog($userInfo['id'], $money, 3, array_merge(['remark'=>'合成七彩龙'], [
                    'before_change' => $beforeChange,
                    'after_change' => $afterChange,
                    'track_port' => 4,
                ]));
                return true;
            });
        }catch (Exception $e){
            throw new ValidateException($e->getMessage());
        }
    }






}