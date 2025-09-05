<?php

namespace app\controller\api\agent;

use app\common\repositories\agent\AgentRepository;
use app\controller\api\Base;
use think\App;

class Agent extends Base
{
    protected $repository;

    public function __construct(App $app, AgentRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    public function getApiList()
    {
        $where = $this->request->param(['lat' => '', 'lng' => '','status'=>1]);
        if(!$where['lat'] || !$where['lng']) return $this->error('请获取经纬度!');
        [$page,$limit] = $this->getPage();
        return $this->success($this->repository->getApiList($where,$page,$limit, $this->request->companyId));
    }

}