<?php
declare (strict_types=1);

namespace app\command;

use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersFoodTimeRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use http\Client;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\exception\ValidateException;
use think\facade\Cache;
use think\swoole\pool\Db;
use Workerman\Lib\Timer;

class MineChanchu extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('MineChanchu')
            ->setDescription('210特殊产出');
    }

    protected function execute(Input $input, Output $output)
    {

        $map = [['status', '=', 1],['level','=',1],['ing','=',1],['company_id','=',35]];

        /** @var MineUserRepository $mineUserRepository */
       $mineUserRepository = app()->make(MineUserRepository::class);
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);


       $total = $mineUserRepository->search([])->where($map)->count();
        $limit = 1000;
        $page = ceil($total / $limit);
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * $limit;
            $list = $mineUserRepository->search([])->where($map)
                ->limit($offset,$limit)->select();
            foreach ($list as $key => $value){
                try {
                    $rate = $value['product'] + 0.1;
                    $arr['product'] = $rate;
                    if($rate >= 3) $arr['ing'] = 2;
                    $re = $usersRepository->batchFoodChange($value['uuid'],4,0.1,['remark'=>'矿场产出']);
                    if($re) $mineUserRepository->editInfo($value['id'],$arr);

                }catch (ValidateException $exception){

                }

            }
        }
    }


}