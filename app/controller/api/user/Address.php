<?php

namespace app\controller\api\user;

use app\common\repositories\users\UsersAddressRepository;
use app\controller\api\Base;
use think\App;

class Address extends Base
{
    protected $repository;

    public function __construct(App $app,UsersAddressRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList(){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
            'type'=>'',
            'uuid'=>$this->request->userId(),
        ]);
        return $this->success($this->repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

    public function add(){
        $data = $this->request->param(['name'=>'','phone'=>'','type'=>'','province'=>'','city'=>'','area'=>'','address'=>'','uuid'=>$this->request->userId()]);
        if(!$data['name']) return $this->error('请输入收货人姓名');
        if(!$data['phone']) return $this->error('请输入收货人手机号');
        if(!$data['province']) return $this->error('请输入收货人省份');
        if(!$data['city']) return $this->error('请输入收货人城市');
        if(!$data['area']) return $this->error('请输入收货人区/县');
        if(!$data['address']) return $this->error('请输入收货人具体地址');
        $res = $this->repository->addInfo($this->request->companyId,$data);
        if($res !== false){
            if($data['type'] == 1){
                $this->repository->search(['uuid'=>$data['uuid']],$this->request->companyId)->where('id <>'.$res['id'])->update(['type'=>0]);
            }
            return $this->success();
        }else{
            return $this->error('网络错误');
        }
    }

    public function edit(){
        $data = $this->request->param(['id'=>'','name'=>'','type'=>'','phone'=>'','province'=>'','city'=>'','area'=>'','address'=>'','uuid'=>$this->request->userId()]);
        if(!$data['id']) return $this->error('地址ID不能为空!');
        if(!$data['name']) return $this->error('请输入收货人姓名');
        if(!$data['phone']) return $this->error('请输入收货人手机号');
        if(!$data['province']) return $this->error('请输入收货人省份');
        if(!$data['city']) return $this->error('请输入收货人城市');
        if(!$data['area']) return $this->error('请输入收货人区/县');
        if(!$data['address']) return $this->error('请输入收货人具体地址');
        $info = $this->repository->getApiDetail($data['id'],$this->request->userId(),$this->request->companyId);
        if(!$info) return $this->error('地址不存在!');
        $res = $this->success($this->repository->editInfo($info,$data));
        if ($res !== false){
            if($data['type'] == 1){
                $this->repository->search(['uuid'=>$data['uuid']],$this->request->companyId)->where('id <>'.$data['id'])->update(['type'=>0]);
            }
            return $this->success();
        }else{
            return $this->error('网络错误');
        }
    }

    public function del(){
        $data = $this->request->param(['id'=>'','name'=>'','phone'=>'','province'=>'','city'=>'','area'=>'','address'=>'','uuid'=>$this->request->userId()]);
        if(!$data['id']) return $this->error('地址ID不能为空!');
        $info = $this->repository->getApiDetail($data['id'],$this->request->userId(),$this->request->companyId);
        if(!$info) return $this->error('地址不存在!');
        if($info->delete()) return $this->success([],'删除成功');
        return $this->error('网络错误，删除失败！');
    }


}