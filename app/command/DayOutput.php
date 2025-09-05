<?php

declare(strict_types=1);

namespace app\command;

use app\common\repositories\game\LevelTeamRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;

use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersFoodTimeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use http\Client;
use Mpdf\Tag\P;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\exception\ValidateException;
use think\facade\Cache;
use think\swoole\pool\Db;
use Workerman\Lib\Timer;
use function GuzzleHttp\Psr7\str;

class DayOutput extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('day:output')
            ->setDescription('矿场每日产出');
    }

    protected function execute(Input $input, Output $output)
    {
        $mineRepository = app()->make(MineRepository::class);
        $userRepository = app()->make(UsersRepository::class);
        $usersPushRepository = app()->make(UsersPushRepository::class);
        $mineUserRepository = app()->make(MineUserRepository::class);

        $list = $mineUserRepository->search([], 3)->where('status', 1)->select();
        foreach ($list as $value) {
            try {
                $mine = $mineRepository->get($value['mine_id']);
                //半小时产出
                $output = bcdiv($mine['day_output'], (24 * 2) . '', 7);
                //产出金币
                $userRepository->batchFoodChange($value['uuid'], 4, $output, ['remark' => '矿场产出'], 4);

                //给上级奖励
                $parent = $usersPushRepository->search(['user_id' => $value['uuid']])->whereIn('levels', [1])->order('levels asc')->select();
                // dd( $parent,$configRate,$output);
                if ($output > 0) {
                    foreach ($parent as $item) {
                        $pUser = $userRepository->search([])->where(['id' => $item['parent_id']])->find();
                        if ($pUser) {
                            if ($item['levels'] == 1 && web_config($value['company_id'], 'program.node.one.rate', 0) > 0) {
                                $node = web_config($value['company_id'], 'program.node.one.rate', 0) / 100;
                                if ($node > 0) {
                                    $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$output, (string)$node, 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '好友卡牌收益'], 4);
                                }
                            }
                            if ($item['levels'] == 2 && web_config($value['company_id'], 'program.node.two.rate', 0) > 0) {
                                $node = web_config($value['company_id'], 'program.node.two.rate', 0) / 100;
                                if ($node > 0) {
                                    $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$output, (string)$node, 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '好友卡牌收益'], 4);
                                }
                            }
                        }
                    }
                }
                $saveData = [
                    'product' => $value['product'] + $output,
                    // 'product_gold' => $value['product_gold'] + $mine['day_output_gold'],
                    'get_time' => time(),
                ];
                //达到最大产出，结束
                if ($value['product'] + $output >= $value['total']) {
                    $saveData['status'] = 2;
                }
                $mineUserRepository->editInfo($value, $saveData);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
