<?php

namespace app\controller\admin;

use app\common\repositories\system\admin\AdminUserRepository;
use app\common\repositories\system\admin\AdminAuthRuleRepository;
use app\common\repositories\system\article\AfficheRepository;
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
        /** @var AdminUserRepository $adminUserRepository */
        $adminUserRepository = app()->make(AdminUserRepository::class);

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
        /** @var AdminUserRepository $adminUserRepository */
        $adminUserRepository = app()->make(AdminUserRepository::class);
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
        return $this->fetch('index/welcome');
    }

    public function statistics()
    {
        return json()->data([
            'code' => 0,
            'data' => [
                'countData' => [],
                'userCountData' => []
            ]
        ]);
    }

    /**
     * 清除 缓存
     *
     * @return \think\response\Json
     */
    public function clearCache()
    {
        Cache::clear();

        return $this->success('缓存清除成功');
    }

    /**
     * 退出登陆
     *
     * @return \think\response\Json
     */
    public function signOut()
    {
        event('admin.logout');
        return $this->success('退出成功');
    }
}