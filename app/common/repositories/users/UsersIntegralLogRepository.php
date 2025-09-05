<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersIntegralLogDao;
use app\common\model\users\UsersModel;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;

class UsersIntegralLogRepository extends BaseRepository
{

    const LOG_TYPE = [
        1 => '后台调整',
        4 => '活动抽奖',
        8 => '转赠',
        9 => '卡牌抵扣'
    ];

    public function __construct(UsersIntegralLogDao $dao)
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


    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }


    public function addXls($companyId,$data)
    {
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        foreach ($data as $v){
            $uuid = $usersRepository->search(['mobile'=>$v['phone']],$companyId)->value('id');
            if($uuid){
                $usersRepository->integralChange($uuid,4,$v['num'],['remark'=>'后台调整','company_id'=>$companyId],4);
            }
        }
        return true;
    }

    public function editInfo($id,array $data)
    {
        return $this->dao->update($id,$data);
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
    public function batchAddLog($userInfo, float $amount, int $logType, array $data, $beforeChange, $afterChange,$trackPort = 9)
    {
        $info = [];
        $befor = [];
        $after = [];
        $arr = [];
        $array = [];
        foreach($userInfo as $k => $v) {
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
        foreach($beforeChange as $k => $i) {
            $befor[] = [
                'before_change' => $i
            ];
        } 
        foreach($afterChange as $k => $i) {
            $after[] = [
                'after_change' => $i
            ];
        } 
        foreach($info as $k=>$v){
            $arr[] = array_merge($v,$befor[$k]);
        }
        foreach($arr as $k=>$v){
            $array[] = array_merge($v,$after[$k]);
        }
        return $this->dao->insertAll($array);
    }

    /**
     * 获取积分日志
     */
    public function integralLogList(array $where, $page, $limit, $userId = null)
    {
        $where['user_id'] = $userId ?: $where['user_id'] ?: '';
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('amount,before_change,after_change,add_time,log_type')
            ->order('id desc')
            ->select();
        foreach ($list as $v) {
            $v['log_type'] = self::LOG_TYPE[$v['log_type']] ?? '';
        }
        return compact('count', 'list');
    }

    public function TransferIntegral($data,$uuid,$companyId)
    {
        $user = (new UsersModel())->where('id',$uuid)->field('id,integral,mobile')->find();
        if($user['integral'] < $data['num']) throw new ValidateException('积分不足!');
        $getUser = (new UsersModel())->where('mobile',$data['mobile'])->field('id,integral,mobile')->find();
        if(!$getUser) throw new ValidateException('请重新输入转赠用户!');
        if($getUser['id'] == $uuid) throw new ValidateException('请重新输入转赠用户!');
        $usersRepository = app()->make(UsersRepository::class);
        $usersRepository->integralChange($getUser['id'], 8, $data['num'], ['remark'=>'用户转赠-'.$user['mobile'],'company_id'=>$companyId],$user['id']);
        $usersRepository->integralChange($user['id'], 8, ($data['num'] * -1), ['remark'=>'转赠用户-'.$getUser['mobile'],'company_id'=>$companyId],$getUser['id']);
        return true;
    }
}