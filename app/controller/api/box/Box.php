<?php

namespace app\controller\api\box;

use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;

class Box extends Base
{
    protected $repository;
    public $usersBoxRepository;

    public function __construct(App $app)
    {
        parent::__construct($app);
        /** @var BoxSaleRepository $repository $repository */
        $repository = app()->make(BoxSaleRepository::class);
        $this->usersBoxRepository = app()->make(UsersBoxRepository::class);
        $this->repository = $repository;
    }


    public function getList(){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
        ]);
        return $this->success($this->repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

    public function getReceiveList(){
        return $this->success($this->repository->getApiReceiveList($this->request->companyId,$this->request->userInfo()));
    }

    public function getDetail(){
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        return $this->success($this->repository->getApiDetail($data['id'],$this->request->userId(),$this->request->companyId));
    }

    public function getBox()
    {
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        return $this->success($this->repository->getApiGoods($data['id'],$this->request->companyId));
    }

    public function buy(){
        $data = $this->request->param(['id'=>'','num'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        if(!$data['num']) return $this->error('请输入购买数量');
        return $this->success($this->repository->apiBuy($data['id'],$this->request->userInfo(),$data['num'],$this->request->companyId));
    }

    public function apiBuy(usersBoxRepository $repository)
    {
        $data = $this->request->param(['box_id'=>'','num'=>'','multiple'=>'']);
        if(!$data['box_id']) return $this->error('ID不能为空');
        if(!(int)$data['num']) return $this->error('请输入购买数量');
        if(!(int)$data['multiple']) $data['multiple'] = 1;
        return $this->success($repository->apiBuy($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function receive(){
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        return $this->success($this->repository->apiReceive($data['id'],$this->request->userInfo(),$this->request->companyId));
    }

    public function giv(UsersBoxRepository $repository){
        $data = $this->request->param(['phone'=>'','id'=>'','pay_password'=>'']);
        if(!$data['phone']) return $this->error('请输入接收人账号');
        if(!$data['id']) return $this->error('请选择要赠送的肓盒');
        if(!$data['pay_password']) return $this->error('请输入交易密码！');
        return  $this->success($repository->send($data,$this->request->userInfo(),$this->request->companyId),'赠送成功!');
    }

    public function getMyList(UsersBoxRepository $repository){
        $data = $this->request->param(['limit'=>'','page'=>'','title'=>'']);
        return $this->success($repository->getApiMyList($data,$data['page'],$data['limit'],$this->request->companyId,$this->request->userId()));
    }


    public function getMyListInfo(UsersBoxRepository $repository){
        $data = $this->request->param(['limit'=>'','page'=>'','box_id'=>'']);
        return $this->success($repository->getApiMyListInfo($data,$data['page'],$data['limit'],$this->request->companyId,$this->request->userId()));
    }
    public function getMyInfo(UsersBoxRepository $repository){
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('【id】参数错误!');
        return $this->success($repository->getMyInfo($data,$this->request->companyId,$this->request->userId()));
    }


    public function givLog(GivLogRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'','buy_type'=>2]);
        return $this->success($repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId,$this->request->userId()));
    }

    ## 开肓盒
    public function open(UsersBoxRepository $repository){
        $data = $this->request->param(['id'=>'']);
        return $this->success($repository->open($data,$this->request->userInfo(),$this->request->companyId),'激活成功');
    }

    public function openAll(UsersBoxRepository $repository){
        $data = $this->request->param(['box_id'=>'','num'=>'']);
        return $this->success($repository->openAll($data,$this->request->userInfo(),$this->request->companyId));
    }
    public function openAllLog(UsersBoxRepository $repository){
        $data = $this->request->param(['open_no'=>'']);
        return $this->success($repository->openAllLog($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function openLog(UsersBoxRepository $repository){
        $data = $this->request->param(['status'=>6,'open_type'=>'','page'=>'','limit'=>'','box_id'=>'']);
        return $this->success($repository->getApiOpenList($data,$data['page'],$data['limit'],$this->request->userId(),$this->request->companyId));
    }

    public function recovery(UsersBoxRepository $repository)
    {
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('请输入');
        return $this->success($repository->Apirecovery($data,$this->request->userId(),$this->request->companyId),'回收成功');
    }
}