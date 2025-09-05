<?php

namespace app\controller\company\system;

use app\common\repositories\game\LevelTeamRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\system\PaymentRepository;
use app\common\services\PaymentService;
use app\controller\company\Base;
use app\validate\system\PaymentValidate;
use think\App;

class Level extends Base
{
    protected $repository;

    public function __construct(App $app, LevelTeamRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'level' => '',
                'is_syn' => '',
                'state' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('system/level/list', [
            'addAuth' => company_auth('companyDataLevelAdd'),
            'editAuth' => company_auth('companyDataLevelEdit'),
            'delAuth' => company_auth('companyDataLevelDel'),
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'level' => '',
                'under_push' => '',
                'under_require' => '',
                'success_rate' => '',
                'team_push' => '',
                'dividend' => '',
                'send_mine' => '',
                'rate' => '',
            ]);

            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->paymentType();
            return $this->fetch('system/level/add');
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
                'id' => '',
                'name' => '',
                'level' => '',
                'under_push' => '',
                'under_require' => '',
                'team_push' => '',
                'dividend' => '',
                'send_mine' => '',
                'rate' => '',
            ]);

            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $mine = [];
            return $this->fetch('system/level/edit', [
                'info' => $info,
                'mine' => $mine
            ]);
        }
    }


    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}