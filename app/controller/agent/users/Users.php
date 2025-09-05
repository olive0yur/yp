<?php

namespace app\controller\agent\users;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\users\user\AgentUserRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use think\App;
use app\controller\agent\Base;
use app\common\repositories\users\UsersRepository;
use app\validate\users\UsersCertValidate;
use app\validate\users\UsersValidate;
use think\exception\ValidateException;
use think\facade\Db;

class Users extends Base
{
    public function pushCountList(UsersPushRepository $repository)
    {
        if ($this->request->isAjax()) {
             /** @var AgentUserRepository $agentUserRepository */
             $agentUserRepository = app()->make(AgentUserRepository::class);
             $userInfo = $agentUserRepository->getLoginUserInfo();
            $where = $this->request->param([
                'parent_id' => $userInfo['id'],
            ]);
            [$page, $limit] = $this->getPage();
            $data = $repository->getAgentList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        }else{
            return $this->fetch('users/users/details');
        }
    }

}