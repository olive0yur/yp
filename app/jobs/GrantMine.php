<?php

namespace app\jobs;

use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Log;
use think\queue\Job;

/**
 * 派送猴子
 */
class GrantMine
{

    public function fire(Job $job, $data)
    {
        try {
            $uuid = $data['uuid'];
            $companyId = $data['companyId'];
            $type = isset($data['type']) ? $data['type'] : 1;
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = app()->make(MineUserRepository::class);
            switch ($type){
                case 1:  //开矿转移旷工
                    $mine = $data['mine'];
                    $mineUserK =  $mineUserRepository->search(['uuid'=>$uuid,'status'=>1,'level'=>1],$companyId)->find();
                    if( $mineUserK['dispatch_count'] < $mine['people']) return true;
                    $mineUserRepository->decField($mineUserK['id'],'dispatch_count',$mine['people']);
                    $mineUserRepository->incField($data['mine_user_id'],'dispatch_count',$mine['people']);
                    break;
                case 3:  //挖矿结束，矿工回初始矿洞
                    $mineUserK =  $mineUserRepository->search(['uuid'=>$uuid,'status'=>1,'level'=>1],$companyId)->find();
                    $mineUserRepository->incField($mineUserK['id'],'dispatch_count',$data['num']);
                    break;
            }

        } catch (\Exception $e) {
            dump($e);
            Log::warning('派送猴子失败',$e);
        }
        $job->delete();
    }




    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}