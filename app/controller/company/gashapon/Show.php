<?php

namespace app\controller\company\gashapon;

use app\common\repositories\gashapon\GashaponRepository;
use think\App;
use app\controller\company\Base;

class Show extends Base
{
    protected $repository;

    public function __construct(App $app, GashaponRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('gashapon/show/list', [
            'addAuth' => company_auth('companyGashaponAdd'),
            'editAuth' => company_auth('companyGashaponEdit'),
            'delAuth' => company_auth('companyGashaponDel'),##
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'key' => '',
                'mode'=>'',
                'num'=>'',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '扭蛋机添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('gashapon/show/add');
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
                'key' => '',
                'mode'=>'',
                'num'=>'',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '扭蛋机修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('gashapon/show/edit', [
                'info' => $info,
            ]);
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '扭蛋机删除 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


}