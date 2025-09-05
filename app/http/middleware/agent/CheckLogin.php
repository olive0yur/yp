<?php

namespace app\http\middleware\agent;


use app\common\repositories\users\user\AgentUserRepository;

class CheckLogin
{
    public function handle($request, \Closure $next)
    {
        /**
         * @var AgentUserRepository $repository
         */
        $repository = app()->make(AgentUserRepository::class);
        $controller = $request->controller(true);

        if (!$repository->isLogin()) {
            if ($controller != 'agent.login') {
                if ($request->isAjax()) {
                    return json()->data(['code' => -1, 'msg' => '请先登录']);
                } else {
                    return redirect(url('agentUserLogin'));
                }
            }
        } else {
            if ($controller == 'agent.login') {
                return redirect(url('agentIndex'));
            }
            $request->userInfo = $repository->getLoginUserInfo();
            $request->adminId = $repository->getLoginAdminId();
            $request->companyId = $repository->getLoginCompanyId();
        }

        return $next($request);
    }
}