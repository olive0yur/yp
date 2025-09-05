<?php

namespace app\controller\api\rabbit;

use app\common\repositories\rabbit\ToysBackpackRepository;
use app\common\repositories\rabbit\ToysGearCateRepository;
use app\common\repositories\rabbit\ToysGearRepository;

use app\common\repositories\rabbit\ToysLevelRepository;
use app\controller\api\Base;
use think\App;

class Toys extends Base
{
    protected $repository;

    public function __construct(App $app, ToysGearRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    public function getList(ToysBackpackRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getMyGear($this->request->userInfo(), $page, $limit, $this->request->companyId));
    }

    public function level(ToysLevelRepository $repository)
    {
        return $this->success($repository->getApiList([], $this->request->companyId));
    }

    public function lay(ToysBackpackRepository $repository)
    {
        return $this->success($repository->lay($this->request->userInfo(), $this->request->companyId));
    }

    public function up(ToysBackpackRepository $repository)
    {
        return $this->success($repository->up($this->request->userInfo(), $this->request->companyId));
    }

    public function myDetails(ToysBackpackRepository $repository)
    {
        return $this->success($repository->getConf($this->request->userInfo(), $this->request->companyId));
    }

    public function sub(ToysBackpackRepository $repository)
    {
        $param = $this->request->param(['gear_id' => '']);
        if (!$param['gear_id']) return $this->error('请选择装备');
        return $this->success($repository->sub($this->request->param(), $this->request->userInfo(), $this->request->companyId));
    }

    public function down(ToysBackpackRepository $repository)
    {
        $param = $this->request->param(['gear_id' => '', 'id' => '']);
        if (!$param['gear_id']) return $this->error('请选择装备');
        if (!$param['id']) return $this->error('请选择装备');
        return $this->success($repository->down($this->request->param(), $this->request->userInfo(), $this->request->companyId));
    }

    public function log(ToysBackpackRepository $repository)
    {
        return $this->success($repository->log($this->request->userInfo(), $this->request->companyId));
    }

    public function receive(ToysBackpackRepository $repository)
    {
        $param = $this->request->param(['task_id' => '']);
        if (!$param['task_id']) return $this->error('请选择任务领取');
        return $this->success($repository->receive($this->request->param(), $this->request->userInfo(), $this->request->companyId));
    }

    public function getGear(ToysGearCateRepository $repository)
    {
        return $this->success($repository->getGear($this->request->userInfo(), $this->request->companyId));
    }

    public function firmLog(ToysGearCateRepository $repository)
    {
        $param = $this->request->param(['type' => '']);
        if (!$param['type']) return $this->error('请选择装备部位');
        [$page, $limit] = $this->getPage();
        return $this->success($repository->firmLog($param, $this->request->userInfo(), $this->request->companyId, $page, $limit));
    }

    public function firm(ToysGearCateRepository $repository)
    {
        $param = $this->request->param(['type' => '', 'num' => 1]);
        if (!$param['type']) return $this->error('请选择装备部位');
        return $this->success($repository->firm($param, $this->request->userInfo(), $this->request->companyId));
    }

    public function complete(ToysBackpackRepository $repository)
    {
        $param = $this->request->param(['id' => '']);
        if (!$param['id']) return $this->error('请选择任务');
        return $this->success($repository->complete($param, $this->request->userInfo(), $this->request->companyId));
    }
}