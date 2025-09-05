<?php

namespace app\controller\agent\users;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\users\user\AgentUserRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\agent\Base;


class UsersChild extends Base
{
    protected $repository;

    protected $userInfo;
    public function __construct(App $app, AgentRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => true,##
            'editAuth' => true,##
            'delAuth' => true,##
        ]);

        /** @var AgentUserRepository $agentUserRepository */
        $agentUserRepository = $this->app->make(AgentUserRepository::class);
        $this->userInfo = $agentUserRepository->getLoginUserInfo();
    }

    protected function commonParams()
    {
        /** @var AgentUserRepository $agentUserRepository */
        $agentUserRepository = app()->make(AgentUserRepository::class);
        $userInfo = $agentUserRepository->getLoginUserInfo();
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);
        $ids = $usersPushRepository->search(['parent_id'=>$userInfo['id']])->whereIn('levels',[1])->column('user_id');
        $usersRepository = app()->make(UsersRepository::class);
        $userList = $usersRepository->search([],$this->request->companyId)->whereIn('id',$ids)
            ->field('id,mobile,nickname')->select();
        $this->assign([
            'userList' => $userList,
        ]);
    }

    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getAgentList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('users/agent/list');
    }

    /**
     * 添加常见问题
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'uuid' => '',
                'wechat' => '',
                'qq' => '',
                'address' => '',
                'lng' => '',
                'lat' => '',
                'level' => '',
                'lv' => '',
            ]);
            if($param['lv'] < 0 || $param['lv'] > 1) return $this->error('返利%错误！');

            /** @var AgentRepository $agentRepository */
            $agentRepository = $this->app->make(AgentRepository::class);
            $lv = $agentRepository->search([])->where(['uuid'=>$this->userInfo['id']])->value('lv');
            if($lv && $param['lv'] >= $lv) return $this->error('当前比例不能为上级大');
            $childs = app()->make(UsersPushRepository::class)->search(['parent_id'=>$param['uuid']])->select();
            foreach ($childs as $value){
                $ch_lv = $agentRepository->search([])->where(['uuid'=>$value['user_id']])->value('lv');
                if($ch_lv && $ch_lv >= $param['lv']) return $this->error('不可低于下级');
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('users/agent/add');
        }
    }

    /**
     * 编辑
     */

    public function edit()
    {
        $id = (int)$this->request->param('id');
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
                'uuid' => '',
                'wechat' => '',
                'qq' => '',
                'address' => '',
                'lng' => '',
                'lat' => '',
                'level' => '',
                'lv' => '',
            ]);
            if($param['lv'] < 0 || $param['lv'] > 1) return $this->error('返利%错误！');
            /** @var AgentRepository $agentRepository */
            $agentRepository = $this->app->make(AgentRepository::class);
            $lv = $agentRepository->search([])->where(['uuid'=>$this->userInfo['id']])->value('lv');
            if($lv && $param['lv'] >= $lv) return $this->error('当前比例不能为上级大');
            $childs = app()->make(UsersPushRepository::class)->search(['parent_id'=>$param['uuid']])->select();
            foreach ($childs as $value){
                $ch_lv = $agentRepository->search([])->where(['uuid'=>$value['user_id']])->value('lv');
                if($ch_lv && $ch_lv >= $param['lv']) return $this->error('不可低于下级');
            }
            try {
                $res = $this->repository->editInfo($id, $param,$info);
                if ($res) {
                    company_user_log(4, '编辑代理 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commonParams();
            return $this->fetch('users/agent/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除常见问题
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除常见问题 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 常见问题显示开关
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->editInfo($id, ['status'=>$status]);
        if ($res) {
            company_user_log(4, '修改代理状态 id:' . $id, [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

}