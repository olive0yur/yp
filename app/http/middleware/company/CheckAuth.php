<?php

declare (strict_types=1);

namespace app\http\middleware\company;


class CheckAuth
{

    public function handle($request, \Closure $next)
    {
        $ruleName = $request->rule()->getName();
        if (!company_auth($ruleName)) {
            return $request->isAjax() ? json(['code' => -1, 'msg' => '权限不足']) : view('company/error/401');
        }

        return $next($request);
    }
}