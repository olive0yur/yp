<?php
declare (strict_types=1);

namespace app\listener\api;


use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;

class UserMine
{
    public function handle($data)
    {
        // if($data['is_mine'] == 2){
        //     /** @var MineUserRepository $mineUserRepository */
        //     $mineUserRepository = app()->make(MineUserRepository::class);
        //     $mineUser = $mineUserRepository->search(['uuid'=>$data['id'],'level'=>1],$data['company_id'])->find();
        //     if(!$mineUser){
        //        $mine = app()->make(MineRepository::class)->search(['level'=>1,'status'=>1],$data['company_id'])->find();
        //        $res1 = Cache::store('redis')->setnx('user_mine' . $data['id'], $data['id']);
        //         Cache::store('redis')->expire('user_mine' . $data['id'], 1);
        //         if (!$res1) return true;
        //         if($mine && $res1 && isset(web_config($data['company_id'], 'program')['output'])){
        //            $mineUser = $mineUserRepository->search(['mine_id'=>$mine['id'],'uuid'=>$data['id']],$data['company_id'])->find();
        //            if(!$mineUser){
        //                $add['mine_id'] = $mine['id'];
        //                $add['uuid'] = $data['id'];
        //                $add['unquide'] = $data['company_id'].$data['id'].$add['mine_id'];
        //                $add['total'] = $mine['output'];
        //                $add['rate'] = $mine['rate'];
        //                $add['dec_num'] = $mine['dec_num'];
        //                $add['total'] = $mine['output'];
        //                $re = $mineUserRepository->addInfo($data['company_id'],$add);
        //                if($re) app()->make(UsersRepository::class)->editInfo($data,['is_mine'=>1]);
        //            }

        //        }
        //     }
        // }
    }
}
