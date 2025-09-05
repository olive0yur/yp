<?php

namespace app\controller\company\game;

use app\common\repositories\game\LevelRepository;
use think\App;
use app\controller\company\Base;

class Level extends Base
{
    protected $repository;

    public function __construct(App $app, LevelRepository $repository)
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
        return $this->fetch('game/level/list', [
//            'addAuth' => company_auth('companyRoleListAdd'),
//            'editAuth' => company_auth('companyRoleListEdit'),
            'addAuth' => false,
            'editAuth' => false,
            'statusAuth' => company_auth('companyRoleListStatus'),##
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'level' => '',
                'title' => '',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '游戏角色添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('game/level/add');
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
                'level' => '',
                'title' => '',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '游戏角色修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('game/level/edit', [
                'info' => $info,
            ]);
        }
    }


    public function status()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
                'is_giv' => '',
            ]);
            foreach ($param as $key => $vo) if ($vo === '') unset($param[$key]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改游戏角色状态 id:' . $id, $param);
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