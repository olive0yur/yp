<?php


namespace app\work;

use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use think\worker\Server;
use Workerman\Lib\Timer;

class WorkLevel extends Server
{
    protected $socket = 'websocket://0.0.0.0:9504';
    protected $so;
    protected $arr=[];
    public function onWorkerStart($worker)
    {
        Timer::add(10, function()use($worker){
            $map = [['status', '=', 1]];
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = app()->make(MineUserRepository::class);
            $total =  (new MineUserModel())->alias('mu')
                ->where(['mu.status'=>1,'mu.level'=>1])
                ->join('mine m','mu.mine_id = m.id')
                ->whereExp('mu.dispatch_count', '> 0')
                ->count('mu.id');
            $limit = 100;
            $page = ceil($total / $limit);
            for ($i = 0; $i < $page; $i++) {
                $offset = $i * $limit;

                $mineUserRepository = app()->make(MineUserRepository::class);
                try {
                    $list = (new MineUserModel())->alias('mu')
                        ->where(['mu.status'=>1,'mu.level'=>1])
                        ->join('mine m','mu.mine_id = m.id')
                        ->limit($offset, $limit)
                        ->whereExp('mu.dispatch_count', '> 0')
                        ->field('mu.*,m.day_output')
                        ->select();
                    foreach ($list as $key => $value) {
                        $time = time() - $value['edit_time'];
                        if (!$value['edit_time'] || $time >= 10) {
                            /** @var UsersRepository $usersRepository */
                            $usersRepository = app()->make(UsersRepository::class);
                            $remark = '挖矿收入';
                            $getRate = get_rate($value['dispatch_count'],$value['company_id']);
                            if(!$getRate) continue;
                            $rate = $getRate['rate'];

                            if($value['day_rate'] >= $getRate['total']) continue;
                            if($value['edit_time']){
                                $rate = round((floor($time / 10) *$rate),7);
                            }
                            if($rate <= 0 ) return true;

                            $update['product'] = round( ($value['product']+$rate), 7);
                            $update['edit_time'] = time();

                            $update['day_rate'] = round( ($value['day_rate']+$rate), 7);
                            $fan = 2;  // 2 正常 1 矿场停止
//                            if ($value['product'] >= $value['total'] && $value['level'] > 1) {
//                                $update['status'] = 2;
//                                $fan = 1;
//                            }
                            $re = $mineUserRepository->search([])->where(['id'=>$value['id']])->update($update);
                            $re = $usersRepository->batchFood($value['uuid'], 4, $rate, ['remark' => $remark,'dis_id'=>$value['id'],'type'=>1]);
                        }
                    }
                    return true;
                } catch (\Exception $e) {
                    dump($e);
                    exception_log('任务处理失败', $e);
                }






            }
        });
    }
}