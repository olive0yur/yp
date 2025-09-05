<?php

namespace app\controller\api\pool;

use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;

class Pool extends Base
{
    protected $repository;
    public $usersPoolRepository;
    public function __construct(App $app)
    {
        parent::__construct($app);
        /** @var PoolSaleRepository $repository $repository */
        $repository = app()->make(PoolSaleRepository::class);
        /** @var UsersPoolRepository usersPoolRepository */
        $this->usersPoolRepository =  app()->make(UsersPoolRepository::class);
        $this->repository = $repository;
    }


    public function getList(PoolSaleRepository $repository){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
            'type' => '',
//            'get_type'=>"",
            'brand_id'=>'',
        ]);
            return $this->success($repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }
    /**
     * 卡牌详情
     * @return mixed
     */
    public function getDetail(){
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        return $this->success($this->repository->getApiDetail($data['id'],$this->request->userId(),$this->request->companyId));
    }

    public function buy(){
        $data = $this->request->param(['id'=>'','num'=>'','address_id'=>'','phone'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        if(!$data['num']) return $this->error('请输入购买数量');
        if($data['num'] < 1) return $this->error('购买数量错误!');
//        if(!$data['address_id']) return $this->error('请选择收获地址!');
        return $this->success($this->repository->apiBuy($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function giv(UsersPoolRepository $repository){
        $data = $this->request->param(['user_code'=>'','id'=>'','pay_password'=>'','pool_id'=>'','num'=>1]);
        if(!$data['user_code']) return $this->error('请输入接收人ID');
        if(!$data['id']) return $this->error('请选择要赠送的卡牌');
        if(!$data['pool_id']) return $this->error('参数[pool_id]错误');
        return  $this->success($repository->send($data,$this->request->userInfo(),$this->request->companyId),'赠送成功!');
    }

    //每500毫秒一次，共10次无内容返回显示转赠失败
    public function getTransferEnd(GivLogRepository $repository)
    {
        $data = $this->request->param(['user_pool_id'=>'','pool_id'=>'','order_no'=>'']);
        if(!$data['user_pool_id']) return $this->error('请选择赠送卡牌id!');  //user_pool  id
        if(!$data['pool_id']) return $this->error('请选择卡牌!');        //pool_sale  id
        return  $this->success($repository->getTransferEnd($data,$this->request->userInfo(),$this->request->companyId));
    }

    ## 我的卡牌
    public function getMyList(){
        $data = $this->request->param(['limit'=>'','page'=>'','title'=>'','type'=>'','is_dis'=>'']);
        return $this->success($this->usersPoolRepository->getApiMyList($data,$data['page'],$data['limit'],$this->request->companyId,$this->request->userInfo()));
    }
    ## 我的卡牌
    public function getMySingleList(){
        $data = $this->request->param(['limit'=>'','page'=>'','title'=>'','type'=>'','is_dis'=>'']);
        return $this->success($this->usersPoolRepository->getApiMySingleList($data,$data['page'],$data['limit'],$this->request->companyId,$this->request->userInfo()));
    }


    public function getMyListInfo(UsersPoolRepository $usersPoolRepository){
        $data = $this->request->param(['pool_id'=>'']);
        $data['uuid'] = $this->request->userId();
        [$page, $limit] = $this->getPage();
        return $this->success($usersPoolRepository->getApiMyListInfo($data,$page,$limit,$this->request->companyId));
    }

    public function getMyInfo(){
        $data = $this->request->param(['id'=>'']);
        return $this->success($this->usersPoolRepository->getApiMyInfo($data,$this->request->companyId,$this->request->userId()));
    }


    public function givLog(GivLogRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'','buy_type'=>1,'uuid'=>$this->request->userId(),'type'=>'']);
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList($data,$data['type'],$page,$limit,$this->request->companyId,$this->request->userId()));
    }

    public function search(PoolSaleRepository $repository){
       $data = $this->request->param(['title'=>'']);
       return $this->success($repository->getApiSearch($data,$this->request->companyId));
    }

    public function ceshi(){
       $r = get_rate(7,26);
       dump($r);die;
    }



}