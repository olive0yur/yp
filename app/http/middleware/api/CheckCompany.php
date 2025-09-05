<?php

declare (strict_types=1);

namespace app\http\middleware\api;

use app\common\repositories\company\CompanyRepository;
use app\exception\ApiException;
use app\http\response\api\StatusCode;
use think\facade\Request;

class CheckCompany
{

    public function handle($request, \Closure $next)
    {
        $companyId = (int)$request->header('company-code');
        if ($companyId <= 0) {
            throw new ApiException('企业信息错误', StatusCode::LOGIN_CODE);
        }
        /**
         * @var CompanyRepository $companyRepository
         */
        $companyRepository = app()->make(CompanyRepository::class);
        if (!$companyRepository->exists($companyId)) {
            throw new ApiException('企业不存在', StatusCode::LOGIN_CODE);
        }
        $request->companyId = $companyId;
        return $next($request);
    }
}