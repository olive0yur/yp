<?php

namespace app\controller\company\dragon;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\DragonCannelRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersGroupRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use think\App;
use app\controller\company\Base;
use app\common\repositories\users\UsersRepository;
use app\validate\users\UsersCertValidate;
use app\validate\users\UsersValidate;
use think\exception\ValidateException;
use think\facade\Db;

class Dragon extends Base
{
    protected $repository;

    public function __construct(App $app, DragonCannelRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'statusAuth' => company_auth('companyDragonListStatus'),
        ]);
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        } else {
            return $this->fetch('dragon/dragon/list');
        }
    }

    public function status()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
                'status_time' => date('Y-m-d H:i:s')
            ]);
            $info = $this->repository->get($param['id']);
            if (!$info) {
                return $this->error('数据不存在');
            }
            if($info['status'] != 1) return $this->error('当前状态无法进行此操作!');
                try {
                    $res = $this->repository->update($id, $param);
                    /**
                     * @var UsersFoodLogRepository $usersFoodLogRepository
                     */
                    $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
                    switch ($param['status']) {
                        case 2: //通过
                            /** @var UsersRepository $usersRepository */
                            $usersRepository = $this->app->make(UsersRepository::class);
                            $userInfo = $usersRepository->search([])->where('id', $info['uuid'])->field('id,food,pledge,pledge_num')->find();
                            if(!$userInfo) return $this->error('提交申请用户状态异常');
                            $beforeChange = $userInfo['food'];
                            $change = bcadd($userInfo['food'], $userInfo['pledge_num'], 2);
                            $afterChange = $change;
                            $usersRepository->editInfo($userInfo, ['food' => $change, 'pledge' => 2, 'pledge_num' => 0]);

                             $usersFoodLogRepository->addLog($userInfo['id'], $userInfo['pledge_num'], 4, array_merge(['remark' => '取消质押-成功'], [
                                'before_change' => $beforeChange,
                                'after_change' => $afterChange,
                                'track_port' => 4,
                            ]));
                            break;
                        case 3: //拒绝
                            $usersRepository = $this->app->make(UsersRepository::class);
                            $userInfo = $usersRepository->search([])->where('id', $info['id'])->field('id,food,pledge,pledge_num')->find();
                            $beforeChange = $userInfo->food;
                            $change = bcadd($userInfo->food, $userInfo['pledge_num'], 2);
                            $afterChange = $change;
                            $usersFoodLogRepository->addLog($userInfo['id'], 0, 4, array_merge(['remark' => '取消质押-拒绝'], [
                                'before_change' => $beforeChange,
                                'after_change' => $afterChange,
                                'track_port' => 4,
                            ]));
                            break;
                    }
                    company_user_log(3, '修改质押申请状态 id:' . $id, $param);
                    if ($res) {
                        return $this->success('修改成功');
                    } else {
                        return $this->error('修改失败');
                    }
                } catch (\Exception $e) {
                    return $this->error($e->getMessage());
                }
        }
    }

}