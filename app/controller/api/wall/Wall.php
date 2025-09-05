<?php

namespace app\controller\api\wall;

use app\common\repositories\users\UsersWallRepository;
use app\common\repositories\wall\WallRepository;
use app\controller\api\Base;
use think\App;

class Wall extends Base
{
    protected $repository;

    public function __construct(App $app,WallRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList(){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
        ]);
        return $this->success($this->repository->getApiList($data,$this->request->userId(),$data['page'],$data['limit'],$this->request->companyId));
    }

    public function log(UsersWallRepository $repository)
    {
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
        ]);
        return $this->success($repository->getApiList(['uuid' => $this->request->userId()],$data['page'],$data['limit'],$this->request->companyId));
    }

    public function buy(){
        $data = $this->request->param(['id' => '']);
        if(!$data['id']) return $this->error('请选择阶段!');
        return $this->success($this->repository->buyWall($data['id'],$this->request->userInfo(),$this->request->companyId),'购买成功');
    }

    public function getAward(){
        $data = $this->request->param(['id' => '']);
        if(!$data['id']) return $this->error('请选择阶段!');
        return $this->success($this->repository->getAward($data['id'],$this->request->userInfo(),$this->request->companyId),'领取成功');
    }


}