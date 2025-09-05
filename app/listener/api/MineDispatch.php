<?php
declare (strict_types=1);

namespace app\listener\api;


use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;

class MineDispatch
{
    public function handle($data)
    {
        $event['uuid'] = $data['uuid'];
        $event['companyId'] = $data['companyId'];
        $event['type'] = $data['type'];
        $event['num'] = $data['num'];
        \think\facade\Queue::push(\app\jobs\GrantMine::class, $event,'GrantMine');
    }
}
