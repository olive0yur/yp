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
use think\facade\Cache;
use Workerman\Lib\Timer;

class MineNode extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('MineNode')
            ->setDescription('开矿返利');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        /** @var MineUserRepository $mineUserRepository */
       $mineUserRepository = app()->make(MineUserRepository::class);
       $total = $mineUserRepository->search([])->alias('mu')->where(['mu.status'=>1])->where('mu.level','>',1)
            ->join('mine m','m.id = mu.mine_id')
            ->count();
        $limit = 1000;
        $page = ceil($total / $limit);
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * $limit;
            $list = $mineUserRepository->search([])->alias('mu')
                ->where(['mu.status'=>1])
                ->where('mu.level','>',1)
                ->where('m.node1','>',0)
                ->join('mine m','m.id = mu.mine_id')
                ->with(['userInfo' => function ($query) {
                    $query->field(['id','mobile','nickname']);
                }])
                ->field('mu.*,m.node1,m.node2,m.node3,m.day_output')
                ->limit($offset,$limit)->select();
            foreach ($list as $key => $value){
                $nickname = $value['userInfo']['nickname'] ? '（'.$value['userInfo']['nickname'].'）' : '';
                   if($value['node1']){
                        $parent1 = $usersPushRepository->search(['user_id'=>$value['uuid'],'levels'=>1])->value('parent_id');
                        $change1 = round($value['day_output'] * $value['node1'],7);
                        if($change1 > 0 && $parent1) $usersRepository->batchFoodChange($parent1,4,$change1,['remark'=>'下级' . $nickname . '开矿收益']);
                   }
                if($value['node2']){
                    $parent2 = $usersPushRepository->search(['user_id'=>$value['uuid'],'levels'=>2])->value('parent_id');
                    $change2 = round($value['day_output'] * $value['node2'],7);
                    if($change2 > 0 && $parent2) $usersRepository->batchFoodChange($parent2,4,$change2,['remark'=>'下级' . $nickname . '开矿收益']);
                }
                if($value['node2']){
                    $parent3 = $usersPushRepository->search(['user_id'=>$value['uuid'],'levels'=>3])->value('parent_id');
                    $change3 = round($value['day_output'] * $value['node3'],7);
                    if($change3 > 0 && $parent3) $usersRepository->batchFoodChange($parent3,4,$change3,['remark'=>'下级' . $nickname . '开矿收益']);


                }
            }
        }
    }


}