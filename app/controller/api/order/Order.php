<?php

namespace app\controller\api\order;

use app\common\repositories\pool\PoolShopOrder;
use app\controller\api\Base;
use think\App;

class Order extends Base
{
    protected $repository;

    public function __construct(App $app)
    {
        parent::__construct($app);
        /** @var PoolShopOrder $repository $repository */
        $repository = app()->make(PoolShopOrder::class);
        $this->repository = $repository;
    }


    public function getList(){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
            'type' => '',
            'is_mark' => '',
            'status'=>''
        ]);
        $data['uuid'] = $this->request->userId();
        return $this->success($this->repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

    public function cannel(){
        $data = $this->request->param(['order_id'=>'']);
        if(!$data['order_id']) return $this->error('订单id错误');
        return $this->success($this->repository->cannel($data,$this->request->userId(),$this->request->companyId),'取消成功');
    }
    public function finish(){
        $data = $this->request->param(['order_id'=>'']);
        if(!$data['order_id']) return $this->error('订单id错误');
        return $this->success($this->repository->finish($data,$this->request->userId(),$this->request->companyId),'收货成功');
    }
    public function getDetail(){
        $data = $this->request->param(['order_id']);
        if(!$data['order_id']) return $this->error('订单编号不能为空!');
        return $this->success($this->repository->getApiDetail(['order_id'=>$data['order_id']],$this->request->userId(),$this->request->companyId));
    }

    public function rebate()
    {
        $data = $this->request->param(['order_id']);
        if(!$data['order_id']) return $this->error('订单编号不能为空!');
        return $this->success($this->repository->apiRebate(['order_id'=>$data['order_id']],$this->request->userId(),$this->request->companyId),'回收成功');
    }

}