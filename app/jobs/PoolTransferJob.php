<?php

namespace app\jobs;

use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;
use think\facade\Cache;
use app\common\repositories\users\UsersPoolRepository;

class PoolTransferJob
{
    public function fire(Job $job, $data)
    {
        die;
        Log::info("进入队列任务");
        try {
            $this->transfer($data);
        } catch (\Exception $e) {
            exception_log('任务处理失败', $e);
        }
        $job->delete();
    }

    /**
     * 卡牌转赠
     * @param $data
     * @return mixed
     */
    public function transfer($data)
    {
        Log::info("---------------------队列数据".json_encode($data)."-----------------------------");
        try {
            $pool_id = $data['pool_id'];
            $uuid = $data['uuid'];
            $companyId = $data['companyId'];
            $getUser = $data['getUser'];
            $order_no = $data['order_no'];
            return Db::transaction(function () use ( $getUser, $uuid, $companyId, $pool_id,$order_no) {
                /** @var GivLogRepository $givRepository */
                $givRepository = app()->make(GivLogRepository::class);
                /** @var UsersPoolRepository $usersPoolRepository */
                $usersPoolRepository = app()->make(UsersPoolRepository::class);
                /** @var PoolSaleRepository $poolSaleRepository */
                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                $pool = $poolSaleRepository->search(['id'=>$pool_id],$companyId)->find();
                if(!$pool) return true;

                $userPool = $usersPoolRepository->search(['pool_id'=>$pool_id,'status'=>1,'uuid'=>$uuid],$companyId)->where('is_dis',2)->find();
                if(!$userPool) return true;
                $transfer = $givRepository->search(['uuid'=>$uuid,'sell_id'=>$userPool['id'],'goods_id'=>$pool_id,'buy_type'=>1],$companyId)->find();
                if($transfer){
                    $usersPoolRepository->editInfo($userPool,['status'=>4]);
                    return false;
                }
                Log::info($getUser);
                $arr['uuid'] = $getUser['id'];
                $arr['pool_id'] = $userPool['pool_id'];
                $arr['no'] = $userPool['no'];
                $arr['add_time'] = date('Y-m-d H:i:s');
                $arr['status'] = 1;
                $arr['type'] = 3;
                $arr['company_id'] = $companyId;
                if($companyId == 21){
                    $arr['is_dis'] = 2;
                }
                $re = $usersPoolRepository->addInfo($companyId,$arr);
                if ($re) {
                    $givLogRepository = app()->make(GivLogRepository::class);
                    $giv['uuid'] = $uuid;
                    $giv['to_uuid'] = $getUser['id'];
                    $giv['goods_id'] = $pool_id;
                    $giv['buy_type'] = 1;
                    $giv['sell_id'] = $userPool['id'];
                    $giv['company_id'] = $companyId;
                    $giv['add_time'] = date('Y-m-d H:i:s');
                    $giv['unquied'] = $uuid.$userPool['id'];
                    $giv['order_no'] = $order_no;
                    $givLogRepository->addInfo($companyId, $giv);
                    $usersPoolRepository->editInfo($userPool,['status'=>4]);

//                    $grant['uuid'] = $getUser['id'];
//                    $grant['companyId'] = $companyId;
//                    $grant['num'] = 1;
//                    $grant['user_pool_id'] = $re['id'];
//                    $grant['type'] = 2;
//                    \think\facade\Queue::push(\app\jobs\GrantMine::class, $grant,'GrantMine');
                    $this->send($getUser['id'],$companyId,$re['id']);
                }
                api_user_log($uuid, 4, $companyId, '转赠卡牌:' . $pool['title']);
                return true;
            });
            return true;
        } catch (\Exception $e) {
            dump($e);
            exception_log('任务处理失败', $e);
        }
    }


    public function send($uuid,$companyId,$user_pool_id){
        //买卡发旷工
        /** @var MineRepository $mineRepository */
        $mineRepository = app()->make(MineRepository::class);

        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);

        $mine = $mineRepository->search(['level'=>1,'status'=>1],$companyId)->find();
        if(!$mine) return true;
        $mineUser = app()->make(MineUserRepository::class)->search(['mine_id'=>$mine['id'],'uuid'=>$uuid,'status'=>1,'level'=>1],$companyId)->find();
        if($mine && $mineUser){
            /** @var UsersPoolRepository $usersPoolRepository */
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            $count = $usersPoolRepository->search(['uuid'=>$uuid,'status'=>1],$companyId)->count('id');
            if($mineUser['dispatch_count'] < $count) {
                //发放start
                $re = $mineUserRepository->incField($mineUser['id'],'dispatch_count',1);
                //发放end
                if($re){
                    /** @var UsersPushRepository $usersPushRepository */
                    $usersPushRepository = app()->make(UsersPushRepository::class);
                    $parent_id = $usersPushRepository->search(['user_id'=>$uuid],$companyId)->value('parent_id');
                    if($parent_id){
                        /** @var MineDispatchRepository $mineDispatchRepository */
                        $mineDispatchRepository = app()->make(MineDispatchRepository::class);
                        $dispatch = $mineDispatchRepository->search(['uuid'=>$parent_id],$companyId)->find();
                        if(!$dispatch){
                            $arr['uuid'] = $parent_id;
                            $dispatch = $mineDispatchRepository->addInfo($companyId,$arr);
                        }
                        $arr1['mine_id'] = 0;
                        $arr1['uuid'] = $parent_id;
                        $arr1['user_pool_id'] = $user_pool_id;
                        $arr1['mine_user_id'] = 0;
                        $arr1['type'] = 2;
                        $arr1['frend_uuid'] = $uuid;
                        $arr1['end_time'] = strtotime(date('Y-m-d H:i:s') . ' +30 days');
                        $re = app()->make(MineUserDispatchRepository::class)->addInfo($companyId,$arr1);
                        if($re) $mineDispatchRepository->incField($dispatch['id'],'dispatch_count',1);
                    }
                }
            }
            return true;
        }
    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}