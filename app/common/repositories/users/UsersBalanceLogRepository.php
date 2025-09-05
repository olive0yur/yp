<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersBalanceLogDao;
use app\common\repositories\BaseRepository;

class UsersBalanceLogRepository extends BaseRepository
{
    const LOG_TYPE = [
        1 => '后台调整',
        2 => '充值',
        3 => '消费',
        4 => '收入',
        5 => '分红',
        6 => '开盲盒',
    ];

    public function __construct(UsersBalanceLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getList(array $where, $page, $limit, int $companyId = 0)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,mobile');
                    $query->bind(['mobile' => 'mobile']);
                }
            ])
            ->field('id,user_id,amount,before_change,after_change,log_type,remark,track_port,add_time')
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function addInfo($companyId, $data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($id, array $data)
    {
        return $this->dao->update($id, $data);
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


    /**
     * 添加日志
     *
     * @param int $userId 用户ID
     * @param float $amount 变动金额
     * @param int $logType 变动类型
     * @param array $data 数据
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function addLog(int $userId, float $amount, int $logType, array $data)
    {
        $data['user_id'] = $userId;
        $data['amount'] = $amount;
        $data['log_type'] = $logType;
        $data['add_time'] = date('Y-m-d H:i:s');
        $data['serial_number'] = $this->generateSerialNumber($logType, $userId);

        return $this->dao->create($data);
    }

    /**
     * 添加日志
     *
     * @param array $userInfo 用户信息
     * @param float $amount 变动金额
     * @param int $logType 变动类型
     * @param array $data 数据
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function batchAddLog($userInfo, float $amount, int $logType, array $data, $beforeChange, $afterChange, $trackPort = 9)
    {
        $info = [];
        $befor = [];
        $after = [];
        $arr = [];
        $array = [];
        foreach ($userInfo as $k => $v) {
            $info[] = [
                'company_id' => $v['company_id'],
                'user_id' => $v['id'],
                'amount' => $amount,
                'log_type' => $logType,
                'add_time' => date('Y-m-d H:i:s'),
                'remark' => $data['remark'],
                'track_port' => $trackPort
            ];
        }

        foreach ($beforeChange as $k => $i) {
            $befor[] = [
                'before_change' => $i
            ];
        }
        foreach ($afterChange as $k => $i) {
            $after[] = [
                'after_change' => $i
            ];
        }
        foreach ($info as $k => $v) {
            $arr[] = array_merge($v, $befor[$k]);
        }

        foreach ($arr as $k => $v) {
            $array[] = array_merge($v, $after[$k]);
        }

        return $this->dao->insertAll($array);
    }

    public function generateSerialNumber($type, $userId)
    {
        $prefix = 'AA';
        if ($type == 2) {
            $prefix = 'CZ';
        }
        $date = date('Ymd');


        return $prefix . $date . str_pad($userId, 14, str_shuffle(date('His')), STR_PAD_BOTH) . rand(1111, 9999);
    }

    /**
     * 获取余额日志
     */
    public function balanceLogList(array $where, $page, $limit, $userId = null)
    {
        $where['user_id'] = $userId ?: $where['user_id'] ?: '';
        $query = $this->dao->search($where);
        if ($where['type'] > 0) {
            if ($where['type'] == 1) {
                $query->whereRaw('amount > 0');
            } else {
                $query->whereRaw('amount < 0');
            }
        }
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('amount,before_change,after_change,add_time,log_type,remark')
            ->order('id desc')
            ->select();
        foreach ($list as $v) {
            $v['log_type'] = self::LOG_TYPE[$v['log_type']] ?? '';
        }

        return compact('count', 'list');
    }
}