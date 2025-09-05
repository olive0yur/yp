<?php
declare (strict_types=1);

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

class MineOutput extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('mine:output')
            ->setDescription('矿场每日产出');
    }

    protected function execute(Input $input, Output $output)
    {
        $mineRepository = app()->make(MineRepository::class);
        $userRepository = app()->make(UsersRepository::class);
        $usersPushRepository = app()->make(UsersPushRepository::class);

        $userPoolRepository = app()->make(UsersPoolRepository::class);
        $poolsaleRepository = app()->make(PoolSaleRepository::class);
        $mineUserRepository = app()->make(MineUserRepository::class);
        $list = $mineUserRepository->search([],74)->where('status', 1)->select();
        foreach ($list as $value) {

            $HOURS_PER_DAY = 24;
            $MINUTES_PER_HOUR = 60;
            $SECONDS_PER_MINUTE = 60;

            $update = time();
            if ($value['level'] == 1) {
                if ($value['dispatch_count'] <= 0) {
                    $mineUserRepository->update($value['id'], ['get_time' => null]);
                    continue;
                }
                if (!$value['get_time']) {
                    $dis_time = $userPoolRepository->search(['uuid' => $value['uuid'], 'status' => 1], $value['company_id'])->where('is_dis', 1)->order('id asc')->value('add_time');
                    if ($dis_time) {
                        $mineUserRepository->editInfo($value, ['get_time' => strtotime($dis_time)]);
                    }
                    $value['get_time'] = strtotime($dis_time);
                }

                $time = $value['edit_time'] ? $value['edit_time'] : $value['get_time'];
                $config = get_rate($value['dispatch_count'], $value['company_id']);

                $total = bcmul((string)($update - $time), (string)$config['rate'], 7);
//                $sum = bcadd($total, $value['product'], 7);
//                $userPool = $userPoolRepository->search([])->where(['uuid' => $value['uuid'], 'company_id' => $value['company_id']])->where('is_dis', 1)->find();
//                $pool = $poolsaleRepository->get($userPool['pool_id']);
//                $pool['ageing'] = $pool['ageing'] <= 0 ? 360 : $pool['ageing'];

//                $zong = bcmul((string)$config['total'], (string)$pool['ageing'], 7);
//                if ($sum >= $zong) {
//                    $mineUserRepository->update($value['id'], ['ing' => 1]);
//                    $total = bcsub((string)$zong, (string)$value['product'], 7);
//                } else {
//                    $mineUserRepository->update($value['id'], ['ing' => 2]);
//                }
                $mineUserRepository->update($value['id'], ['edit_time' => $update]);
                if ($total > 0) {
                    $mineUserRepository->incField($value['id'], 'day_rate', $total);
                    $mineUserRepository->incField($value['id'], 'product', $total);
                    $mineUserRepository->incField($value['id'], 'total', $total);
                    $userRepository->batchFoodChange($value['uuid'], 4, $total, ['remark' => '卡牌产出'], 4);
                    $parent = $usersPushRepository->search(['user_id' => $value['uuid']])->whereIn('levels', [1, 2, 3])->order('levels asc')->select();
                    $user = $userRepository->search([])->where(['id' => $value['uuid']])->find();
                    if ($user) {
                        foreach ($parent as $item) {
                            $pUser = $userRepository->search([])->where(['id' => $item['parent_id']])->find();
                            if ($pUser) {
                                if ($item['levels'] == 1 && web_config($value['company_id'], 'program.node.one.rate', 0) > 0) {
                                    $node = web_config($value['company_id'], 'program.node.one.rate', 0) / 100;
                                    $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$total, (string)$node, 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '好友卡牌收益'], 4);
                                    $dividend = app()->make(LevelTeamRepository::class)->search(['level' => $pUser['team_vip']], $pUser['company_id'])->value('dividend');
                                    if ($dividend && $dividend > 0) {
                                        $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$total, (string)($dividend / 100), 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '团队收益'], 4);
                                    }
                                }
                                if ($item['levels'] == 2 && web_config($value['company_id'], 'program.node.two.rate', 0) > 0) {
                                    $node = web_config($value['company_id'], 'program.node.two.rate', 0) / 100;
                                    $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$total, (string)$node, 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '好友卡牌收益'], 4);
                                    $dividend = app()->make(LevelTeamRepository::class)->search(['level' => $pUser['team_vip']], $pUser['company_id'])->value('dividend');
                                    if ($dividend && $dividend > 0) {
                                        $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$total, (string)($dividend / 100), 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '团队收益'], 4);
                                    }
                                }
                                if ($item['levels'] == 3 && web_config($value['company_id'], 'program.node.three.rate', 0) > 0) {
                                    $node = web_config($value['company_id'], 'program.node.three.rate', 0) / 100;
                                    $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$total, (string)$node, 7), ['child_id' => $value['uuid'], 'is_frends' => 2, 'remark' => '好友卡牌收益'], 4);
                                }
                            }
                        }
                    }
                }


            } else {
                if ($value['rate'] <= 0) continue;
                $time = $value['edit_time'] ? $value['edit_time'] : strtotime($value['add_time']);
                $total = bcmul((string)($update - $time), (string)$value['rate'], 7);
                $sum = bcadd($total, $value['product'], 7);
                if ($sum >= $value['total']) {
                    $total = bcsub((string)$value['total'], (string)$value['product'], 7);
                }
                if ($total > 0) {
                    $mineUserRepository->incField($value['id'], 'product', $total);
                    $mineUserRepository->update($value['id'], ['edit_time' => $update]);
                    $userRepository->batchFoodChange($value['uuid'], 4, $total, ['remark' => '矿场挖矿'], 4);
                    $parent = $usersPushRepository->search(['user_id' => $value['uuid']])->whereIn('levels', [1, 2, 3])->order('levels asc')->select();
                    $mine = $mineRepository->get($value['mine_id']);
                    $divideData = $mine['output'] - $mine['price'];
                    $divide = bcdiv((string)$divideData, (string)30, 8);
                    $totalSeconds = $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE;
                    $rate = bcdiv((string)$divide, (string)$totalSeconds, 8);

                    $produce = bcmul((string)$rate, (string)($update - $time), 8);
                    $user = $userRepository->search([])->where(['id' => $value['uuid']])->find();
                    if ($user) {
                        if ($produce > 0) {
                            foreach ($parent as $item) {
                                $pUser = $userRepository->search([])->where(['id' => $item['parent_id']])->find();
                                if ($pUser) {
                                    if ($item['levels'] == 1 && $mine['node1'] > 0) {
                                        $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$produce, (string)($mine['node1'] / 100), 7), ['child_id' => $value['uuid'], 'is_frends' => 3, 'remark' => '好友矿场收益'], 4);
                                    }
                                    if ($item['levels'] == 2 && $mine['node2'] > 0) {
                                        $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$produce, (string)($mine['node2'] / 100), 7), ['child_id' => $value['uuid'], 'is_frends' => 3, 'remark' => '好友矿场收益'], 4);
                                    }
                                    if ($item['levels'] == 3 && $mine['node3'] > 0) {
                                        $userRepository->batchFoodChange($item['parent_id'], 4, bcmul((string)$produce, (string)($mine['node3'] / 100), 7), ['child_id' => $value['uuid'], 'is_frends' => 3, 'remark' => '好友矿场收益'], 4);
                                    }
                                }
                            }
                        }
                    }
                }
                if ($sum >= $value['total']) {
                    $mineUserRepository->update($value['id'], ['status' => 2]);
                }
            }


        }


    }
}