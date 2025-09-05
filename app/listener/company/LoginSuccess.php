<?php
declare (strict_types=1);

namespace app\listener\company;

use think\facade\Session;

class LoginSuccess
{
    public function handle($adminInfo)
    {
        $adminInfo->last_login_time = time();
        $adminInfo->last_login_ip = request()->ip();
        $adminInfo->session_id = Session::getId();
        $adminInfo->save();

        company_user_log(1, '登陆成功', [], $adminInfo['id'], $adminInfo['company_id'], $adminInfo['session_id']);
    }
}
