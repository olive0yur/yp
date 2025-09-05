<?php

namespace app\controller\company\mine;

use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\user\AgentUserRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\Base;
use think\App;

class MineUserLog extends Base
{
    protected $repository;

    public function __construct(App $app, MineUserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {

        $usersRepository = app()->make(UsersRepository::class);
        if ($this->request->isAjax()) {
            $id = $this->request->param('user_id');
            $where = $this->request->param([
                'reg_time' => '',
                'levels' => ''
            ]);
            if ($id) {
                if (!$usersRepository->exists($id)) {
                    return $this->error('数据不存在');
                }
                $userPushRepository = app()->make(UsersPushRepository::class);
                $userIds = $userPushRepository->search(['parent_id' => $id, 'levels' => $where['levels']], $this->request->companyId)->column('user_id');
                if ($userIds) {
                    $where['uuid'] = $userIds;
                }
            }
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        $userList = $usersRepository->search([],$this->request->companyId)
            ->field('id,mobile,nickname')->select();
        return $this->fetch('mine/userlog/list', [
            'userList' => $userList,
        ]);
    }
}