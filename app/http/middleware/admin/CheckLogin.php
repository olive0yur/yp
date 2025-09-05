<?php

namespace app\http\middleware\admin;

use app\common\repositories\system\admin\AdminUserRepository;

class CheckLogin
{
    public function handle($request, \Closure $next, $force = true)
    {
        /** @var AdminUserRepository $repository */
        $repository = app()->make(AdminUserRepository::class);
        $controller = $request->controller(true);
        $request->isLogin = $repository->isLogin();
        if (!$request->isLogin && $force) {
            if ($request->isAjax()) {
                return json()->data(['code' => -1, 'msg' => '请先登录']);
            } else {
                return redirect(url('adminLoginPage'));
            }
        } else {
            $request->userInfo = $repository->getLoginUserInfo();
            $request->adminId = $repository->getLoginAdminId();
        }

        return $next($request);
    }
}