<?php

namespace app\controller\agent\users;

use app\common\repositories\users\UsersFoodLogRepository;
use think\App;
use app\controller\agent\Base;

class Foodlod extends Base
{

    protected $repository;

    public function __construct(App $app, UsersFoodLogRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'user_id' => '',
                'mobile' => '',
                'keyword' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getAgentList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $res['list'],'count' => $res['count']]);
        }
        return $this->fetch('users/foodlog/list');
    }


}