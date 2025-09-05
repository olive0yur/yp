<?php

namespace app\controller\api;

use app\common\repositories\system\PaymentRepository;
use think\App;

class Payment extends Base
{
    protected $repository;

    public function __construct(App $app)
    {
        parent::__construct($app);
        /** @var PaymentRepository $repository $repository */
        $repository = app()->make(PaymentRepository::class);
        $this->repository = $repository;
    }


    public function getList(){
        return $this->success($this->repository->getApiList([],$this->request->companyId));
    }

    public function payment(){
        $data = $this->request->param(['type'=>'','password'=>'','order_id'=>'','payReturn'=>'']);
        if(!$data['type']) return $this->error('请选择支付方式');
        return $this->success($this->repository->payment($data,$this->request->userInfo(),$this->request->companyId));
    }



}