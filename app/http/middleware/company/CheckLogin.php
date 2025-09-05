<?php

namespace app\http\middleware\company;

use app\common\repositories\company\user\CompanyUserRepository;

class CheckLogin
{
    public function handle($request, \Closure $next)
    {
        /**
         * @var CompanyUserRepository $repository
         */
        $repository = app()->make(CompanyUserRepository::class);
        $controller = $request->controller(true);
        if (!$repository->isLogin()) {
            if ($controller != 'company.login') {
                if ($request->isAjax()) {
                    return json()->data(['code' => -1, 'msg' => '请先登录']);
                } else {
                    return redirect(url('companyUserLogin'));
                }
            }
        } else {
            if ($controller == 'company.login') {
                return redirect(url('companyIndex'));
            }
            $request->userInfo = $repository->getLoginUserInfo();
            $request->adminId = $repository->getLoginAdminId();
            $request->companyId = $repository->getLoginCompanyId();
        }

        return $next($request);
    }
}