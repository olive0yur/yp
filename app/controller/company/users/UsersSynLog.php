<?php

namespace app\controller\company\users;

use app\common\repositories\active\syn\ActiveSynLogRepository;
use think\App;
use app\controller\company\Base;

class UsersSynLog extends Base
{

    protected $repository;

    public function __construct(App $app, ActiveSynLogRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0,'data' => $res['list'],'count' => $res['count']]);
        }
        return $this->fetch('users/synLog/list');
    }

}