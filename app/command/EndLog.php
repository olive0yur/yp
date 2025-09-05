<?php
declare (strict_types=1);

namespace app\command;

use app\common\model\system\upload\UploadFileModel;
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
use think\cache\driver\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use Workerman\Lib\Timer;

class EndLog extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('EndLog')
            ->setDescription('产出记录结算');
    }
    protected function execute(Input $input, Output $output)
    {
        $redis = new Redis(['host'=>env('cache.redis_host','127.0.0.1'),'password'=>env('cache.redis_password',123456)]);
        $redis->select(12);


        /** @var UsersRepository $usersRepository */
        $usersRepository = $this->app->make(UsersRepository::class);

        $total = $usersRepository->search([])->where('cert_id','>',0)->count('id');
        $limit = 1000;
        $page = ceil($total / $limit);
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * $limit;

            $uuids = $usersRepository->search([])->where('cert_id','>',0)
                ->limit($offset,$limit)->column('id');
            foreach ($uuids as $v){
                $key = 'REDIS_LOG1TYPE'.$v;
                $ee = $redis->getJava($key);
                if($ee && $ee['price'] > 0){
                   $this->updateNode($v,$ee,$key,$redis);
                }

                $key2 = 'REDIS_LOG2TYPE'.$v;
                $ee2 = $redis->getJava($key2);
                if($ee2 && $ee2['price'] > 0){
                    $this->updateNode($v,$ee2,$key2,$redis);
                }
                $key3 = 'REDIS_LOG3TYPE'.$v;
                $ee3 = $redis->getJava($key3);
                if($ee3 && $ee3['price'] > 0){
                    $this->updateNode($v,$ee3,$key3,$redis);
                }
            }


        }

    }


    public function updateNode($v,$ee,$key,$redis){
        /** @var UsersRepository $usersRepository */
        $usersRepository = $this->app->make(UsersRepository::class);

        /** @var UsersFoodLogRepository $usersFoodLogRepository */
        $usersFoodLogRepository = $this->app->make(UsersFoodLogRepository::class);

        $user = $usersRepository->search([])->where(['id'=>$v])->find();
        switch ($ee['type']){
            case 1:
                $remark = '挖矿收入';
                break;
            case 2:
                $remark = '好友挖矿';
                break;
            case 3:
                $remark = '好友矿场';
                break;
        }
        $usersFoodLogRepository->addLog($v, $ee['price'], 4, array_merge(['remark'=>$remark], [
            'before_change' => $user['food'],
            'after_change' => round($user['food'] + $ee['price'],7),
            'is_frends' => $ee['type'],
            'company_id' => $user['company_id'],
        ]));
        $redis->delete($key);
    }


}