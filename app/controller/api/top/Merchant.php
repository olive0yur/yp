<?php

namespace app\controller\api\top;

use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\top\UsersTopRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;
use think\App;

class Merchant extends Base
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function getconf(){
        $top = web_config($this->request->companyId,'program.top');
        return $this->success($top);
    }
    public function obstacles(UsersRepository $repository){
        $data = $this->request->param(['top_name'=>'']);
         return $this->success($repository->obstacles($data['top_name'],$this->request->userInfo(),$this->request->companyId),'开通成功！');
    }

    public function getList(UsersRepository $repository){
        $data = $this->request->param(['limit'=>'','page'=>'']);
        return $this->success($repository->getTopList($data['page'],$data['limit'],$this->request->companyId));
    }

    public function addTop(UsersTopRepository $repositorye){
        $data = $this->request->param(['user_code'=>'']);
        return $this->success($repositorye->addTop($data,$this->request->userInfo(),$this->request->companyId),'添加成功');
    }

    public function addNum(UsersTopRepository $repository)
    {
        return $this->success($repository->addNum($this->request->userInfo(),$this->request->companyId));
    }

    public function topList(UsersTopRepository $repository)
    {
        $data = $this->request->param(['page'=>'','limit'=>'','top_id'=>'']);
        return $this->success($repository->getApiList($data['top_id'],$data['page'],$data['limit'],$this->request->companyId));
    }
    public function myTopList(UsersTopRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'']);
        return $this->success($repository->getMyTopList($data['page'],$data['limit'],$this->request->userInfo(),$this->request->companyId));
    }

     public function getTopMsg(UsersTopRepository $repositorye){
        $data = $this->request->param(['top_id'=>'']);
        return $this->success($repositorye->getTopMsg($data['top_id'],$this->request->companyId));
     }

     public function getLogList(UsersTopRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'','top_id'=>'','user_code'=>'']);
        return $this->success($repository->getApiTopList($data['user_code'],$data['top_id'],$data['page'],$data['limit'],$this->request->companyId));
     }

     public function reportDay(MineGivLogRepository $repository){
         $data = $this->request->param(['top_id'=>'','limit'=>'','page'=>'']);
         return $this->success($repository->reportDay($data['top_id'],$data['page'],$data['limit'],$this->request->companyId));
     }

    public function reportMon(MineGivLogRepository $repository){
        $data = $this->request->param(['top_id'=>'','limit'=>'','page'=>'']);
        return $this->success($repository->reportMon($data['top_id'],$data['page'],$data['limit'],$this->request->companyId));
    }
    public function reportYer(MineGivLogRepository $repository){
        $data = $this->request->param(['top_id'=>'','limit'=>'','page'=>'']);
        return $this->success($repository->reportYer($data['top_id'],$data['page'],$data['limit'],$this->request->companyId));
    }
}