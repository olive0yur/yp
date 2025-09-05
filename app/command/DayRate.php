<?php
declare (strict_types=1);

namespace app\command;

use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Workerman\Lib\Timer;

class DayRate extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('DayRate')
            ->setDescription('日产恢复');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = $this->app->make(MineUserRepository::class);
        /** @var MineUserDispatchRepository $mineDispatchRepository */
        $mineDispatchRepository = $this->app->make(MineDispatchRepository::class);

        $total = $mineUserRepository->search(['level' => 1])->where('day_rate', '>', 0)
            ->where('dispatch_count', '>', 0)->count('id');
        $limit = 1000;
        $page = ceil($total / $limit);
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * $limit;
            $list = $mineUserRepository->search(['level' => 1])
                ->where('day_rate', '>', 0)
                ->where('dispatch_count', '>', 0)
                ->limit($offset, $limit)->select();
            foreach ($list as $key => $value) {
                $rate = get_rate($value['dispatch_count'], $value['company_id']);
                if ($value['edit_time'] && $value['day_rate'] >= $rate['total'] && $value['edit_time'] < strtotime(date('Y-m-d 23:30:00'))) {
                    $time = time() - strtotime(date('Y-m-d 00:00:00'));
                    $day_rate = round((floor($time / 10) * $rate['rate']), 7);
                    $mineUserRepository->editInfo($value, ['day_rate' => $day_rate]);
                }
            }
        }




        $total1 = $mineUserRepository->search([])->alias('mu')->where(['mu.status' => 1])
            ->where('mu.level', '>', 1)
            ->join('mine m', 'm.id = mu.mine_id')
            ->field('mu.*', 'm.day_output')
            ->where('day_rate > day_output')
            ->count('mu.id');
        $limit1 = 1000;
        $page = ceil($total1 / $limit1);
        for ($i = 0; $i < $page; $i++) {
            $offset1 = $i * $limit1;
            $list1 = $mineUserRepository->search([])->alias('mu')->where(['mu.status' => 1])
                ->where('mu.level', '>', 1)
                ->field('mu.*,m.day_output')
                ->join('mine m', 'm.id = mu.mine_id')
                ->where('day_rate > day_output')
                ->limit($offset1, $limit1)->select();
            foreach ($list1 as $k => $v) {
                if ($v['edit_time'] && $v['day_rate'] >= $v['day_output'] && $v['edit_time'] < strtotime(date('Y-m-d 23:30:00'))) {
                    $time = time() - strtotime(date('Y-m-d'));
                    $day_rate1 = round((floor($time / 10) * ($v['rate'])), 7);
                    $mineUserRepository->editInfo($v, ['day_rate' => $day_rate1]);
                }
            }

//        $mineDispatchRepository->search([])->where('day_rate','>',0)->select();


//      $mineUserRepository->search([])->whereIn('status',[1,2])->update(['day_rate'=>0]);
//        $mineDispatchRepository->search([])->where('day_rate','>',0)->update(['day_rate'=>0]);
        }
        }
}