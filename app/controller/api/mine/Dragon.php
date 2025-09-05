<?php

namespace app\controller\api\mine;

use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\controller\api\Base;

class Dragon extends Base
{
     public function getConf(){
        $dragon = web_config($this->request->companyId,'program.colors','');
        return $this->success($dragon);
     }

    public function pledge(MineRepository $repository){
        return $this->success($repository->pledge($this->request->userInfo(),$this->request->companyId),'质押成功');
    }
    public function syn(MineRepository $repository){
         $data = $this->request->param(['num'=>'']);
         if($data['num'] < 1) return $this->error('最小数量为1');
         return $this->success($repository->syn($data['num'],$this->request->userInfo(),$this->request->companyId),'合成成功');
    }

    public function cannelPledge(MineRepository $repository){
        return $this->success($repository->cannelPledge($this->request->userInfo(),$this->request->companyId),'申请成功，待审核');
    }

    public function giv(MineUserRepository $repository){
        $data = $this->request->param(['user_code'=>'','pay_password'=>'','num'=>1]);
        if(!$data['user_code']) return $this->error('请输入接收人ID');
        return  $this->success($repository->send($data,$this->request->userInfo(),$this->request->companyId),'赠送成功!');
    }
}