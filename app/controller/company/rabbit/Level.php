<?php

namespace app\controller\company\rabbit;

use app\common\repositories\currency\EtcRepository;
use app\common\repositories\rabbit\ToysGearLevelRepository;
use app\common\repositories\rabbit\ToysLevelRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;


class Level extends Base
{
    protected $repository;

    public function __construct(App $app, ToysLevelRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyGameLevelShowAdd'),
            'editAuth' => company_auth('companyGameLevelShowEdit'),
            'delAuth' => company_auth('companyGameLevelShowDel'),
            'detailsAuth' => company_auth('companyGameLevelDetails')
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
        return $this->fetch('rabbit/level/list');
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'lv' => '',
                'exp' => '',
                'up' => '',
                'price' => '',
//                'chance' => '',
                'day_num' => '',
                'max_num' => '',
                'lay_num' => '',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    company_user_log(3, '扭蛋等级添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误' . $e->getMessage());
            }
        } else {
            return $this->fetch('rabbit/level/add', ['companyId' => $this->request->companyId]);
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
                'lv' => '',
                'exp' => '',
                'up' => '',
                'price' => '',
//                'chance' => '',
                'day_num' => '',
                'max_num' => '',
                'lay_num' => '',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '扭蛋等级修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('rabbit/level/edit', [
                'info' => $info,
                'companyId' => $this->request->companyId
            ]);
        }
    }


    public function details()
    {
        $id = $this->request->param('id');

        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        $toysGaearLevelRepository = app()->make(ToysGearLevelRepository::class);
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'gear_level' => '',
                'rate' => ''
            ]);
            $chance = [];
            foreach ($where['gear_level'] as $k => $v) {
                $chance[] = ['gear_level' => $where['gear_level'][$k], 'rate' => $where['rate'][$k]];
            }

            $this->repository->editInfo($info, ['chance' => json_encode($chance)]);
            return $this->success('修改成功');
        }

        $list = $toysGaearLevelRepository->search([], $this->request->companyId)->order('level asc')->select();
        $chance = json_decode($info['chance'], true);
        foreach ($list as &$value) {
            $value['gear_level'] = $value['level'];
            $value['rate'] = isset($chance[$value['level'] - 1]) ? $chance[$value['level'] - 1]['rate'] : 0;
        }
        return $this->fetch('rabbit/level/details', ['list' => $list, 'id' => $id]);
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '扭蛋等级删除 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }
}