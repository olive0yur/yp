<?php

namespace app\common\repositories\rabbit;

use app\common\dao\rabbit\ToysGearCateDao;
use app\common\dao\rabbit\ToysGearDao;
use app\common\dao\rabbit\ToysGearJobDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\sign\SignAbcRepository;
use app\common\repositories\sign\SignRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class ToysGearJobRepository
 * @package app\common\repositories\rabbit
 * @mixin ToysGearJobDao
 */
class ToysGearJobRepository extends BaseRepository
{


    public function __construct(ToysGearJobDao $dao)
    {
        $this->dao = $dao;

    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
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
            ->withAttr('type_name', function ($q, $data) {
                if ($data['type'] == 1) {
                    return '扭蛋任务';
                } else {
                    return '分解任务';
                }
            })
            ->append(['type_name'])
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

    public function getGear($userInfo, $companyId)
    {
        $where = [
            'uuid' => $userInfo['id'],
            'company_id' => $companyId,
            'status' => 1,
            'is_use' => 1,
        ];
        $type = ToysGearRepository::TYPE;
        $number = 0;
        $price = 0;
        $produce_num = 0;
        $day_produce_num = 0;
        $toysBackpackRepository = app()->make(ToysBackpackRepository::class);
        foreach ($type as $k => $v) {
            $equipment = $toysBackpackRepository->search($where, $companyId)
                ->where(['gear_type' => $k])
                ->with(['gear' => function ($q) {
                    $q->field('id,file_id,title,gw,down,produce')->with(['cover' => function ($q) {
                        $q->bind(['logo' => 'show_src']);
                    }]);
                }])
                ->find();
            $cate = $this->dao->search([], $companyId)->where('id', $k)->find();
            if ($cate) {
                $firm_price = Db::table('toys_gear_firm')->where(['uuid' => $userInfo['id'], 'type' => $k])->sum('price');
                $firm_num = Db::table('toys_gear_firm')->where(['uuid' => $userInfo['id'], 'type' => $k])->count('id');
                $firm_rate = bcadd($equipment['gear']['produce'] ?? 0, bcmul($cate['produce_up'], $firm_num, 7), 7);
                $produce = Db::table('toys_gear_produce')->where(['uuid' => $userInfo['id'], 'type' => $k])->sum('produce');
                $day_produce = Db::table('toys_gear_produce')->where(['uuid' => $userInfo['id'], 'type' => $k])->whereTime('add_time', 'today')->sum('produce');
                $produce_num = bcadd($produce_num, $produce, 7);
                $day_produce_num = bcadd($day_produce_num, $day_produce, 7);
                $number = bcadd($firm_rate, $number, 7);
                $price = bcadd($firm_price, $price, 7);
            }
        }
        $number30 = bcmul($number, 30, 7);
        $number365 = bcadd($number, 365, 7);
        return compact('number', 'price', 'produce_num', 'day_produce_num', 'number30', 'number365');
    }

    public function firmLog($where, $userInfo, $companyId, $page, $limit)
    {
        $where['uuid'] = $userInfo['id'];
        $query = Db::table('toys_gear_produce')->where($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('add_time desc')
            ->select();
        return compact('count', 'list');
    }

    public function firm($data, $userInfo, $companyId)
    {
        $info = $this->dao->search([], $companyId)->where('id', $data['type'])->find();
        if (!$info) throw new ValidateException('请等待配置');
        if ($userInfo['food'] < $info['price_up'] * $data['num']) throw new ValidateException('余额不足');
        return Db::transaction(function () use ($data, $userInfo, $companyId, $info) {
            $userRepository = app()->make(UsersRepository::class);
            $userRepository->batchFoodChange($userInfo['id'], 3, (-1) * $info['price_up'] * $data['num'], ['remark' => '强化消耗'], 4);
            $old = Db::table('toys_gear_firm')->where(['uuid' => $userInfo['id'], 'type' => $data['type']])->order('id desc')->value('rl');
            for ($i = 0; $i < $data['num']; $i++) {
                Db::table('toys_gear_firm')->insert([
                    'uuid' => $userInfo['id'],
                    'type' => $data['type'],
                    'num' => $data['num'],
                    'price' => $info['price_up'],
                    'rl' => $old + $i,
                    'company_id' => $companyId,
                ]);
            }
            app()->make(ToysBackpackRepository::class)->whereUpdate(['uuid' => $userInfo['id'], 'gear_type' => $data['type']], ['rl' => $old + $data['num']]);
        });

    }
}