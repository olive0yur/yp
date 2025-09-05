<?php


namespace app\work;

use app\common\model\mine\MineUserModel;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use think\worker\Server;
use Workerman\Lib\Timer;
use function JmesPath\search;

class WorkChild extends Server
{
    protected $socket = 'websocket://0.0.0.0:9505';
    protected $so;
    protected $arr=[];
    public function onWorkerStart($worker)
    {
        Timer::add(10, function()use($worker){
            $map = [['status', '=', 1]];
            /** @var MineDispatchRepository $mineDispatchRepository */
            $mineDispatchRepository = app()->make(MineDispatchRepository::class);
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            try {
                $total = $mineDispatchRepository->search([])
                    ->whereExp('dispatch_count', '> 0')
                    ->count('id');
                $limit = 100;
                $page = ceil($total / $limit);
                for ($i = 0; $i < $page; $i++) {
                    $offset = $i * $limit;
                    $list = $mineDispatchRepository->search([])
                        ->whereExp('dispatch_count', '> 0')
                        ->limit($offset, $limit)
                        ->select();
                    foreach ($list as $key => $value) {
                        $time = time() - $value['edit_time'];

                        if (!$value['edit_time'] || $time >= 10) {

                            $remark = '好友挖矿';

                            $getRate = get_rate($value['dispatch_count'], $value['company_id']);
                            if (!$getRate) continue;
                            $rate = $getRate['rate'];

                            if ($value['day_rate'] >= $getRate['total']) continue;
                            if ($value['edit_time']) {
                                $rate = bcmul(floor($time / 10), $rate, 7);
                            }
                            if ($rate <= 0) continue;

                            $update['product'] = bcadd($value['product'], $rate, 7);
                            $update['edit_time'] = time();

                            $update['day_rate'] = bcadd($value['day_rate'], $rate, 7);

                            $mineDispatchRepository->editInfo($value, $update);
                            $usersRepository->batchFood($value['uuid'], 4, $rate, ['remark' => $remark, 'dis_id' => $value['id'], 'type' => 2]);
                        }
                    }
                    return true;
                }

            }catch (\Exception $e) {
                    dump($e);
                    exception_log('任务处理失败', $e);
                }
        });
    }
}