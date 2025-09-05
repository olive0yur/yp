<?php

namespace app\common\repositories\rabbit;

use app\common\dao\rabbit\ToysBackpackDao;
use app\common\dao\sign\SignSetDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\sign\SignAbcRepository;
use app\common\repositories\sign\SignRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\rabbit\Level;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class ToysGearRepository
 * @package app\common\repositories\rabbit
 * @mixin ToysBackpackDao
 */
class ToysBackpackRepository extends BaseRepository
{
    public function __construct(ToysBackpackDao $dao)
    {
        $this->dao = $dao;

    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['gear' => function ($q) {
                $q->withAttr('type_name', function ($value, $data) {
                    return ToysGearRepository::TYPE[$data['type']];
                })
                    ->append(['type_name']);
            }])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function editInfo($info, $data)
    {

        return $this->dao->update($info['id'], $data);
    }

    public function addInfo($companyId, $data)
    {

        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }

    /**
     * 删除
     */
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


    public function lay($userInfo)
    {
        $egg = Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 2)->count('id');
        if ($egg <= 0) throw new ValidateException('请等待产蛋');
        Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 2)->update(['status' => 1]);
        return true;
    }

    public function complete($param, $user, $companyId)
    {
        $jobGearJobRepository = app()->make(ToysGearJobRepository::class);
        $info = Db::table('toys_gear_job_log')->where(['uuid' => $user['id'], 'job_id' => $param['id'], 'status' => 1])->find();
        if ($info) throw new ValidateException('已完成');
        $job = $jobGearJobRepository->search([], $companyId)
            ->where('id', $param['id'])
            ->find();
        if (!$job) throw new ValidateException('请等待');
        if ($job['type'] == 1) {
            $num = Db::table('toys_egg')->where('uuid', $user['id'])->where('status', 3)->count('id');
        } else if ($job['type'] == 2) {
            $num = $this->dao->search(['uuid' => $user['id'], 'status' => 2])->count('id');
        }
        if ($num >= $info['num']) {
            return Db::transaction(function () use ($user, $companyId, $param, $job) {
                $res = Db::table('toys_gear_job_log')->insert([
                    'uuid' => $user['id'],
                    'job_id' => $param['id'],
                    'status' => 1,
                    'company_id' => $companyId
                ]);
                if ($res) {
                    for ($i = 0; $i < $job['get_num']; $i++) {
                        Db::table('toys_egg')->insert([
                            'uuid' => $user['id'],
                            'company_id' => $companyId
                        ]);
                    }
                }
                return true;
            });
        }
    }

    public function up($userInfo, $companyId)
    {
        $gearRepository = app()->make(ToysGearRepository::class);
        $userRepository = app()->make(UsersRepository::class);

        $ToysLevelRepository = app()->make(ToysLevelRepository::class);
        $level = $ToysLevelRepository->search(['lv' => $userInfo['toys_level']], $companyId)->find();
        if (!$level) throw new ValidateException('请等待操作');
//        $price = $level['price'];
//        if ($userInfo['food'] < $price) throw new ValidateException(web_config($companyId, 'site.tokens') . '不足');
//        if ($level['day_num'] > 0) {
//            $count = app()->make(UsersFoodLogRepository::class)->search(['user_id' => $userInfo['id'], 'remark' => '扭蛋兔开启'])->whereTime('add_time', 'today')->count('id');
//            if ($count >= $level['day_num']) throw new ValidateException('次数超限');
//        }
//        if ($level['max_num'] > 0) {
//            $count = app()->make(UsersFoodLogRepository::class)->search(['user_id' => $userInfo['id'], 'remark' => '扭蛋兔开启'])->count('id');
//            if ($count >= $level['max_num']) throw new ValidateException('次数超限');
//        }
        $egg = Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 1)->count('id');
        if ($egg <= 0) throw new ValidateException('请等待产蛋');

        $res1 = Cache::store('redis')->setnx('toys_up' . $userInfo['id'], $userInfo['id']);
        Cache::store('redis')->expire('toys_up' . $userInfo['id'], 2);
        if (!$res1) throw new ValidateException('操作频繁!!');

        return Db::transaction(function () use ($userInfo, $level, $companyId, $gearRepository, $userRepository, $ToysLevelRepository) {
            $chance = json_decode($level['chance'], true);
            if (!$chance) throw new ValidateException('请等待配置');
            $item = [];
            foreach ($chance as $key => $value) {
                $gearCount = $gearRepository->search(['level_id' => $value['gear_level']], $companyId)->count('id');
                if ($gearCount > 0) {
                    $item[$value['gear_level']] = $value['rate'];
                }
            }
            $goodsId = getRand($item);
            if (!$goodsId) throw new ValidateException('请等待配置');
            $gear = $gearRepository->search(['level_id' => $goodsId], $companyId)->orderRand()->find();
            if (!$gear) throw new ValidateException('请等待配置');

//            $res = $userRepository->batchFoodChange($userInfo['id'], 3, (-1) * $price, ['remark' => '扭蛋兔开启', 'company_id' => $companyId], 4);
//            if (!$res) throw new ValidateException('操作失败');
            $res = Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 1)->limit(1)->update(['status' => 3]);
            $result = $this->addInfo($companyId, [
                'uuid' => $userInfo['id'],
                'gear_id' => $gear['id'],
                'gear_type' => $gear['type']
            ]);
            $progress = Db::table('toys_user')->where(['uuid' => $userInfo['id'], 'level_id' => $level['id']])->sum('num');
            $arr = [
                [
                    'uuid' => $userInfo['id'],
                    'company_id' => $companyId,
                    'num' => $level['up'],
                    'level_id' => $level['id'],
                ]
            ];
            if ($progress + $level['up'] >= $level['exp']) {
                $next = $ToysLevelRepository->search(['lv' => $userInfo['lv'] + 1], $companyId)->find();
                if ($next) {
                    $arr = [
                        [
                            'uuid' => $userInfo['id'],
                            'company_id' => $companyId,
                            'num' => ($level['exp'] - $progress),
                            'level_id' => $level['lv'],
                        ],
                        [
                            'uuid' => $userInfo['id'],
                            'company_id' => $companyId,
                            'num' => $level['up'] - ($level['exp'] - $progress),//5 - (100 - 98)
                            'level_id' => $level['lv'] + 1,
                        ]
                    ];
                    $userRepository->update($userInfo['id'], ['toys_level' => $level['lv'] + 1, 'toys_level_time' => date("Y-m-d H:i:s")]);
                }
            }
            Db::table('toys_user')->insertAll($arr);
            $details = $gearRepository->getDetail($gear['id']);
            $details['user_gear_id'] = $result['id'];
            return $details;
        });
    }

    public function sub($data, $user, $companyId)
    {
        $where = [
            'status' => 1,
            'uuid' => $user['id'],
            'gear_id' => $data['gear_id'],
        ];
        $info = $this->dao->search($where, $companyId)->find();
        if (!$info) throw new ValidateException('请先获取装备');
        if ($info['is_use'] != 1) {
            $this->dao->whereUpdate([
                'gear_type' => $info['gear_type'],
                'status' => 1,
                'uuid' => $user['id']
            ], ['is_use' => 2]);
            $this->dao->update($info['id'], ['is_use' => 1, 'edit_time' => date('Y-m-d H:i:s')]);
        }
        return true;
    }

    public function down($data, $user, $companyId)
    {
        $where = [
            'status' => 1,
            'uuid' => $user['id'],
            'gear_id' => $data['gear_id'],
        ];
        $info = $this->dao->search($where, $companyId)->where('id', $data['id'])->find();
        if (!$info) throw new ValidateException('请先获取装备');
        if ($info['is_use'] == 1) throw new ValidateException('已装备');
        $toysTaskLogRepository = app()->make(ToysTaskLogRepository::class);
        $toysTaskRepository = app()->make(ToysTaskRepository::class);
        $count = $toysTaskLogRepository->search(['uuid' => $user['id']], $companyId)->count('id');

        $res1 = Cache::store('redis')->setnx('toys_down' . $user['id'], $user['id']);
        Cache::store('redis')->expire('toys_down' . $user['id'], 1);
        if (!$res1) throw new ValidateException('操作频繁!!');

        return Db::transaction(function () use ($info, $toysTaskRepository, $toysTaskLogRepository, $count, $user, $companyId) {
            $taskNext = $toysTaskRepository->search(['num' => $count + 1], $companyId)->find();
            if ($taskNext) {
                $log = $toysTaskLogRepository->search(['uuid' => $user['id'], 'num' => $count + 1, 'task_id' => $taskNext['id']], $companyId)->find();
                if (!$log) {
                    $toysTaskLogRepository->addInfo($companyId, [
                        'uuid' => $user['id'],
                        'num' => $count + 1,
                        'price' => $taskNext['price'],
                        'task_id' => $taskNext['id'],
                    ]);
                }
//                if ($taskNext['price'] > 0) {
//                    $userRepository = app()->make(UsersRepository::class);
//                    $userRepository->batchBalanceChange($user['id'], 4, $taskNext['price'], ['remark' => '分解装备', 'company_id' => $companyId], 4);
//                }
            }
            return $this->dao->update($info['id'], ['status' => 2, 'edit_time' => date('Y-m-d H:i:s')]);
        });
    }

    public function receive($data, $userInfo, $companyId)
    {
        $toysTaskLogRepository = app()->make(ToysTaskLogRepository::class);
        $toysTaskRepository = app()->make(ToysTaskRepository::class);
        $where = [
            'uuid' => $userInfo['id'],
            'task_id' => $data['task_id'],
            'status' => 2,
        ];
        $log = $toysTaskLogRepository->search($where, $companyId)->find();
        if (!$log) throw new ValidateException('请先完成分解任务');
        $res1 = Cache::store('redis')->setnx('toys_receive' . $userInfo['id'], $userInfo['id']);
        Cache::store('redis')->expire('toys_receive' . $userInfo['id'], 1);
        if (!$res1) throw new ValidateException('操作频繁!!');

        return Db::transaction(function () use ($log, $userInfo, $toysTaskLogRepository, $companyId) {
            if ($log['price'] > 0) {
                $userRepository = app()->make(UsersRepository::class);
                $userRepository->batchBalanceChange($userInfo['id'], 4, $log['price'], ['remark' => '分解装备', 'company_id' => $companyId], 4);
            }
            $toysTaskLogRepository->update($log['id'], ['status' => 1]);
            return $log['price'];
        });
    }

    public function log($userInfo, $companyId)
    {
        $toysTaskLogRepository = app()->make(ToysTaskLogRepository::class);
        $toysTaskRepository = app()->make(ToysTaskRepository::class);
        $task = $toysTaskRepository->search([], $companyId)
            ->withAttr('is_true', function ($v, $data) use ($userInfo, $companyId, $toysTaskLogRepository) {
                $where = [
                    'uuid' => $userInfo['id'],
                    'num' => $data['num'],
                    'task_id' => $data['id'],
                ];
                $info = $toysTaskLogRepository->search($where, $companyId)->find();
                if ($info) {
                    return $info['status'];
                }
                return 0;
            })
            ->append(['is_true'])
            ->order('num asc')->select();
        return $task;
    }

    public function getMyGear($userInfo, $page, $limit, $companyId)
    {
        $where = [
            'uuid' => $userInfo['id'],
            'company_id' => $companyId,
            'status' => 1,
        ];
        $list = $this->dao->search($where, $companyId)
            ->with(['gear' => function ($q) {
                $q->field('id,file_id,title,gw,down')->with(['cover' => function ($q) {
                    $q->bind(['logo' => 'show_src']);
                }]);
            }])
            ->group('gear_id')
            ->field('*,count(id) as gearCount')
            ->page($page, $limit)
            ->select();
        return $list;
    }

    public function getConf($userInfo, $companyId)
    {
        $num = Db::table('toys_user')->where(['uuid' => $userInfo['id'], 'level_id' => $userInfo['toys_level']])->sum('num');
        $ToysLevelRepository = app()->make(ToysLevelRepository::class);
        $level = $ToysLevelRepository->search(['lv' => $userInfo['toys_level']], $companyId)->find();
        $where = [
            'uuid' => $userInfo['id'],
            'company_id' => $companyId,
            'status' => 1,
            'is_use' => 1,
        ];
        $type = ToysGearRepository::TYPE;
        $gear = [];
        foreach ($type as $k => $v) {
            $gear[$k]['title'] = $v;
            $equipment = $this->dao->search($where, $companyId)
                ->where(['gear_type' => $k])
                ->with(['gear' => function ($q) {
                    $q->field('id,file_id,title,gw,down,produce')->with(['cover' => function ($q) {
                        $q->bind(['logo' => 'show_src']);
                    }]);
                }])
                ->find();
            $cate = app()->make(ToysGearCateRepository::class)->search([], $companyId)->where('id', $k)->find();
            if ($cate) {
                $cate['firm_price'] = Db::table('toys_gear_firm')->where(['uuid' => $userInfo['id'], 'type' => $k])->sum('price');
                $cate['firm_num'] = Db::table('toys_gear_firm')->where(['uuid' => $userInfo['id'], 'type' => $k])->count('id');
                $cate['firm_rate'] = bcadd($equipment['gear']['produce'] ?? 0, bcmul($cate['produce_up'], $cate['firm_num'], 7), 7);
                $cate['produce_num'] = Db::table('toys_gear_produce')->where(['uuid' => $userInfo['id'], 'type' => $k])->sum('produce');
                $cate['day_produce_num'] = Db::table('toys_gear_produce')->where(['uuid' => $userInfo['id'], 'type' => $k])->whereTime('add_time', 'today')->sum('produce');
            }
            $gear[$k]['equipment'] = $equipment;
            $gear[$k]['cate'] = $cate;

        }
//        $gear = array_values($gear);

        $toysTaskLogRepository = app()->make(ToysTaskLogRepository::class);
        $toysTaskRepository = app()->make(ToysTaskRepository::class);
        $task = [
            'total' => $toysTaskRepository->search([], $companyId)->count('id'),
            'num' => $toysTaskLogRepository->search(['uuid' => $userInfo['id']], $companyId)->count('id'),
        ];

        $jobGearJobRepository = app()->make(ToysGearJobRepository::class);
        $ids = Db::table('toys_gear_job_log')->where(['uuid' => $userInfo['id']])->column('job_id');
        $job = $jobGearJobRepository->search([], $companyId)
            ->whereNotIn('id', $ids)
            ->withAttr('use_num', function ($v, $data) use ($userInfo, $companyId, $toysTaskLogRepository) {
                if ($data['type'] == 1) {
                    return Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 3)->count('id');
                } else if ($data['type'] == 2) {
                    return $this->dao->search(['uuid' => $userInfo['id'], 'status' => 2])->count('id');
                }
            })
            ->withAttr('type_name', function ($q, $data) {
                if ($data['type'] == 1) {
                    return '扭蛋任务';
                } else {
                    return '分解任务';
                }
            })
            ->append(['use_num', 'type_name'])
            ->order('sort asc,id desc')
            ->find();


        return ['gear' => $gear, 'level' => ['exp' => $level['exp'], 'up' => $level['up'], 'num' => $num], 'task' => $task, 'job' => $job];
    }

}