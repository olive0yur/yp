<?php
declare (strict_types=1);

namespace app\listener\company;

use app\common\repositories\company\user\CompanyUserRepository;

class LogoutSuccess
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        company_user_log(1, '退出登录');

        /**
         * @var CompanyUserRepository $repository
         */
        $repository = app()->make(CompanyUserRepository::class);
        $repository->clearSessionInfo();
    }
}
