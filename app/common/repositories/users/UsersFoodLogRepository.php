<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersFoodLogDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\game\LevelTeamRepository;
use app\common\repositories\users\user\AgentUserRepository;

class UsersFoodLogRepository extends BaseRepository
{
    const LOG_TYPE = [
        1 => '后台调整',
        2 => '充值',
        3 => '消费',
        4 => '收入',
        5 => '转赠',
        6 => '转赠手续费',
    ];

    public function __construct(UsersFoodLogDao $dao)
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
        $sum = $query->sum(\think\facade\Db::raw('abs(amount)'));
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) use ($where) {
                    $query->field('id,mobile,nickname');
                    if (isset($where['mobile']) && $where['mobile'] !== '') {
                        $query->where('nickname|mobile', 'like', '%' . trim($where['mobile'] . '%'));
                    }
                    $query->bind(['mobile' => 'mobile', 'nickname' => 'nickname']);
                }
            ])
            ->field('id,user_id,amount,before_change,after_change,log_type,remark,track_port,add_time')
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count', 'sum');
    }

    public function getAgentList(array $where, $page, $limit, int $companyId = 0)
    {
        /** @var AgentUserRepository $agentUserRepository */
        $agentUserRepository = app()->make(AgentUserRepository::class);
        $userInfo = $agentUserRepository->getLoginUserInfo();
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);
        $ids = $usersPushRepository->search(['parent_id' => $userInfo['id']])->whereIn('levels', [1, 2, 3])->column('user_id');
        $ids = array_merge($ids, [$userInfo['id']]);
        $query = $this->dao->search($where, $companyId)->whereIn('user_id', $ids);
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
                'is_frends' => $data['is_frends'] ?? 1,
                'child_id' => $data['child_id'] ?? 0,
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
    public function foodLogList($type, $page, $limit, $userId = null, $companyId = null)
    {
        $where['user_id'] = $userId;
        $query = $this->dao->search($where, $companyId);
        switch ($type) {
            case 1:
                $query->whereIn('log_type', [1, 2, 4]);
                break;
            case 2:
                $query->whereIn('log_type', [3]);
                break;
            case 3:
                $query->where('log_type', [5, 6]);
                break;
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

    public function frendsRawd($uuid, $companyId)
    {
        $user = app()->make(UsersRepository::class)->get($uuid);
        $data['today'] = $this->dao->search(['user_id' => $uuid, 'is_frends' => 2], $companyId)->whereTime('add_time', 'today')->where('amount', '>', 0)->sum('amount');
        $data['total'] = $this->dao->search(['user_id' => $uuid, 'is_frends' => 2], $companyId)->sum('amount');;
        $data['frend'] = app()->make(UsersPushRepository::class)->search(['parent_id' => $uuid, 'levels' => 1], $companyId)->count();
        $data['team_dividend'] = app()->make(LevelTeamRepository::class)->search(['level' => $user['team_vip']], $companyId)->value('dividend');
        $data['team_income'] =  $this->dao->search(['user_id' => $uuid, 'is_frends' => 2], $companyId)->where('remark','团队收益')->sum('amount');;
        return $data;
    }
}