<?php

namespace app\controller\company\gashapon;

use app\common\repositories\gashapon\GashaponRepository;
use app\common\repositories\gashapon\GashaponToRepository;
use think\App;
use app\controller\company\Base;

class ToShow extends Base
{
    protected $repository;

    public function __construct(App $app, GashaponToRepository $repository)
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
        return $this->fetch('gashapon/toshow/list', [
            'addAuth' => company_auth('companyGashaponToAdd'),
            'editAuth' => company_auth('companyGashaponToEdit'),
            'delAuth' => company_auth('companyGashaponToDel'),##
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'number' => '',
                'count'=>'',
                'type'=>'',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '赠送扭蛋机添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('gashapon/toshow/add');
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
                'number' => '',
                'count'=>'',
                'type'=>'',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '赠送扭蛋机修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('gashapon/toshow/edit', [
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
                company_user_log(4, '赠送扭蛋机删除 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


}