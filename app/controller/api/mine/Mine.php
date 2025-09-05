<?php

namespace app\controller\api\mine;

use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;

class Mine extends Base
{

    public function landList(MineRepository $repository)
    {
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList(['status'=>1],$page,$limit,$this->request->companyId));
    }

    public function develop(MineRepository $repository){
        $data = $this->request->param(['mine_id'=>'']);
        return $this->success($repository->develop($data,$this->request->userInfo(),$this->request->companyId));
    }
    public function getMyMine(MineUserRepository $repository){
        [$page,$limit] = $this->getPage();
        $data = $this->request->param(['type'=>'']);
        return $this->success($repository->getApiList($data,$page,$limit,$this->request->userInfo(),$this->request->companyId));
    }

    public function rank(MineUserRepository $repository){
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getRank($page,$limit,$this->request->userInfo,$this->request->companyId));
    }

    public function getFriendMine(UsersPushRepository $repository){
        return $this->success($repository->getFriendMine($this->request->userInfo(),$this->request->companyId));
    }

    public function introduce(MineRepository $repository){
        $data = $this->request->param(['mine_id'=>'']);
        return $this->success($repository->getIntroduce($data['mine_id'],$this->request->companyId));
    }

    public function dispatch(MineUserRepository $repository)
    {
        $data = $this->request->param(['num'=>'']);
        if(!isset($data['num']) || empty($data['num'])) return $this->error('请填写数量');
        return $this->success($repository->dispatch($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function run(UsersRepository $repository){
        return $this->success($repository->run($this->request->userId(),$this->request->companyId),'');
    }

}