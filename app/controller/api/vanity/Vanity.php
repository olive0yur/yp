<?php

namespace app\controller\api\vanity;

use app\common\repositories\vanity\VanityRepository;
use app\controller\api\Base;
use think\App;

class Vanity extends Base
{
    protected $repository;

    public function __construct(App $app,VanityRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList(){
        $data = $this->request->param([
            'limit' => 15,
            'page' => 1,
        ]);
        return $this->success($this->repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

    public function buy(){
        $data = $this->request->param(['id' => '']);
        if(!$data['id']) return $this->error('靓号ID不能为空!');
        return $this->success($this->repository->buyVanityCode($data['id'],$this->request->userInfo(),$this->request->companyId),'购买成功');
    }


}