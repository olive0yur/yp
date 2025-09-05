<?php
declare (strict_types=1);

namespace app\command;

use app\common\model\users\UsersPoolModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Workerman\Lib\Timer;

class ProductEnd extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('ProductEnd')
            ->setDescription('旷工到期');
    }

    protected function execute(Input $input, Output $output)
    {
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = $this->app->make(MineUserRepository::class);

        while (true) {
            try {

                $total = $mineUserRepository->search(['status' => 1, 'level' => 1])
                    ->whereRaw('dispatch_count > 0')->count();
                $limit = 100;
                $page = ceil($total / $limit);
                for ($i = 0; $i < $page; $i++) {
                    $offset = $i * $limit;
                    $list = $mineUserRepository->search(['status' => 1, 'level' => 1])
                        ->whereRaw('dispatch_count > 0')
                        ->limit($offset, $limit)->select();
                    foreach ($list as $key => $v) {
                        $userPool = (new UsersPoolModel())->alias('up')
                            ->where(['up.status' => 1, 'up.is_dis' => 1, 'uuid' => $v['uuid']])
                            ->whereRaw('p.ageing > 0')
                            ->join('pool_sale p', 'p.id = up.pool_id')
                            ->field('up.*,p.ageing')
                            ->limit($v['dispatch_count'])
                            ->select();
//                        if (count($userPool) != $v['dispatch_count']) continue;
                        $number = $v['dispatch_count'];
                        foreach ($userPool as $value) {
                            if ($value['ageing'] > 0) {
                                $end = date('Y-m-d H:i:s', strtotime('+' . $value['ageing'] . ' day', strtotime($value['add_time'])));
                                if ($end <= date('Y-m-d H:i:s')) {
                                    (new UsersPoolModel())->where('id', $value['id'])->update(['status' => 10]);
                                    $mineUserRepository->decField($v['id'], 'dispatch_count', 1);
                                    $number -= 1;
                                }
                            }
                        }
                        if ($number == 0) {
                            $mineUserRepository->update($v['id'], ['get_time' => null]);
                        }
                    }
                }
            } catch (\Exception $e) {
                dump($e);
                exception_log('旷工到期运行失败', $e);
                $output->writeln('运行失败');
            }
            sleep(3);
        }

    }
}