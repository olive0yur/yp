<?php

namespace app\controller\company\rabbit;

use app\common\repositories\currency\EtcRepository;
use app\common\repositories\rabbit\ToysGearLevelRepository;
use app\common\repositories\rabbit\ToysGearRepository;
use app\common\repositories\rabbit\ToysLevelRepository;
use app\common\repositories\rabbit\ToysTaskLogRepository;
use app\common\repositories\rabbit\ToysTaskRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;


class GearTask extends Base
{
    protected $repository;

    public function __construct(App $app, ToysTaskRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyGearTaskAdd'),
            'editAuth' => company_auth('companyGearTaskEdit'),
            'delAuth' => company_auth('companyGearTaskDel'),
            'detailsAuth' => company_auth('companyGearTaskLog')
        ]);
    }

    protected function commonParams()
    {

        $this->assign([

        ]);
    }

    public function list()
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('rabbit/gear_task/list');
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'num' => '',
                'price' => '',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    company_user_log(3, '分解红包 添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误' . $e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('rabbit/gear_task/add', ['companyId' => $this->request->companyId]);
        }
    }


    public function edit()
    {
        $id = $this->request->param('id');

        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'num' => '',
                'price' => '',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '分解红包 修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('rabbit/gear_task/edit', [
                'info' => $info,
                'companyId' => $this->request->companyId
            ]);
        }
    }


    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '分解红包 删除 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    public function log()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'task_id' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = app()->make(ToysTaskLogRepository::class)->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('rabbit/gear_task/log', ['task_id' => $_GET['task_id']]);
    }
}