<?php
declare (strict_types=1);

namespace app\command;

use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\model\users\UsersFoodLogModel;
use app\common\model\users\UsersFoodTimeModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;
use Workerman\Lib\Timer;

class Test extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Test')
            ->setDescription('测试');
    }

    protected function execute(Input $input, Output $output)
    {
        $ids = [];
        $max = app()->make(UsersRepository::class)->search([])->order('id desc')->value('id');
        for ($i=1;$i<=$max;$i++){
            $is = app()->make(UsersRepository::class)->search([])
                ->where('id',$i)->find();
            if(!$is) $ids[] = $i;
        }
        if(count($ids) == 0) return true;
        app()->make(GivLogRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(GivLogRepository::class)->search([])->whereIn('to_uuid',$ids)->delete();
        app()->make(MineDispatchRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(MineUserRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(MineUserDispatchRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        $guild = app()->make(GuildRepository::class)->search([])->whereIn('uuid',$ids)->select();
        foreach ($guild as $value){
            app()->make(GuildMemberRepository::class)->search(['guild_id'=>$value['id']])->delete();
            app()->make(GuildWareHouseRepository::class)->search(['guild_id'=>$value['id']])->delete();
            app()->make(GuildWareLogRepository::class)->search(['guild_id'=>$value['id']])->delete();
            app()->make(GuildRepository::class)->search([])->where('id',$value['id'])->delete();
        }
        app()->make(GuildMemberRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(UsersPoolRepository::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(UsersPushRepository::class)->search([])->whereIn('user_id',$ids)->delete();
        app()->make(UsersPushRepository::class)->search([])->whereIn('parent_id',$ids)->delete();
        app()->make(UsersCertRepository::class)->search([])->whereIn('user_id',$ids)->delete();
        app()->make(PoolShopOrder::class)->search([])->whereIn('uuid',$ids)->delete();
        app()->make(AgentRepository::class)->search([])->whereIn('uuid',$ids)->delete();

        return true;
    }
}