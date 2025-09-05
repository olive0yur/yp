<?php
declare (strict_types=1);

namespace app\command;

use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\rabbit\ToysGearLevelRepository;
use app\common\repositories\rabbit\ToysLevelRepository;
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
use think\facade\Cache;
use think\facade\Db;
use Workerman\Lib\Timer;

class TeamLeve extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('TeamLeve')
            ->setDescription('团队等级结算');
    }

    protected function execute(Input $input, Output $output)
    {
//        /** @var UsersFoodLogRepository $usersFoodLogRepository */
//        $usersFoodLogRepository = $this->app->make(UsersFoodLogRepository::class);
//        /** @var MineUserRepository $mineUserRepository */
//        $mineUserRepository = $this->app->make(MineUserRepository::class);
//        $uuids = $mineUserRepository->search(['status'=>1])->group('uuid')->column('uuid');
//        /** @var UsersRepository $usersRepository */
//        $usersRepository = $this->app->make(UsersRepository::class);
//        $usersPushRepository = $this->app->make(UsersPushRepository::class);
//        $userList = $usersRepository->search([])->where('cert_id > 0')->select();
//        foreach ($userList as $value){
//            $list = $usersPushRepository->search(['parent_id'=>$value['id'],'levels'=>1])->select();
//            $number = [];
//            foreach ($list as $item){
//                $childs = $usersPushRepository->search(['parent_id'=>$item['id']])->count('id');
//                $number[] = $childs;
//            }
//
//            $maxNumber = max($number);
//            $result = array_filter($number, function ($value) use ($maxNumber) {
//                return $value != $maxNumber;
//            });
//
//        }

        $usersRepository = $this->app->make(UsersRepository::class);
        $user = $usersRepository->search([], 66)->where('toys_level > 0')->select();
        $gearGearLevelRepository = app()->make(ToysLevelRepository::class);
        foreach ($user as $value) {
            $level = $gearGearLevelRepository->get($value['toys_level']);
            if ($level['lay_num'] > 0) {
                for ($i = 0; $i < $level['lay_num']; $i++) {
                    Db::table('toys_egg')->insert([
                        'uuid' => $value['id'],
                        'company_id' => $value['company_id']
                    ]);
                }
            }
        }

    }

}