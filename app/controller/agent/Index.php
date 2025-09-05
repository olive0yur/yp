<?php

namespace app\controller\agent;

use app\common\repositories\users\user\AgentUserRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\CacheService;
use think\facade\Cache;

class Index extends Base
{

    /**
     * 首页
     *
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        /**
         * @var AgentUserRepository $agentUserRepository
         */
        $agentUserRepository = app()->make(AgentUserRepository::class);
        $adminUserInfo = $agentUserRepository->getLoginUserInfo();
        return $this->fetch('index/index', compact('adminUserInfo'));
    }

    /**
     * 获取目录
     *
     * @return \think\response\Json
     */
    public function getMenu()
    {
        /**
         * @var AgentUserRepository $agentUserRepository
         */
        $agentUserRepository = app()->make(AgentUserRepository::class);
        $data = $agentUserRepository->getMenus();

        return json()->data($data);
    }

    /**
     * 欢迎页
     *
     * @return string
     * @throws \Exception
     */
    public function welcome()
    {
        return $this->fetch('index/welcome', [
            'userNum' => 1,
            'todayUserNum' => 2,
            'todayUserOrderListNum' => 3,
            'todayUserWithdrawNum' => 4,
        ]);
    }

    /**
     * 退出登陆
     *
     * @return \think\response\Json
     */
    public function signOut()
    {
        event('agent.logout');
        return $this->success('退出成功');
    }

    /**
     * 清除 缓存
     *
     * @return \think\response\Json
     */
    public function clearCache()
    {
        CacheService::init($this->request->companyId)->clear();
        return $this->success('缓存清除成功');
    }



    public function statistics()
    {

        $data = Cache::remember('agent_dashboard_statistics_' . $this->request->companyId, function () {

            return [
                'collection_data' => [
                    'collection_total' => 0, // 卡牌数
                    'collection_num' => 0, // 卡牌数
                ],
            ];
        }, 300);
        return json()->data([
            'code' => 0,
            'data' => $data
        ]);
    }


}