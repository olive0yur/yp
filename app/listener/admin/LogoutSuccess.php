<?php
declare (strict_types=1);

namespace app\listener\admin;

use app\common\repositories\system\admin\AdminUserRepository;

class LogoutSuccess
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        admin_log(1, '退出登录');

        /**
         * @var AdminUserRepository $repository
         */
        $repository = app()->make(AdminUserRepository::class);
        $repository->clearSessionInfo();
    }
}
