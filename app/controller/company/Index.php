<?php

namespace app\controller\company;

use app\common\repositories\company\user\CompanyUserRepository;
use app\common\repositories\mark\UsersMarkReportRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\property\bill\PropertyRoomBillRepository;
use app\common\repositories\property\PropertyRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
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
         * @var CompanyUserRepository $adminUserRepository
         */
        $adminUserRepository = app()->make(CompanyUserRepository::class);
        $adminUserInfo = $adminUserRepository->getLoginUserInfo();

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
         * @var CompanyUserRepository $adminUserRepository
         */
        $adminUserRepository = app()->make(CompanyUserRepository::class);
        $data = $adminUserRepository->getMenus();

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
        $tokens = web_config($this->request->companyId, 'site.tokens', '代币');
        return $this->fetch('index/welcome', [
            'userNum' => 1,
            'todayUserNum' => 2,
            'todayUserOrderListNum' => 3,
            'todayUserWithdrawNum' => 4,
            'tokens' => $tokens
        ]);
    }

    /**
     * 退出登陆
     *
     * @return \think\response\Json
     */
    public function signOut()
    {
        event('company.logout');
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

        $data = Cache::remember('company_dashboard_statistics1_' . $this->request->companyId, function () {
            $userEchartData = $this->getUserEchartData();
            /** @var  UsersPoolRepository $usersPoolRepository */
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            /** @var PoolSaleRepository $poolSaleRepository */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            return [
                'collection_data' => [
                    'collection_total' => $poolSaleRepository->search([], $this->request->companyId)->sum('num'), // 卡牌数
                    'collection_num' => $usersPoolRepository->search(['status' => 1], $this->request->companyId)->count(), // 卡牌数
                ],
                'userEchartData' => $userEchartData,
                'user_data' => [
                    'food_num' => $usersRepository->search([], $this->request->companyId)->sum('food'),
                    'gold_num' => $usersRepository->search([], $this->request->companyId)->sum('gold'),
                    'score_num' => $usersRepository->search([], $this->request->companyId)->sum('score'),
                    'today_user' => $usersRepository->search([], $this->request->companyId)
                        ->whereTime('add_time', 'today')
                        ->count('id'),
                    'total_user' => $usersRepository->search([], $this->request->companyId)
                        ->count('id'),
                    'is_cert_user' => $usersRepository->search([], $this->request->companyId)
                        ->where('cert_id > 0')
                        ->count('id'),
                    'no_cert_user' => $usersRepository->search([], $this->request->companyId)
                        ->where('cert_id', ' 0')
                        ->count('id'),
                ]
            ];
        }, 1);
        return json()->data([
            'code' => 0,
            'data' => $data
        ]);
    }

    private function getUserEchartData()
    {
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $count = $usersRepository->search([], $this->request->companyId)->where('id', '>=', 0)->count('id');
        $count1 = $usersRepository->search([], $this->request->companyId)->where('id', 0)->count('id');
        $res = [
            ['name' => '未认证', 'count' => $count1],
            ['name' => '已认证', 'count' => $count],
        ];

        return $res;
    }

    public function gameStatistics(UsersPushRepository $repository)
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'user_code' => '',
                'time' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $repository->getLowerGame($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('index/game', [
        ]);

    }

    public function mineStatistics(UsersPushRepository $repository)
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'user_code' => '',
                'time' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $repository->getLowerMine($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('index/mine', [
        ]);
    }

}