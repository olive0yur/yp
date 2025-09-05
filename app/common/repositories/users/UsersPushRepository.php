<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersPushDao;
use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\BaseRepository;
use app\common\repositories\game\KillRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\system\SystemPactRepository;
use app\common\repositories\users\UsersRepository;

use app\listener\api\UserMine;
use function JmesPath\search;

/**
 * @mixin UsersPushDao
 */
class UsersPushRepository extends BaseRepository
{

    public function __construct(UsersPushDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,cert_id,id as mobile,nickname,add_time')
                        ->append(['zt_num'])->with([
                            'cert' => function ($query) {
                                $query->field('id,cert_status,remark,username');
                            }
                        ]);
                }
            ])
            ->field('user_id,parent_id,levels')
            ->order('user_id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getAgentList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->whereIn('levels', [1, 2, 3]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,cert_id,mobile,nickname,add_time')
                        ->append(['zt_num'])->with([
                            'cert' => function ($query) {
                                $query->field('id,cert_status,remark,username');
                            }
                        ]);
                }
            ])
            ->order('user_id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function tjr($user, $tjrId, $companyId)
    {
        $id = $user['id'];
        $this->dao->whereDelete(['user_id' => $id]);
        $tjrInfo = $this->dao->getByUserId($tjrId);
        $info = [[
            'user_id' => $id,
            'parent_id' => $tjrId,
            'levels' => 1,
            'company_id' => $companyId,
            'user_mobile' => $user['mobile'],
        ]];
        foreach ($tjrInfo as $k => $v) {
            $info[] = [
                'user_id' => $id,
                'parent_id' => $v['parent_id'],
                'levels' => $v['levels'] + 1,
                'company_id' => $companyId,
                'user_mobile' => $user['mobile'],
            ];
        }
        
        $is =  $this->dao->insertAll($info);
    }

    /**
     * 修改用户推荐人
     *
     * @param int $id 用户ID
     * @param int $tjrId 推荐人ID
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function batchTjr($userInfo, $tjrId, $companyId)
    {
        $this->tjr($userInfo, $tjrId, $companyId);
        $list = $this->dao->search(['parent_id' => $userInfo['id']], $companyId)->select();
        foreach ($list as $k => $v) {
            $v['id'] = $v['user_id'];
            $v['mobile'] = $v['user_mobile'];
            $this->tjr($v, $v['parent_id'], $companyId);
        }
    }

    public function getFrom(array $where, $page, $limit, int $uuid, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();


        $list = $query->page($page, $limit)->field('user_id,parent_id,levels,parent_id')
            ->with(['user' => function ($query) use ($companyId, $where, $uuid) {
                $query->field('id,mobile,nickname,user_code,head_file_id,cert_id,add_time')->filter(function ($query) use ($companyId, $where, $uuid) {
                    if ($companyId == 74) {
                        $query['mobile'] = $query['user_code'];
                    } else {
                        $query['mobile'] = substr_replace($query['mobile'], '****', 3, 4);
                    }

                    if ($where['levels'] == 1) $lv = web_config($companyId, 'program.node.one.rate');
                    if ($where['levels'] == 2) $lv = web_config($companyId, 'program.node.two.rate');
                    if ($where['levels'] == 3) $lv = web_config($companyId, 'program.node.three.rate');

                    $product = (new MineUserModel())
                            ->where(['uuid' => $query['id'], 'level' => 1])
                            ->sum('product') * $lv;

                    if ($where['levels'] == 1) {
                        $subQuery = (new MineUserModel())->alias('mu')
                            ->where(['mu.uuid' => $query['id']])->where('mu.level', '>', 1)
                            ->join('mine m', 'm.id=mu.mine_id')
                            ->field('mu.product * m.node1 as product_node1')->select();
                        $product1 = array_sum(array_column(json_decode($subQuery, true), 'product_node1'));
                    }

                    if ($where['levels'] == 2) {
                        $subQuery = (new MineUserModel())->alias('mu')->where(['mu.uuid' => $query['id']])
                            ->where('mu.level', '>', 1)
                            ->join('mine m', 'm.id=mu.mine_id')
                            ->field('mu.product * m.node2 as product_node1')->select();
                        $product1 = array_sum(array_column(json_decode($subQuery, true), 'product_node1'));

                    }
                    if ($where['levels'] == 3) {
                        $subQuery = (new MineUserModel())->alias('mu')->where(['mu.uuid' => $query['id']])->where('mu.level', '>', 1)
                            ->join('mine m', 'm.id=mu.mine_id')
                            ->field('mu.product * m.node3 as product_node1')->select();
                        $product1 = array_sum(array_column(json_decode($subQuery, true), 'product_node1'));

                    }
                    /** @var MineUserRepository $mineUserRepository */
                    $mineUserRepository = app()->make(MineUserRepository::class);

                    $pcount = $mineUserRepository->search(['uuid' => $query['id'], 'level' => 1])->where('dispatch_count', '>', 7)->count('id');
                    $pcount1 = $mineUserRepository->search(['uuid' => $query['id'], 'status' => 1])->where('level', '>', 1)->count('id');

                    $query['isYx'] = 0;
                    if ($pcount > 0 || $pcount1 > 0) $query['isYx'] = 1;


                    $query['total'] = bcadd($product, $product1, 2);
                    /** @var UsersFoodLogRepository $usersFoodLogRepository */
                    $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
                    $query['yesterdayFood'] = $usersFoodLogRepository->search(['user_id' => $query['user_id'], 'log_type' => 4, 'keyword' => '挖矿收入'], $companyId)
                        ->whereDay('add_time', 'yesterday')->sum('amount');

                    /** @var MineUserRepository $MineUserRepository */
                    $MineUserRepository = app()->make(MineUserRepository::class);
                    $dispatch_count = $MineUserRepository->search(['uuid' => $query['id'], 'status' => 1, 'level' => 1])->sum('dispatch_count');
                    $level2 = $MineUserRepository->search(['uuid' => $query['id'], 'status' => 1])->where('level', '>', 1)->count('id');

                    $query['kapai'] = $dispatch_count >= 7 || $level2 > 0 ? 1 : 0;
                    $query['pool'] = app()->make(UsersPoolRepository::class)->search(['uuid' => $query['id'], 'status' => 1])->count('id');
                    $parent = $this->dao->search(['levels' => 1, 'user_id' => $query['id']], $companyId)->find();
                    $query['parent'] = $query ? app()->make(UsersRepository::class)->search([], $companyId)
                        ->where('id', $parent['parent_id'])->with(['avatars' => function ($query) {
                            $query->bind(['picture' => 'show_src']);
                        }])->field('nickname,id,user_code,head_file_id')->find() : null;
                    /** @var UsersCertRepository $usersCertRepository */
                    $usersCertRepository = app()->make(UsersCertRepository::class);
                    $query['cert_status'] = $usersCertRepository->search([], $companyId)->where(['id' => $query['cert_id']])->value('cert_status');
                    return $query;
                })
                    ->with(['avatars' => function ($query) {
                        $query->bind(['picture' => 'show_src']);
                    }]);
            }])
            ->order('user_id', 'desc')
            ->select();
        $tui = $this->dao->search(['parent_id' => $uuid, 'levels' => 2], $companyId)->count('user_id');

        $today = $this->dao->search([])->alias('p')
            ->join('users u', 'u.id = p.user_id')->whereDay('u.add_time')
            ->where(['p.parent_id' => $uuid, 'u.company_id' => $companyId])->whereIn('levels', [1, 2])->count('p.user_id');


        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);

        $ids = $this->dao->search(['parent_id' => $uuid, 'levels' => $where['levels']], $companyId)->column('user_id');
        $isYx = 0;
        foreach ($ids as $v) {
            $pcount = $mineUserRepository->search(['uuid' => $v, 'level' => 1])->where('dispatch_count', '>', 7)->count('id');
            $pcount1 = $mineUserRepository->search(['uuid' => $v, 'status' => 1])->where('level', '>', 1)->count('id');
            if ($pcount > 0 || $pcount1 > 0) {
                $isYx++;
            }
        }
        return compact('list', 'count', 'tui', 'today', 'isYx');
    }

    public function getFriendMine($userInfo, $companyId)
    {
        $userRepository = app()->make(UsersRepository::class);
        $list = $this->dao->search([])->alias('p')
            ->where(['parent_id' => $userInfo['id']])
            ->whereIn('levels', [1, 2, 3])
            ->select();
        $count = 0;
        $rate = 0;
        $dayRate = 0;
        foreach ($list as $item) {
            $mine = app()->make(MineUserRepository::class)->search(['uuid' => $item['user_id'], 'level' => 1])->find();
            $pUser = $userRepository->search([])->where(['id' => $item['parent_id']])->find();
            if ($pUser) {
                $rateArr = get_rate($mine['dispatch_count'], $pUser['company_id']);
                $info_rate = $rateArr['total'];
                if ($item['levels'] == 1 && web_config($pUser['company_id'], 'program.node.one.rate', 0) > 0) {
                    $node = web_config($pUser['company_id'], 'program.node.one.rate', 0);
                    $dayRate += bcmul((string)$info_rate, (string)$node, 7);
                }
                if ($item['levels'] == 2 && web_config($pUser['company_id'], 'program.node.two.rate', 0) > 0) {
                    $node = web_config($pUser['company_id'], 'program.node.two.rate', 0);
                    $dayRate += bcmul((string)$info_rate, (string)$node, 7);
                }
                if ($item['levels'] == 3 && web_config($pUser['company_id'], 'program.node.three.rate', 0) > 0) {
                    $node = web_config($pUser['company_id'], 'program.node.three.rate', 0);
                    $dayRate += bcmul((string)$info_rate, (string)$node, 7);
                }
                $count += $mine['dispatch_count'];
                $rate += $rateArr['rate'];
            }

        }
        $totalProduct = app()->make(UsersFoodLogRepository::class)->getSearch(['user_id' => $userInfo['id'], 'is_frends' => 2])->sum('amount');//

//        $HOURS_PER_DAY = 24;
//        $MINUTES_PER_HOUR = 60;
//        $SECONDS_PER_MINUTE = 60;
//
//        if ($dayRate > 0) {
//            $rate = bcdiv($dayRate, $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
//        }

        $systemPactRepository = app()->make(SystemPactRepository::class);
        $desc = $systemPactRepository->getPactInfo(15, $companyId);
//
        return ['total' => $count, 'rate' => round($rate, 7), 'totalProduct' => $totalProduct, 'desc' => $desc, 'dayRate' => $dayRate];
    }

    public function getApiRank($companyId)
    {

        $list = $this->dao->search(['levels' => 1], $companyId)
            ->group('parent_id')
            ->field('count(user_id) zt_num,parent_id')
            ->with(['tjrOne' => function ($query) {
                $query->with(['avatars' => function ($query) {
                    $query->bind(['avatar' => 'show_src']);
                }]);
                $query->bind(['nickname' => 'nickname', 'avatar' => 'avatar']);
            }])
            ->order('zt_num desc')
            ->limit(100)
            ->select();
        return $list;
    }

    public function getLowerGame($where, $page, $limit, $companyId)
    {
        if (!$where['user_code']) {
            return ['list' => [], 'count' => 0];
        }
        $add_time = [0 => '2025-05-01 00:00:00 ', 1 => '2030-05-01 00:00:00'];
        if ($where['time']) {
            $add_time = explode(' - ', $where['time']);
        }
        $arr = app()->make(UsersRepository::class)->search(['user_code' => $where['user_code']])->field('id as user_id,user_code,nickname')->find();
        if (!$arr) return ['list' => [], 'count' => 0];
        $arr = $arr->toArray();
        $query = $this->dao->search($where, $companyId)->whereIn('levels', [1, 2]);
        $count = $query->count();
        $list = $query->order('user_id asc')
            ->page($page, $limit)
            ->select()->toArray();
        $arr['levels'] = 0;
//        $arr['dtsTouzhu'] = app()->make(KillRepository::class)->search(['coinType' => '大逃杀', 'uuid' => $arr['id'], 'type' => 1], $companyId)->where($time)->sum('changePoints');
//        $arr['dtsYingli'] = app()->make(KillRepository::class)->search(['coinType' => '大逃杀', 'uuid' => $arr['id'], 'type' => 1], $companyId)->where($time)->sum('changePoints');
//        $arr['dtsYk'] = $arr['dtsYingli'] - $arr['dtsTouzhu'];
//
//        $arr['ydhTouzhu'] = app()->make(KillRepository::class)->search(['coinType' => '赛跑', 'uuid' => $arr['id'], 'type' => 1], $companyId)->where($time)->sum('changePoints');
//        $arr['ydhYingli'] = app()->make(KillRepository::class)->search(['coinType' => '赛跑', 'uuid' => $arr['id'], 'type' => 1], $companyId)->where($time)->sum('changePoints');
//        $arr['ydhYk'] = $arr['ydhYingli'] - $arr['ydhTouzhu'];
        array_unshift($list, $arr);
        foreach ($list as &$item) {
            $user = app()->make(UsersRepository::class)->getSearch(['id' => $item['user_id']])->field('id,user_code,nickname')->find();
            $item['user_code'] = $user['user_code'];
            $item['nickname'] = $user['nickname'];
            $item['dtsTouzhu'] = app()->make(KillRepository::class)->search(['gameName' => '大逃杀', 'uuid' => $item['user_id'], 'type' => 1], $companyId)
                ->whereBetweenTime('gameDate', $add_time[0], $add_time[1])
                ->sum('changePoints');
            $item['dtsYingli'] = app()->make(KillRepository::class)->search(['gameName' => '大逃杀', 'uuid' => $item['user_id'], 'type' => 2], $companyId)
                ->whereBetweenTime('gameDate', $add_time[0], $add_time[1])->sum('changePoints');
            $item['dtsYk'] = $item['dtsYingli'] - $item['dtsTouzhu'];

            $item['ydhTouzhu'] = app()->make(KillRepository::class)->search(['gameName' => '赛跑', 'uuid' => $item['user_id'], 'type' => 1], $companyId)
                ->whereBetweenTime('gameDate', $add_time[0], $add_time[1])->sum('changePoints');
            $item['ydhYingli'] = app()->make(KillRepository::class)->search(['gameName' => '赛跑', 'uuid' => $item['user_id'], 'type' => 2], $companyId)
                ->whereBetweenTime('gameDate', $add_time[0], $add_time[1])->sum('changePoints');
            $item['ydhYk'] = $item['ydhYingli'] - $item['ydhTouzhu'];
        }
        return ['list' => $list, 'count' => $count];
    }

    public function getLowerMine($where, $page, $limit, $companyId)
    {
        if (!$where['user_code']) {
            return ['list' => [], 'count' => 0];
        }

        $add_time = [0 => '2025-05-01 00:00:00', 1 => '2030-05-01 00:00:00'];
        if ($where['time']) {
            $add_time = explode(' - ', $where['time']);
        }
        $query = $this->dao->search($where, $companyId)->whereIn('levels', [1, 2]);
        $count = $query->count();
        $list = $query->order('user_id asc')
            ->page($page, $limit)
            ->select()->toArray();
        $arr = app()->make(UsersRepository::class)->search(['user_code' => $where['user_code']])->field('id as user_id,user_code,nickname')->find();
        if (!$arr) return ['list' => [], 'count' => 0];
        $arr = $arr->toArray();
        $arr['levels'] = 0;
//        foreach ($mine as $mineItem) {
//            $arr['product'][$mineItem['level']] = app()->make(MineUserRepository::class)->search(['uuid' => $arr['id'], 'level' => $mineItem['level']], $companyId)->where($time)->sum('product');
//        }
        array_unshift($list, $arr);
        $mine = app()->make(MineRepository::class)->search([], $companyId)->order('level asc')->select();
        foreach ($list as &$item) {
            $user = app()->make(UsersRepository::class)->getSearch(['id' => $item['user_id']])->field('id,user_code,nickname')->find();
            $item['user_code'] = $user['user_code'];
            $item['nickname'] = $user['nickname'];
            $level = [];
            foreach ($mine as $mineItem) {
                $level[$mineItem['level']] = app()->make(MineUserRepository::class)->search(['uuid' => $item['user_id'], 'level' => $mineItem['level']], $companyId)
                    ->whereBetweenTime('add_time', $add_time[0], $add_time[1])->sum('product');
            }
            $item['product_total'] = $level;
        }

        return ['list' => $list, 'count' => $count];
    }
}