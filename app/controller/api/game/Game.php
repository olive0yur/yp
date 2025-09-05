<?php

namespace app\controller\api\game;

use app\common\repositories\game\GameJadeLogLogRepository;
use app\common\repositories\game\RoleRepository;
use app\common\repositories\game\UsersKnspRepository;
use app\common\repositories\game\UsersRoleRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\controller\api\Base;
use think\App;

class Game extends Base
{


    public function __construct(App $app)
    {
        parent::__construct($app);
    }
    public function getRole(UsersRoleRepository $repositsory){
        return $this->success($repositsory->getAll($this->request->userInfo(),$this->request->companyId));
    }

    public function getFalling(RoleRepository $repository){
        return $this->success($repository->getFalling($this->request->userInfo(),$this->request->companyId));
    }

    public function getBack(UsersKnspRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'','uuid'=>$this->request->userId()]);
        return $this->success($repository->getBack($data,$data['page'],$data['limit'],$this->request->userInfo(),$this->request->companyId));
    }

    public function exchange(RoleRepository $repository){
        $data = $this->request->param(['num'=>'','pay_password'=>'','type'=>'']);
        return $this->success($repository->exchange($data,$this->request->userInfo(),$this->request->companyId),'兑换成功');
    }

    public function addJade(GameJadeLogLogRepository $repository){
        $data = $this->request->param(['num'=>'']);
        return $this->success($repository->addJade($data['num'],$this->request->userInfo(),$this->request->companyId),'添加成功');
    }



    /**
     * 获取灵石日志
     */
    public function jadeLogList(GameJadeLogLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type'=>'']);
        return app('api_return')->success($repository->foodLogList($data['type'], $page, $limit, $this->request->userId()));
    }

}