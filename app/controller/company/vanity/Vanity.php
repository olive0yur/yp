<?php

namespace app\controller\company\vanity;

use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;
use app\common\repositories\vanity\VanityRepository;

class Vanity extends Base
{
    protected $repository;

    public function __construct(App $app, VanityRepository $repository)
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
        return $this->fetch('vanity/list', [
            'addAuth' => company_auth('companyVanityAdd'),
            'editAuth' => company_auth('companyVanityEdit'),
            'delAuth' => company_auth('companyVanityDel'),##
            'switchStatusAuth' => company_auth('companyVanitySwitch'),##
            'giveAuth' => company_auth('companyVanityAddGiveSwitch'),##
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'code' => '',
                'price'=>'',
                'status'=>'',
            ]);

            $userRepository = app()->make(UsersRepository::class);
            $isExists = $userRepository->search(['user_code' => $param['code']])->find();
            if ($isExists) $this->error('号码已存在');

            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '靓号添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('vanity/add',['companyId'=>$this->request->companyId]);
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
                'code' => '',
                'price'=>'',
                'status'=>'',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '靓号修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('vanity/edit', [
                'info' => $info,
                'companyId'=>$this->request->companyId
            ]);
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '靓号删除 ids:' . implode(',', $ids), $data);
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
                company_user_log(3, '修改靓号状态 id:' . $id, $param);
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