<?php

namespace app\controller\company\mine;

use think\App;
use app\controller\company\Base;
use app\common\repositories\mine\MineRepository;

class Mine extends Base
{
    protected $repository;

    public function __construct(App $app, MineRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'is_type' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('mine/mine/list', [
            'addAuth' => company_auth('companyMineAdd'),
            'editAuth' => company_auth('companyMineEdit'),
            'delAuth' => company_auth('companyMineDel'), ##
            'switchStatusAuth' => company_auth('companyMineSwitch'), ##
            'giveAuth' => company_auth('companyMineAddGiveSwitch'), ##
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'price' => '',
                'num' => '',
                'output' => '',
                'people' => '0',
                'cover' => '',
                'level' => '',
                'content' => '',
                'rate' => '',
                'day_output' => '',
                'day_output_gold' => '',
                'is_use' => '',
                'max_num' => '',
                'one_output' => '',
                'two_output' => '',
                'three_output' => '',
                'unlock_level' => '',
            ]);
            if ($param['day_output'] > $param['output']) {
                return $this->error('日产出不能大于总产出');
            }
            if ($param['day_output'] == 0) {
                return $this->error('日产出不能为0');
            }

            $quotient = $param['output'] / $param['day_output'];
            if (abs($quotient - round($quotient)) > 0.01) {
                return $this->error('总产出必须为日产出的倍数');
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    company_user_log(3, '权益卡包添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误' . $e->getMessage());
            }
        } else {
            return $this->fetch('mine/mine/add', ['companyId' => $this->request->companyId]);
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
                'name' => '',
                'people' => '0',
                'output' => '',
                'price' => '',
                'num' => '',
                'cover' => '',
                'level' => '',
                'content' => '',
                'rate' => '',
                'day_output' => '',
                'day_output_gold' => '',
                'is_use' => '',
                'node1' => '',
                'node2' => '',
                'node3' => '',
                'max_num' => '',
                'one_output' => '',
                'two_output' => '',
                'three_output' => '',
                'unlock_level' => '',
            ]);
            if ($param['day_output'] > $param['output']) {
                return $this->error('日产出不能大于总产出');
            }
            if ($param['day_output'] == 0) {
                return $this->error('日产出不能为0');
            }

            $quotient = $param['output'] / $param['day_output'];
            if (abs($quotient - round($quotient)) > 0.01) {
                return $this->error('总产出必须为日产出的倍数');
            }
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '矿场修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('mine/mine/edit', [
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
                company_user_log(4, '卡牌删除 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


    public function status()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
            ]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改权益卡包状态 id:' . $id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }

    public function give()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_give' => '',
            ]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改权益卡包开关状态 id:' . $id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }
}
