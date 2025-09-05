<?php
declare (strict_types=1);

namespace app\command;

use app\common\model\game\KillRebateModel;
use app\common\repositories\game\KillRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersFoodTimeRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use http\Client;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use Workerman\Lib\Timer;

class GameRebate extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('GameRebate')
            ->setDescription('失败返利');
    }
    protected function execute(Input $input, Output $output)
    {
        /** @var KillRepository $KillRepository */
        $KillRepository = app()->make(KillRepository::class);
        /*已经同步期号*/
//        $game = (new KillRebateModel())
//            ->group('batch_no')->column('batch_no');
        /*未同步期号*/
        $list = $KillRepository->search([],35)
            ->where('rebat',0)
            ->group('batch_no')->select();
        foreach ($list as $value){
            $first = $KillRepository->search([],35)
                ->where(['batch_no'=>$value['batch_no']])->order('id asc')->find();
            if(time() >= strtotime($first['gameDate']) + 100){
                $winUser = $KillRepository->search([],35)
                    ->where(['type'=>2,'batch_no'=>$value['batch_no']])
                    ->column('uuid');
                $KillRepository->search(['batch_no' => $value['batch_no']])->update(['rebat'=>1]);
                app()->make(UsersRepository::class)->circulate($winUser,$value['batch_no']);
            }

        }

    }

}