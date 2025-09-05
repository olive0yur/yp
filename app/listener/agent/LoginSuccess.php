<?php
declare (strict_types=1);

namespace app\listener\agent;

use think\facade\Session;

class LoginSuccess
{
    public function handle($adminInfo)
    {
        $adminInfo->last_login_time = time();
        $adminInfo->last_login_ip = request()->ip();
        $adminInfo->session_id = Session::getId();
        $adminInfo->save();

        api_user_log($adminInfo['id'],1,$adminInfo['company_id'],'登录代理后台');
    }
}
