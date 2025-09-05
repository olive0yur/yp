<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineUserDao;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class CardPackUserRepository
 * @package app\common\repositories\pool
 * @mixin MineUserDao
 */
class MineUserRepository extends BaseRepository
{

    public function __construct(MineUserDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['userInfo' => function ($query) {
                $query->field('id,mobile,nickname');
                $query->bind(['mobile', 'nickname']);
            }, 'mineInfo'])
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            return $this->dao->create($data);
        });


    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getApiList($data, $page, $limit, $userInfo, $companyId = null)
    {
        $where['uuid'] = $userInfo['id'];
        $where['status'] = 1;
        $query = $this->dao->search($where, $companyId);
        if ($data['type'] == 1) {
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            $count = $usersPoolRepository->search(['uuid' => $userInfo['id'], 'status' => 1], $companyId)->where('is_dis', 1)->count('id');
            $mineLevel = $this->dao->search(['uuid' => $userInfo['id'], 'level' => 1], $companyId)->find();
            $maxLevel = $this->dao->search([])
                ->alias('mu')
                ->join('mine m', 'm.id = mu.mine_id')
                ->where(['mu.uuid' => $userInfo['id'], 'm.is_use' => 1])
                ->where('mu.level > 1')->sum('mu.dispatch_count');
            $bianliang = $count - $maxLevel <= 0 ? 0 : $count - $maxLevel;
            if ($bianliang != $mineLevel['dispatch_count']) {
                $this->editInfo($mineLevel, ['dispatch_count' => $bianliang]);
            }
            $query->where(['level' => 1]);
            $query->append(['rateDay']);
        }
        if ($data['type'] == 2) $query->where('level', '>', 1);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,company_id,mine_id,product as total,product_gold as total_gold,rate as product,day_rate,day_rate_gold,dispatch_count,status,add_time')
            ->with(['mineInfo' => function ($query) {
                $query->field('id,name,file_id,output,level,day_output')->with(['fileInfo' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }]);
            }])
            ->order('id', 'desc')
            ->select();
        if (count($list) > 0) {
            $list = $list->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['mineInfo']['now_output'] = get_rate($value['dispatch_count'], $companyId);
                $day = bcdiv($value['mineInfo']['output'],$value['mineInfo']['day_output']);
                $list[$key]['add_time'] = strtotime('+'.$day.' day', strtotime($list[$key]['add_time']));
                if ($data['type'] == 2 && $companyId != 74) {
                    $list[$key]['dispatch_count'] = 1;
                }
            }
        }

        return compact('count', 'list');
    }

    public function getRank($page, $limit, $userInfo, $companyId)
    {
        $query = app()->make(UsersRepository::class)->search(['status' => 1], $companyId)->field('id,food as total,mobile,nickname,wechat,qq,head_file_id');
        $count = $query->count();
        $query->with(['avatars' => function ($query) {
            $query->bind(['picture' => 'show_src']);
        }])->filter(function ($query) {
            $query['mobile'] = substr_replace($query['mobile'], '****', 3, 4);
            return $query;
        });
        $list = $query->order('total desc')->limit(100)->select();
        return compact('count', 'list');
        return compact('count', 'list');
    }

    public function dispatch($data, $userInfo, $companyId)
    {
        $info = $this->dao->search(['level' => 1, 'uuid' => $userInfo['id']], $companyId)->find();
        if (!$info) throw new ValidateException('您选择的矿场不存在');
        $count = app()->make(UsersPoolRepository::class)
            ->search(['uuid' => $userInfo['id']], $companyId)
            ->where(['is_dis' => 2, 'status' => 1])
            ->count('id');
        if ($data['num'] > $count) throw new ValidateException('数量不足');
        $res1 = Cache::store('redis')->setnx('dispatch_' . $userInfo['id'], $userInfo['id']);
        Cache::store('redis')->expire('dispatch_' . $userInfo['id'], 3);
        if (!$res1) throw new ValidateException('操作频繁!');

        return Db::transaction(function () use ($data, $info, $companyId, $userInfo) {
            $list = app()->make(UsersPoolRepository::class)
                ->search([], $companyId)
                ->where(['uuid' => $userInfo['id'], 'is_dis' => 2, 'status' => 1])
                ->limit($data['num'])
                ->select();
            foreach ($list as $value) {
                $re = app()->make(UsersPoolRepository::class)->search([])
                    ->where('id', $value['id'])
                    ->where('is_dis', 2)
                    ->update(['is_dis' => 1]);
                if ($re) {
                    $this->dao->incField($info['id'], 'dispatch_count', 1);
                }
            }
        });
        return true;
    }


    public function send(array $data, $user, int $companyId = null)
    {
        $res1 = Cache::store('redis')->setnx('giv_dragon_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('giv_dragon_' . $user['id'], 1);
        if (!$res1) throw new ValidateException('禁止同时转增!!');
        $uuid = $user['id'];
        if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        if ($user['dragon'] < $data['num']) throw new ValidateException('您所拥有的七彩龙卡不足！');

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $getUser = $usersRepository->search(['user_code' => $data['user_code']], $companyId)->field('id,mobile,pledge,cert_id')->find();

        if (!$getUser) throw new ValidateException('接收方账号不存在！');
        if ($getUser['cert_id'] == 0) throw new ValidateException('接收方未实名认证!');
        if ($getUser['pledge'] != 1) throw new ValidateException('接收方未质押!');
        $user = $usersRepository->search([], $companyId)->where(['id' => $uuid])->field('id,pay_password,cert_id')->find();
        if ($user['cert_id'] == 0) throw new ValidateException('请先实名认证!');
        if ($getUser['id'] == $uuid) throw new ValidateException('禁止转给自己!');

        $giv_pwd = web_config($companyId, 'program.giv_pwd');
        if ($giv_pwd != 2) {
            if (!$data['pay_password']) throw new ValidateException('请输入交易密码!');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $user['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }
        return Db::transaction(function () use ($data, $user, $getUser, $companyId) {
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $usersRepository->search([], $companyId)->where(['id' => $user['id']])->where('dragon', '>=', $data['num'])->dec('dragon', $data['num'])->update();
            $usersRepository->search([], $companyId)->where(['id' => $getUser['id']])->inc('dragon', $data['num'])->update();
            /** @var GivLogRepository $givLogRepository */
            $givLogRepository = app()->make(GivLogRepository::class);
            $arr['uuid'] = $user['id'];
            $arr['to_uuid'] = $getUser['id'];
            $arr['goods_id'] = 0;
            $arr['buy_type'] = 3;
            $arr['dragon_num'] = $data['num'];
            $arr['unquied'] = $companyId . $user['id'] . $getUser['id'] . $data['num'];
            $arr['order_no'] = SnowFlake::createOnlyId();
            $givLogRepository->addInfo($companyId, $arr);
            return true;
        });
    }


    public function getMyWite($user, $companyId)
    {
        $list = $this->dao->search(['uuid' => $user['id']], $companyId)->select();
        $rate = 0;
        $childs = 0;
        foreach ($list as $value) {
            $re = $this->getWiter($value['id'], $user, $companyId);
            $rate += $re['rate'];
            $childs += $re['child'];
        }
        return ['childs' => $childs, 'rate' => $rate];
    }

    public function getWiter($id, $user, $companyId)
    {
        $info = $this->dao->search(['uuid' => $user['id']], $companyId)->where(['id' => $id])->find();
        if (!$info) return ['rate' => 0, 'child' => 0];
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        switch ($info['level']) {
            case 1:
                $rate = 0;
                if ($info['dispatch_count'] > 0) {
                    if (!$info['edit_time']) {
                        $userPool = app()->make(UsersPoolRepository::class)
                            ->search(['uuid' => $user['id']], $companyId)
                            ->where(['is_dis' => 1, 'status' => 1])
                            ->order('add_time desc')
                            ->find();
                        $change_time = $userPool ? strtotime($userPool['add_time']) : strtotime(date('Y-m-d 00:00:00'));
                    } else {
                        $change_time = $info['edit_time'];
                    }
                    $product = get_rate($info['dispatch_count'], $companyId);
                    $time = time() - $change_time;
                    $rate = round((floor($time / 10) * $product['rate']), 7);
                } else {
                    if (!$info['edit_time']) {
                        $time = strtotime(date('Y-m-d 00:00:00'));
                        $this->editInfo($info, ['edit_time' => $time, 'get_time' => $time]);
                        //计算时间差值  用于计算下级的分成
                        $time = time() - $time;
                    } else {
                        $time = time() - $info['edit_time'];
                    }
                }
                $child = $this->getChild($time, $user, $companyId);
                //不管自己有没有基础矿  都要更新自己的产出时间
                $this->editInfo($info, ['edit_time' => time(), 'get_time' => time()]);
                if ($rate <= 0) return ['rate' => 0, 'child' => 0];;

                $r = $usersRepository->batchFoodChange($user['id'], 4, $rate, ['remark' => '基础矿场挖矿']);
                if ($r) {
                    $new = round($info['product'] + $rate, 7);
                    $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new]);
                }
                break;
            default:
                if ($info['status'] == 2) return ['rate' => 0, 'child' => 0];
                if (!$info['edit_time']) {
                    $change_time = strtotime($info['add_time']);
                } else {
                    $change_time = $info['edit_time'];
                }
                $time = time() - $change_time;
                /** @var MineRepository $mineRepository */
                $mineRepository = app()->make(MineRepository::class);
                $rt = $mineRepository->search([], $companyId)->where(['id' => $info['mine_id']])->find();
                if ($rt) {
                    /** @var GuildMemberRepository $GuildMemberRepository */
                    $GuildMemberRepository = app()->make(GuildMemberRepository::class);
                    //计算每10秒产出多少
                    $rate = bcmul(floor($time / 10), $rt['day_output'] / 8640, 7);
                    // $guild = $GuildMemberRepository->search(['uuid' => $user['id']], $companyId)->with(['guild' => function ($query) use ($companyId) {
                    //     $query->where(['company_id' => $companyId]);
                    // }])->find();
                    // if ($guild) {
                    //     /** @var GuildConfigRepository $guildConfigRepository */
                    //     $guildConfigRepository = app()->make(GuildConfigRepository::class);
                    //     $add_rate = $guildConfigRepository->search(['level' => $guild['guild']['level']], $companyId)->value('rate');
                    //     $rate = bcadd($rate, bcmul($rate, $add_rate, 7), 7);
                    // }
                    $new = $info['product'] + $rate;
                    if ($new >= $info['total']) $rate = $info['total'] - $info['product'];
                    $child = 0;
                    //0619 ce063 如果没有高级矿场收益，则不增加收入记录
                    if ($rate) {
                        $r = $usersRepository->batchGoldChange($user['id'], 4, $rate, ['remark' => '矿场挖矿']);
                        if ($r) {
                            $status = 1;
                            if ($new >= $info['total']) $status = 2;
                            $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new, 'status' => $status]);
                        }
                    }
                }
                break;
        }
        return ['rate' => $rate, 'child' => $child];
    }


    public function getChild($time, $user, $companyId)
    {

        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        /** @var UsersPushRepository MineRepository */
        $mineRepository = app()->make(MineRepository::class);

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);

        $praentList = $usersPushRepository->search(['parent_id' => $user['id']], $companyId)->whereIn('levels', [1, 2, 3])->order('levels asc')->select();
        $child_of = 0;

        foreach ($praentList as $item) {
            $mineUser = $this->dao->search(['uuid' => $item['user_id']], $companyId)->select();
            foreach ($mineUser as $childs) {
                switch ($childs['level']) {
                    case 1:
                        $num = $childs['dispatch_count'];

                        $nodeP = get_rate($num, $companyId)['rate'];
                        $node_rate = round((floor($time / 10) * $nodeP), 7);
                        if ($item['levels'] == 1) {
                            $node_xia = web_config($companyId, 'program.node.one.rate', 0);
                        } elseif ($item['levels'] == 2) {
                            $node_xia = web_config($companyId, 'program.node.two.rate', 0);
                        } elseif ($item['levels'] == 3) {
                            $node_xia = web_config($companyId, 'program.node.three.rate', 0);
                        }
                        $node_change = $node_rate * $node_xia;
                        if ($node_change) {
                            $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友挖矿', 'is_frends' => 2, 'child_id' => $item['user_id']]);
                        }
                        break;
                    default:
                        $node_change = 0;
                        if ($childs['status'] == 1) {
                            $mine = $mineRepository->search(['level' => $childs['level']])->find();
                            $node_rate = round((floor($time / 10) * $childs['rate']), 7);

                            if ($item['levels'] == 1) {
                                $node_xia = $mine['node1'];
                            } elseif ($item['levels'] == 2) {
                                $node_xia = $mine['node2'];
                            } elseif ($item['levels'] == 3) {
                                $node_xia = $mine['node3'];
                            }
                            $node_change = $node_rate * $node_xia;
                            if ($node_change) {
                                $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友挖矿', 'is_frends' => 2, 'child_id' => $item['user_id']]);
                            }
                        }
                        break;
                }
                $child_of += $node_change;
            }
        }
        return $child_of;
    }


    //预估每天返利
    public function getChildDay($user, $companyId)
    {

        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        /** @var UsersPushRepository MineRepository */
        $mineRepository = app()->make(MineRepository::class);

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);

        $praentList = $usersPushRepository->search(['parent_id' => $user['id']], $companyId)->whereIn('levels', [1, 2, 3])->order('levels asc')->select();
        $node_change = 0;

        foreach ($praentList as $item) {
            $mineUser = $this->dao->search(['uuid' => $item['user_id']], $companyId)->field('id,uuid,level,dispatch_count,status')->select();
            foreach ($mineUser as $childs) {
                switch ($childs['level']) {
                    case 1:
                        $num = $childs['dispatch_count'];

                        $nodeP = get_rate($num, $companyId)['total'];

                        if ($item['levels'] == 1) {
                            $node_xia = web_config($companyId, 'program.node.one.rate', 0);
                        } elseif ($item['levels'] == 2) {
                            $node_xia = web_config($companyId, 'program.node.two.rate', 0);
                        } elseif ($item['levels'] == 3) {
                            $node_xia = web_config($companyId, 'program.node.three.rate', 0);
                        }
                        $node_change += $nodeP * $node_xia;
                        break;
                    default:
                        if ($childs['status'] == 1) {
                            $mine = $mineRepository->search(['level' => $childs['level']], $companyId)->find();
                            if ($item['levels'] == 1) {
                                $node_xia = $mine['node1'];
                            } elseif ($item['levels'] == 2) {
                                $node_xia = $mine['node2'];
                            } elseif ($item['levels'] == 3) {
                                $node_xia = $mine['node3'];
                            }
                            $node_change += $mine['day_output'] * $node_xia;
                        }
                        break;
                }
            }
        }
        return $node_change;
    }


}