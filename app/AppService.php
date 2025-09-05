<?php
declare (strict_types = 1);

namespace app;

use app\command\BuRate;
use app\command\ChildRate;
use app\command\DayOutput;
use app\command\DayRate;
use app\command\EndLog;
use app\command\GameRebate;
use app\command\MemberLevel;
use app\command\MineChanchu;
use app\command\MineNode;
use app\command\ProductEnd;
use app\command\TeamLeve;
use app\command\Test;
use app\command\vipRate;
use think\Service;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册
    }
    public function boot()
    {
        // 服务启动
        $this->commands(
            [
                'ProductEnd'=>ProductEnd::class,
                'EndLog'=>EndLog::class,
                'DayRate'=>DayRate::class,
                'Test'=>Test::class,
                'MineNode'=>MineNode::class,
                'MineChanchu'=>MineChanchu::class,
                'TeamLeve'=>TeamLeve::class,
                'GameRebate'=>GameRebate::class,
                'MemberLevel'=>MemberLevel::class,
                'day:output'=>DayOutput::class,
            ]
        );
    }
}
