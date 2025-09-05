<?php

namespace app\controller\company\mine;

use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\Base;
use think\App;

class MineGiv extends Base
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
                'keywords' => '',
                'reg_time' => '',
                'mobile' => '',
            ]);
            $where['log_type'] = 6;
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            $this->assign([
                'sum' => $data['sum']
            ]);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'], 'sum' => $data['sum'] ]);
        }
        return $this->fetch('mine/giv/list', [
        ]);
    }

}