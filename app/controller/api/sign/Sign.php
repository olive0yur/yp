<?php

namespace app\controller\api\sign;

use app\common\repositories\sign\SignAbcRepository;
use app\common\repositories\sign\SignRepository;
use app\common\repositories\sign\SignSetRepository;
use app\controller\api\Base;
use think\App;

class Sign extends Base
{
    protected $repository;

    public function __construct(App $app, SignSetRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }


    public function signConf(){
        return $this->success($this->repository->getCong($this->request->userInfo(),$this->request->companyId));
    }

    public function sign(SignRepository $repository){
         return $this->success($repository->sign($this->request->userInfo(),$this->request->companyId));
    }

    public function openBox(SignRepository $repository){
        $data  = $this->request->param(['num'=>'']);
        if(!in_array($data['num'],[1,10])) return $this->error('开盒数量错误！');
        return $this->success($repository->openBox($data['num'],$this->request->userInfo(),$this->request->companyId));
    }
}