<?php

namespace app\common\repositories\active\syn;

use app\common\dao\active\syn\ActiveSynDao;
use app\common\repositories\active\ActiveRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\helper\SnowFlake;
use app\jobs\ActiveSynJob;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class ActiveSynRepository
 * @package app\common\repositories\active
 * @mixin ActiveSynDao
 */
class ActiveSynRepository extends BaseRepository
{

    public $usersBoxRepository;
    public $usersPoolRepository;
    public $activeSynInfoRepository;
    public $activeSynLogRepository;
    public $poolSaleRepository;

    public function __construct(ActiveSynDao $dao)
    {
        $this->usersPoolRepository = app()->make(UsersPoolRepository::class);
        $this->usersBoxRepository = app()->make(UsersBoxRepository::class);
        $this->activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);
        $this->activeSynLogRepository = app()->make(ActiveSynLogRepository::class);
        $this->poolSaleRepository = app()->make(PoolSaleRepository::class);
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['cover' => function ($query) {
                $query->bind(['picture' => 'show_src']);
            }])
            ->hidden(['file'])->order('id desc')
            ->select();

        return compact('count', 'list');
    }

    public function getDetail(int $id)
    {
        $with = [
            'cover' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['picture' => 'show_src']);
            },
        ];
        $data = $this->dao->search([])
            ->with($with)
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

    public function getApiDetail(array $where, int $uuid, int $companyId = null)
    {
        $data = $this->dao->search([], $companyId)
            ->where('id', $where['with_id'])
            ->find();
        if (!$data) {
            throw new ValidateException('with_id 参数传值错误!');
        }
        $info = app()->make(ActiveRepository::class)->search([], $companyId)->where('id', $where['id'])
            ->field('id,file_id,start_time,end_time,content')->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }])
            ->find();
        if (!$info) {
            throw new ValidateException('应用活动 ID 参数传值错误!');
        }
        $map['syn_id'] = $where['with_id'];
        $list = $this->activeSynInfoRepository->getApiDetail($map, $uuid, $companyId);
        $material = $list['material'];
        $buy_num = $this->getUserCanNum(json_decode($material, true), $uuid, $companyId);
        if ($buy_num) {
            if($data['open_max'] <= $buy_num[$uuid]){
                $info['buy_num'] = $data['open_max'];
            }else{
                $info['buy_num'] = $buy_num[$uuid];
            }
            $synNum = array_sum(array_column(json_decode($list['target'], true), 'num'));
            if($info['buy_num'] > $synNum)  $info['buy_num'] = $synNum;
        } else {
            $info['buy_num'] = 0;
        }
        api_user_log($uuid, 3, $companyId, '查看合成:' . $data['title']);
        return compact('info', 'data', 'list');
    }

    public function getDetail_v1(array $where, int $uuid, int $companyId = null)
    {
        $data = $this->dao->search([], $companyId)
            ->where('id', $where['with_id'])
            ->find();
        if (!$data) {
            throw new ValidateException('with_id 参数传值错误!');
        }
        $info = app()->make(ActiveRepository::class)->search([], $companyId)->where('id', $where['id'])
            ->field('id,file_id,start_time,end_time,content')->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }])
            ->find();
        if (!$info) {
            throw new ValidateException('应用活动 ID 参数传值错误!');
        }
        $map['syn_id'] = $where['with_id'];
        $list = $this->activeSynInfoRepository->getApiDetail_v1($map, $uuid, $companyId);
        $material = $list['material'];
        $buy_num = $this->getUserCanNum_v1(json_decode($material, true), $uuid, $companyId);
        if ($buy_num) {
            if($data['open_max'] <= $buy_num[$uuid]){
                $info['buy_num'] = $data['open_max'];
            }else{
                $info['buy_num'] = $buy_num[$uuid];
            }
            $synNum = array_sum(array_column(json_decode($list['target'], true), 'num'));
            if($info['buy_num'] > $synNum)  $info['buy_num'] = $synNum;
        } else {
            $info['buy_num'] = 0;
        }
        api_user_log($uuid, 3, $companyId, '查看合成:' . $data['title']);
        return compact('info', 'data', 'list');
    }
    public function getUserCanNum_v1($collection = [], $uuid, $companyId = null)
    {
        if (!$collection) {
            return [];
        }
        $ids = array_column($collection, 'goods_id');
        if (!$ids) {
            return [];
        }
        $userCollInfo = app()->make(UsersPoolRepository::class)->search(['status' => 1, 'uuid' => $uuid], $companyId)
            ->field('uuid,pool_id,count(id) as num')->whereIn('pool_id', $ids)
            ->group('pool_id')->order('num asc')
            ->select();

        $poolIds = array_unique(array_column(json_decode($userCollInfo, true), 'pool_id'));
        if (count($ids) != count($poolIds)) return [];

        $uid[$uuid] = 0;
        foreach ($collection as $k => $v) {
            foreach ($userCollInfo as $key => $value) {
                if ($value['num'] >= $v['num'] && $v['goods_id'] == $value['pool_id']) {
                    $uidCount[$v['goods_id']] = intval($value['num'] / $v['num']);
                }
            }
            if(isset($uidCount)){
                $uid[$uuid] = min($uidCount);
            }
        }

        return $uid;
    }


    public function getUserCanNum($collection = [], $uuid, $companyId = null)
    {
        if (!$collection) {
            return [];
        }
        $ids = array_column($collection, 'goods_id');
        if (!$ids) {
            return [];
        }
        $userCollInfo = app()->make(UsersPoolRepository::class)->search(['status' => 1, 'uuid' => $uuid], $companyId)
            ->field('uuid,pool_id,count(id) as num')->whereIn('pool_id', $ids)
            ->group('pool_id')->order('num asc')
            ->select();

        $poolIds = array_unique(array_column(json_decode($userCollInfo, true), 'pool_id'));
        if (count($ids) != count($poolIds)) return [];

        $uid[$uuid] = 0;
        foreach ($collection as $k => $v) {
            foreach ($userCollInfo as $key => $value) {
                if ($value['num'] >= $v['num'] && $v['goods_id'] == $value['pool_id']) {
                    $uidCount[$v['goods_id']] = intval($value['num'] / $v['num']);
                }
            }
            if(isset($uidCount)){
                $uid[$uuid] = min($uidCount);
            }
        }

        return $uid;
    }

    public function getApiPoolList(int $id,$pool_id = null,int $page, int $limit, int $uuid, int $companyId = null)
    {

        $list = $this->usersPoolRepository->search(['status' => 1, 'uuid' => $uuid,'pool_id'=>$pool_id], $companyId)
            ->field('id,pool_id,no')->with(['pool' => function ($query) {
                $query->field('id,title,num,file_id')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }])->page($page, $limit)
            ->select();
        if (!$list) {
            throw new ValidateException('with_id 参数传值错误!');
        }
        return $list;
    }

    ## 选择合成
    public function syn(array $data, int $uuid, int $companyId = null)
    {
        if (!$data['pool'] || !is_array($data['pool'])) {
            throw new ValidateException('参数错误');
        }

        $usersCertRepository = app()->make(UsersCertRepository::class);
        $certInfo = $usersCertRepository->getSearch(['user_id' =>$uuid])->find();
        if(!$certInfo) throw new ValidateException('请先完成实名认证!');
        if (((int)$certInfo['cert_status']) != 2) throw new ValidateException('请先完成实名认证!');


        if(!isset($data['id'])) throw new ValidateException('活动不存在');

        $userPoolId = get_arr_column($data['pool'], 'user_pool_id');

        $res1 = Cache::store('redis')->setnx('syn_' . $uuid, $uuid);
        Cache::store('redis')->expire('syn_' . $uuid, 1);
        if (!$res1) throw new ValidateException('排队中!');

        $syn = $this->dao->search([], $companyId)->where('id', $data['with_id'])->find();
        if (!$syn) throw new ValidateException('合成信息不存在!');

        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $synUserInfo = $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->where('syn_id', $syn['id'])
                ->find();
            if($synUserInfo['id'] <= 0)  throw new ValidateException('无法参与本次活动');
        }

        $active = app()->make(ActiveRepository::class)->search([],$companyId)->where(['id'=>$data['id']])->find();
        if(!$active) throw new ValidateException('活动不存在!');
        $auth = authSyn($active);
        if($auth == 2) throw new ValidateException('活动暂未开始!');
        if($auth == 3) throw new ValidateException('活动已结束!');

        /** @var ActiveSynInfoRepository $activeSynInfoRepository */
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);
        $targetNum = $activeSynInfoRepository->search(['syn_id' => $syn['id'], 'is_target' => 1])->sum('num');
        if (!$targetNum || $targetNum <= 0) {
            throw new ValidateException('卡牌可合成数量不足');
        }

        // 门槛检测
        $this->thresholdDetection($syn['id'], $uuid,$companyId);

        /** @var UsersPoolRepository $userPoolRepository */
        $userPoolRepository = app()->make(UsersPoolRepository::class);
        $userPoolList = $userPoolRepository->getSearch([])
            ->where('uuid', $uuid)
            ->where('id', 'in', $userPoolId)
            ->where('status', 1)
            ->field('id,pool_id')
            ->select();
        if (!count($userPoolList)) {
            throw new ValidateException('您所拥有的卡牌不足');
        }
        $userPoolCountList = convert_arr_key($userPoolList, 'pool_id', true);

        // 合成信息
        $synthesizable = [];
        $list = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 2], $companyId)->select();

        if (count($list) < count($userPoolCountList)) {
            throw new ValidateException('您选择的材料过多');
        }

        // 最少材料量
        $minNum = $syn['num'] > 0 ? $syn['num'] : count($list);

        $synthesizable = $this->calcSynthesizableData($list, $userPoolCountList, $minNum);

        if (!$synthesizable) {
            throw new ValidateException('您所选择的合成材料不全');
        }
        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->limit('1')
                ->where('syn_id', $syn['id'])
                ->update(['status'=>2,'use_time'=>date('Y-m-d H:i:s')]);
        }
        return Db::transaction(function () use ($data, $syn, $uuid, $companyId, $list, $synthesizable) {
            foreach ($synthesizable as $userLogIds) {
                $logIds = [];
                foreach ($userLogIds as $v) {
                    $logIds = array_merge($logIds, $v);
                }
                $this->usersPoolRepository->updates($logIds, ['status' => 5]);

                /** @var PoolOrderNoRepository $poolOrderNoRepository */
                $poolOrderNoRepository = app()->make(PoolOrderNoRepository::class);
                $poolOrderNoRepository->getSearch([])
                    ->where(function ($query) use ($userLogIds) {
                        foreach ($userLogIds as $poolId => $logIds) {
                            $query->whereOr(function ($query) use ($poolId, $logIds) {
                                $query->where('pool_id', $poolId);
                                $query->where('no', 'in', function ($query) use ($logIds) {
                                    $query->name('user_pool')
                                        ->where('id', 'in', $logIds)
                                        ->field('no');
                                });
                            });
                        }
                    })
                    ->update([
                        'status' => 9
                    ]);

                switch ($syn['syn_type']) {
                    case 1: //普通
                        $target = $this->activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->with(['cover' => function ($query) {
                                $query->bind(['picture' => 'show_src']);
                            }])
                            ->orderRand()
                            ->find();
                        if (!$target || $target['num'] <= 0) {
                            throw new ValidateException('卡牌可合成数量不足');
                        }
                        return $this->synTypeOne($target, $uuid, $companyId, $logIds);
                        break;
                    case 2://缤纷
                        $target = $this->activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->select();
                        return $this->synTypeTwo($target, $uuid, $companyId, $logIds, $syn);
                        break;
                }
                api_user_log($uuid, 4, $companyId, '参与合成:' . $syn['title']);
            }
        });
    }


    public function syn_v1(array $data, int $uuid, int $companyId = null)
    {
        if (!$data['syn'] || !is_array($data['syn'])) {
            throw new ValidateException('参数错误');
        }

        $usersCertRepository = app()->make(UsersCertRepository::class);
        $certInfo = $usersCertRepository->getSearch(['user_id' =>$uuid])->find();
        if(!$certInfo) throw new ValidateException('请先完成实名认证!');
        if (((int)$certInfo['cert_status']) != 2) throw new ValidateException('请先完成实名认证!');


        if(!isset($data['id'])) throw new ValidateException('活动不存在');


        $res1 = Cache::store('redis')->setnx('syn_' . $uuid, $uuid);
        Cache::store('redis')->expire('syn_' . $uuid, 1);
        if (!$res1) throw new ValidateException('排队中!');

        $syn = $this->dao->search([], $companyId)->where('id', $data['with_id'])->find();
        if (!$syn) throw new ValidateException('合成信息不存在!');

        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $synUserInfo = $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->where('syn_id', $syn['id'])
                ->find();
            if($synUserInfo['id'] <= 0)  throw new ValidateException('无法参与本次活动');
        }

        $active = app()->make(ActiveRepository::class)->search([],$companyId)->where(['id'=>$data['id']])->find();
        if(!$active) throw new ValidateException('活动不存在!');
        $auth = authSyn($active);
        if($auth == 2) throw new ValidateException('活动暂未开始!');
        if($auth == 3) throw new ValidateException('活动已结束!');

        $list = app()->make(ActiveSynKeyRepository::class)->search(['syn_id'=>$data['with_id']])
            ->select();
        if(count($list) < 1) throw new ValidateException('合成配置为完善！');

        $userPoolIdInfo = [];
        foreach ($data['syn'] as $v) {
            $key = app()->make(ActiveSynKeyRepository::class)->search(['syn_id'=>$data['with_id']],$companyId)
                ->where(['id'=>$v['key_id']])->find();
            if(!$key) throw new ValidateException('所选合成规则不存在!');
//            if(count($v['pool']) != $key['type_num']){
//                throw new ValidateException('所选卡牌数量错误！');
//            }

            //TODO  start新规则   判断总数不判断类型
            $ids = [];
            foreach ($v['pool'] as $key=> $va){
                $id_arr = explode(',',$va['user_pool_id']);
                $ids[$key] = $id_arr;
                array_push($userPoolIdInfo,$id_arr);
            }
            $ids_arr = array_column($ids, 'user_pool_id');
            if(count($ids_arr) != $key['num']){
                throw new ValidateException('条件数量不足！');
            }
            //TODO  END

//            foreach ($v['pool'] as $va){
//                $mater = app()->make(ActiveSynMaterInfoRepository::class)->search(['syn_id'=>$data['with_id'],'key_id'=>$key['id'],'pool_id'=>$va['goods_id']],$companyId)->find();
//                if(!$mater) throw new ValidateException('所选合成规则不存在');
//                if(count(explode(',',$va['user_pool_id'])) != $key['num']) throw new ValidateException('所选卡牌数量错误！');
//                for ($i=0;$i<count(explode(',',$va['user_pool_id']));$i++){
//                    $userPoolId[] = explode(',',$va['user_pool_id'])[$i];
//                }
//
//                $count = app()->make(UsersPoolRepository::class)->search(['pool_id'=>$va['goods_id'],'status'=>1],$companyId)->whereIn('id',explode(',',$va['user_pool_id']))->count('id');
//                if($count != $key['num']) throw new ValidateException('您所拥有的卡牌不足！');
//            }
        }


        /** @var ActiveSynInfoRepository $activeSynInfoRepository */
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);
        $targetNum = $activeSynInfoRepository->search(['syn_id' => $syn['id'], 'is_target' => 1])->sum('num');
        if (!$targetNum || $targetNum <= 0) {
            throw new ValidateException('卡牌可合成数量不足');
        }

        $userPoolId = array();
        foreach ($userPoolIdInfo as $v) {
            // 循环遍历子数组
            foreach ($v as $value) {
                // 将值添加到空数组中
                $userPoolId[] = $value;
            }
        }

        // 门槛检测
        $this->thresholdDetection($syn['id'], $uuid,$companyId);

        /** @var UsersPoolRepository $userPoolRepository */
        $userPoolRepository = app()->make(UsersPoolRepository::class);
        $synthesizable = $userPoolRepository->getSearch([])
            ->where('uuid', $uuid)
            ->where('id', 'in', $userPoolId)
            ->where('status', 1)
            ->field('id,pool_id,no')
            ->select();
        if (!count($synthesizable)) {
            throw new ValidateException('您所拥有的卡牌不足');
        }
        $userPoolCountList = convert_arr_key($synthesizable, 'pool_id', true);

        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->limit('1')
                ->where('syn_id', $syn['id'])
                ->update(['status'=>2,'use_time'=>date('Y-m-d H:i:s')]);
        }
        return Db::transaction(function () use ($data, $syn, $uuid, $companyId, $list, $synthesizable,$userPoolCountList) {
            $no = array_column(json_decode($synthesizable,true),'no');
            $ids = array_column(json_decode($synthesizable,true),'id');
            $pids = array_column(json_decode($synthesizable,true),'pool_id');

              $this->usersPoolRepository->updates($ids, ['status' => 5]);

                /** @var PoolOrderNoRepository $poolOrderNoRepository */
                $poolOrderNoRepository = app()->make(PoolOrderNoRepository::class);
                $list = $poolOrderNoRepository->getSearch(['status'=>1])
                    ->whereIn('no',$no)
                    ->whereIn('pool_id',$pids)
                   ->update(['status'=>9]);
                switch ($syn['syn_type']) {
                    case 1: //普通
                        $target = $this->activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->with(['cover' => function ($query) {
                                $query->bind(['picture' => 'show_src']);
                            }])
                            ->orderRand()
                            ->find();
                        if (!$target || $target['num'] <= 0) {
                            throw new ValidateException('卡牌可合成数量不足');
                        }
                        return $this->synTypeOne($target, $uuid, $companyId, $ids);
                        break;
                    case 2://缤纷
                        $target = $this->activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->select();
                        return $this->synTypeTwo($target, $uuid, $companyId, $ids, $syn);
                        break;
                }
                api_user_log($uuid, 4, $companyId, '参与合成:' . $syn['title']);
        });
    }

    /**
     * 门槛检测
     *
     * @return bool
     */
    private function thresholdDetection(int $synId, $uuid,int $companyId = null)
    {
        /** @var ActiveSynInfoRepository $activeSynInfoRepository */
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);

        ## 卡牌
        $poollist = $activeSynInfoRepository->search(['syn_id' => $synId, 'is_target' => 3],$companyId)
            ->where('target_type',  1)
            ->where('num', '>', 0)
            ->field('goods_id,num')->select();
        if (count($poollist) <= 0) {
            return true;
        }
        $poolgoodsIds = get_arr_column($poollist, 'goods_id');

        /** @var UsersPoolRepository $userPoolRepository */
        $userPoolRepository = app()->make(UsersPoolRepository::class);
        $userPoolCountList = $userPoolRepository->getSearch([])
            ->where('uuid', $uuid)
            ->where('pool_id', 'in', $poolgoodsIds)
            ->where('status', 1)
            ->group('pool_id')
            ->column('count(id)', 'pool_id');
        if (!$userPoolCountList) {
            throw new ValidateException('您所拥有的卡牌数未达到门槛条件');
        }
        foreach ($poollist as $v) {
            if (!isset($userPoolCountList[$v['goods_id']]) || $userPoolCountList[$v['goods_id']] < $v['num']) {
                throw new ValidateException('您所拥有的卡牌数未达到门槛条件');
            }
        }

        ## 肓盒
        $boxlist = $activeSynInfoRepository->search(['syn_id' => $synId, 'is_target' => 3],$companyId)
            ->where('target_type',  2)
            ->where('num', '>', 0)
            ->field('goods_id,num')->select();
        if (count($boxlist) <= 0) {
            return true;
        }
        $boxgoodsIds = get_arr_column($boxlist, 'goods_id');

        /** @var UsersBoxRepository $userBoxRepository */
        $userBoxRepository = app()->make(UsersBoxRepository::class);
        $userBoxCountList = $userBoxRepository->getSearch([])
            ->where('uuid', $uuid)
            ->where('box_id', 'in', $boxgoodsIds)
            ->where('status', 1)
            ->group('box_id')
            ->column('count(id)', 'box_id');
        if (!$userBoxCountList) {
            throw new ValidateException('您所拥有的卡牌数未达到门槛条件');
        }
        foreach ($boxlist as $v) {
            if (!isset($userBoxCountList[$v['goods_id']]) || $userBoxCountList[$v['goods_id']] < $v['num']) {
                throw new ValidateException('您所拥有的肓盒数未达到门槛条件');
            }
        }
        return true;
    }

    /**
     * 计算可合成信息
     *
     * @param array $list 材料了列表
     * @param array $userPoolCountList 用户材料信息
     * @param int $minNum 最少材料数
     * @param int $type 合同类型 1选择合成 2一键合成
     * @return array
     */
    private function calcSynthesizableData($list, $userPoolCountList, $minNum, $type = 1,$num = 1)
    {

        $synthesizable = [];
        $meetWith = [];
        $userLogIds = [];

        foreach ($list as $v) {
            if(Request()->action() == 'syn'){ //自选材料
                $param = Request()->param('pool');
                $column = array_column($param,'goods_id');
                if($v['is_must'] == 1 && !in_array($v['goods_id'],$column)){
                     throw new ValidateException('必选卡牌不能为空!');
                }
            }
            $userHave = count($userPoolCountList[$v['goods_id']] ?? []);

            if ($userHave >= $v['num']) {
                $meetWith[] = [
                    'goods_id' => $v['goods_id'],
                    'num' => $v['num']*$num
                ];
                // 满足最小条件
                if ($type != 1) {
                    if (count($meetWith) >= $minNum) {
                        break;
                    }
                }
            }
        }


        if (count($meetWith) < $minNum) {
            return $synthesizable;
        }

        $continue = false;
        foreach ($meetWith as $v) {
            for ($i = 0; $i < $v['num']; $i++) {
                if (isset($userLogIds[$v['goods_id']])) {
                    $userLogIds[$v['goods_id']][] = array_shift($userPoolCountList[$v['goods_id']])['id'];
                } else {
                    $userLogIds[$v['goods_id']] = [array_shift($userPoolCountList[$v['goods_id']])['id']];
                }
            }
            if ($userPoolCountList[$v['goods_id']]) {
                $continue = true;
            }
        }
        $synthesizable[] = $userLogIds;

        if ($type != 1) {
            if ($continue) {
                return array_merge($synthesizable, $this->calcSynthesizableData($list, $userPoolCountList, $minNum, $type));
            }
        }

        return $synthesizable;
    }

    public function synTypeOne($target, $uuid, $companyId, $logIds)
    {
        ## 不管有没有合成，先减去一次机会
        $this->activeSynInfoRepository->search([], $companyId)->where(['id' => $target['id']])
            ->whereExp('num','> 0')->dec('num', 1)->update();
        switch ($target['target_type']) {
            case 1:
                $goods = app()->make(PoolSaleRepository::class)->search([], $companyId)
                    ->where(['id' => $target['goods_id']])->field('id,file_id,title,stock')->with(['cover' => function ($query) {
                        $query->bind(['picture' => 'show_src']);
                    }])->find();
                if (!$goods) throw new ValidateException('卡牌不存在!');
                if ($goods['stock'] < 1) throw new ValidateException('合成卡牌库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['pool_id'] = $target['goods_id'];
                $data['no'] = $no->getNo($target['goods_id'],$uuid);
                $data['price'] = 0.00;
                $data['type'] = 4;
                $re = $this->usersPoolRepository->addInfo($companyId, $data);
                if ($re) {
                    $log['pool_id'] = $target['goods_id'];
                    $log['no'] = $data['no'];
                    $log['uuid'] = $uuid;
                    $log['type'] = 6;
                    app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);

                    Cache::store('redis')->rPop('goods_num_' . $target['goods_id']);
                    $this->poolSaleRepository->search([], $companyId)->where(['id' => $target['goods_id']])->dec('stock', 1)->update();
//                    $this->activeSynInfoRepository->search([], $companyId)->where(['id' => $target['id']])
//                        ->whereExp('num','> 0')->dec('num', 1)->update();
                    $log['syn_pool_id'] = $re['id'];
                    $log['order_id'] = SnowFlake::createOnlyId("No");;
                    $log['syn_info_id'] = $target['id'];
                    $log['goodsIds'] = implode(',', $logIds);
                    $log['uuid'] = $uuid;
                    $this->activeSynLogRepository->addInfo($companyId, $log);
                }
                return ['buy_type' => 1, 'title' => $goods['title'], 'cover' => $goods['picture']];
                break;
            case 2:
                /** @var BoxSaleRepository $boxSaleRepository */
                $boxSaleRepository = app()->make(BoxSaleRepository::class);
                $goods = $boxSaleRepository->search([], $companyId)
                    ->where(['id' => $target['goods_id']])->field('id,file_id,title,stock')->with(['cover' => function ($query) {
                        $query->bind(['picture' => 'show_src']);
                    }])->find();
                if (!$goods) throw new ValidateException('肓盒不存在!');
                if ($goods['stock'] < 1) throw new ValidateException('合成肓盒库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['box_id'] = $target['goods_id'];
                $data['price'] = 0.00;
                $data['type'] = 4;
                $re = $this->usersBoxRepository->addInfo($companyId, $data);
                if ($re) {
                    Cache::store('redis')->rPop('goods_num_' . $target['goods_id']);
                    $boxSaleRepository->search([], $companyId)->where(['id' => $target['goods_id']])->dec('stock', 1)->update();
                    $log['syn_pool_id'] = $re['id'];
                    $log['order_id'] = SnowFlake::createOnlyId("No");;
                    $log['syn_info_id'] = $target['id'];
                    $log['goodsIds'] = implode(',', $logIds);
                    $log['uuid'] = $uuid;
                    $this->activeSynLogRepository->addInfo($companyId, $log);
                }
                return ['buy_type' => 1, 'title' => $goods['title'], 'cover' => $goods['picture']];
                break;
            case 3:
                $log['syn_info_id'] = $target['id'];
                $log['order_id'] = SnowFlake::createOnlyId("No");;
                $log['goodsIds'] = implode(',', $logIds);
                $log['uuid'] = $uuid;
                $this->activeSynLogRepository->addInfo($companyId, $log);
                return ['buy_type' => 3, 'title' => $target['title'], 'cover' => $target['picture']];
        }
    }

    public function addInfo($companyId, $data)
    {

        return Db::transaction(function () use ($data, $companyId) {
            if (isset($data['cover']) && $data['cover']) {
                /** @var UploadFileRepository $uploadFileRepository */
                $uploadFileRepository = app()->make(UploadFileRepository::class);
                $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
                if ($fileInfo['id'] > 0) {
                    $data['file_id'] = $fileInfo['id'];
                }
            }
            unset($data['cover']);
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            return $this->dao->create($data);
        });

    }


    ## 快速合成
    public function synTypeTwo($target, $uuid, $companyId, $logIds, $syn)
    {

            $synInfo = $this->activeSynInfoRepository->getProbabilityRandomTarget($syn['id']);
            $is_fraud = 2;
        if(!$synInfo) throw new ValidateException('合成卡牌不存在');
        ## 不管有没有合成，先减去一次机会
        $this->activeSynInfoRepository->search([], $companyId)->where(['id' => $synInfo['id']])->dec('num', 1)->update();
        switch ($synInfo['target_type']) {
            case 1:
                $goods = app()->make(PoolSaleRepository::class)->search([], $companyId)
                    ->where(['id' => $synInfo['goods_id']])->field('id,file_id,title,stock')
                    ->with(['cover' => function ($query) {
                        $query->bind(['picture' => 'show_src']);
                    }])
                    ->find();
                if (!$goods) throw new ValidateException('卡牌不存在!');
                if ($goods['stock'] < 1) throw new ValidateException('合成卡牌库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['pool_id'] = $synInfo['goods_id'];
                $data['no'] = $no->getNo($synInfo['goods_id'],$uuid);
                $data['price'] = 0.00;
                $data['type'] = 4;
                $re = $this->usersPoolRepository->addInfo($companyId, $data);
                if ($re) {
                    $log['pool_id'] = $synInfo['goods_id'];
                    $log['no'] = $data['no'];
                    $log['uuid'] = $uuid;
                    $log['type'] = 6;
                    app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);

                    Cache::store('redis')->rPop('goods_num_' . $synInfo['goods_id']);
                    $this->poolSaleRepository->search([], $companyId)->where(['id' => $synInfo['goods_id']])->dec('stock', 1)->update();
//                    $this->activeSynInfoRepository->search([], $companyId)->where(['id' => $synInfo['id']])->dec('num', 1)->update();
                    $log['syn_info_id'] = $synInfo['id'];
                    $log['syn_pool_id'] = $re['id'];
                    $log['order_id'] = SnowFlake::createOnlyId("No");;
                    $log['goodsIds'] = implode(',', $logIds);
                    $log['uuid'] = $uuid;
                    $this->activeSynLogRepository->addInfo($companyId, $log);

                    return ['buy_type' => 1, 'title' => $goods['title'], 'cover' => $goods['picture']];
                }
            case 2:
                /** @var BoxSaleRepository $boxSaleRepository */
                $boxSaleRepository = app()->make(BoxSaleRepository::class);
                $goods = $boxSaleRepository->search([], $companyId)
                    ->where(['id' => $target['goods_id']])->field('id,file_id,title,stock')->with(['cover' => function ($query) {
                        $query->bind(['picture' => 'show_src']);
                    }])->find();
                if (!$goods) throw new ValidateException('肓盒不存在!');
                if ($goods['stock'] < 1) throw new ValidateException('合成肓盒库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['box_id'] = $target['goods_id'];
                $data['price'] = 0.00;
                $data['type'] = 4;
                $re = $this->usersBoxRepository->addInfo($companyId, $data);
                if ($re) {
                    Cache::store('redis')->rPop('goods_num_' . $target['goods_id']);
                    $boxSaleRepository->search([], $companyId)->where(['id' => $target['goods_id']])->dec('stock', 1)->update();
                    $log['syn_pool_id'] = $re['id'];
                    $log['order_id'] = SnowFlake::createOnlyId("No");;
                    $log['syn_info_id'] = $target['id'];
                    $log['goodsIds'] = implode(',', $logIds);
                    $log['uuid'] = $uuid;
                    $this->activeSynLogRepository->addInfo($companyId, $log);
                }
                return ['buy_type' => 1, 'title' => $goods['title'], 'cover' => $goods['picture']];
                break;
            case 3:
                $log['syn_info_id'] = $synInfo['id'];
                $log['order_id'] = SnowFlake::createOnlyId("No");;
                $log['goodsIds'] = implode(',', $logIds);
                $log['uuid'] = $uuid;
                $this->activeSynLogRepository->addInfo($companyId, $log);
                return ['buy_type' => 3, 'title' => $synInfo['title'], 'cover' => $synInfo['picture']];
        }
    }

    public function editInfo($info, $data)
    {
        if (isset($data['cover']) && $data['cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            unset($data['cover']);
            if ($fileInfo['id'] != $info['id']) {
                $data['file_id'] = $fileInfo['id'];
            }
        }

        return $this->dao->update($info['id'], $data);
    }

    ## 批量合成【一次提交多份】
    public function fastSyn(array $data, int $uuid, int $companyId = null)
    {
        $res1 = Cache::store('redis')->setnx('syn_' . $uuid, $uuid);
        Cache::store('redis')->expire('syn_' . $uuid, 1);
        if (!$res1) throw new ValidateException('排队中!');


        $usersCertRepository = app()->make(UsersCertRepository::class);
        $certInfo = $usersCertRepository->getSearch(['user_id' =>$uuid])->find();
        if(!$certInfo) throw new ValidateException('请先完成实名认证!');
        if (((int)$certInfo['cert_status']) != 2) throw new ValidateException('请先完成实名认证!');

        $syn = $this->dao->search([], $companyId)->where('id', $data['with_id'])->find();
        if (!$syn) throw new ValidateException('合成信息不存在!');

        if($syn['is_open'] != 1)  throw new ValidateException('本次活动不支持批量合成!');
        if($syn['is_open'] == 1){
            if($data['num'] > $syn['open_max'])  throw new ValidateException('合成数量超出最大限值'. $data['num']);
        }

        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $synUserInfo = $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->where('syn_id', $syn['id'])
                ->find();
            if($synUserInfo['id'] <= 0)  throw new ValidateException('无法参与本次活动');
        }

        $active = app()->make(ActiveRepository::class)->search([],$companyId)->where(['id'=>$data['id']])->find();
        if(!$active) throw new ValidateException('活动不存在!');
        $auth = authSyn($active);
        if($auth == 2) throw new ValidateException('活动暂未开始!');
        if($auth == 3) throw new ValidateException('活动已结束!');
        /** @var ActiveSynInfoRepository $activeSynInfoRepository */
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);

        // 门槛检测
        $this->thresholdDetection($syn['id'], $uuid,$companyId);

        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        ## 合成材料
        $list = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 2], $companyId)->order('is_must asc')->select();
        $userPoolList = [];
        foreach ($list as $key => $v) {
            $temp = $this->usersPoolRepository->search(['pool_id' => $v['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)
                ->limit($v['num'] * $data['num'])
                ->field('id,pool_id')
                ->select()->toArray();
            if($v['is_must'] == 1 && count($temp) < $v['num']) throw new ValidateException('您所拥有的必须材料不足');
            $userPoolList = array_merge($userPoolList, $temp);
        }
        $userPoolCountList = convert_arr_key($userPoolList, 'pool_id', true);

        // 最少材料量
        $minNum = $syn['num'] > 0 ? $syn['num'] : count($list);
        $synthesizable = $this->calcSynthesizableData($list, $userPoolCountList, $minNum, 2,$data['num']);
        if (!$synthesizable) {
            throw new ValidateException('您所拥有的卡牌不足');
        }
        if((int)$syn['is_user'] == 1){
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->limit('1')
                ->where('syn_id', $syn['id'])
                ->update(['status'=>2,'use_time'=>date('Y-m-d H:i:s')]);
        }
        return Db::transaction(function () use ($data, $syn, $uuid, $companyId, $synthesizable, $activeSynInfoRepository) {
            $goods = [];
            foreach ($synthesizable as $userLogIds) {
                $logIds = [];
                foreach ($userLogIds as $v) {
                    $logIds = array_merge($logIds, $v);
                }
                $this->usersPoolRepository->updates($logIds, ['status' => 5]);

                /** @var PoolOrderNoRepository $poolOrderNoRepository */
                $poolOrderNoRepository = app()->make(PoolOrderNoRepository::class);
                $poolOrderNoRepository->getSearch([])
                    ->where(function ($query) use ($userLogIds) {
                        foreach ($userLogIds as $poolId => $logIds) {
                            $query->whereOr(function ($query) use ($poolId, $logIds) {
                                $query->where('pool_id', $poolId);
                                $query->where('no', 'in', function ($query) use ($logIds) {
                                    $query->name('user_pool')
                                        ->where('id', 'in', $logIds)
                                        ->field('no');
                                });
                            });
                        }
                    })
                    ->update([
                        'status' => 9
                    ]);


                switch ($syn['syn_type']) {
                    case 1: //普通
                        ## 合成目标
                        $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->with(['cover' => function ($query) {
                                $query->bind(['picture' => 'show_src']);
                            }])
                            ->orderRand()
                            ->find();
                        if (!$target) throw new ValidateException('合成目标配置错误!');
                        if ($target['num'] <= 0) {
                            throw new ValidateException('卡牌可合成数量不足');
                        }
                        $goods = [];
                        for ($i = 1; $i <= $data['num']; $i++) {
                            $goods[] = $this->synTypeOne($target, $uuid, $companyId, $logIds);
                        }
                        return $goods;
                        break;
                    case 2://缤纷
                        $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->orderRand()

                            ->select();
                        if (!$target) throw new ValidateException('合成配置错误!');
                        if( array_sum(array_column(json_decode($target,true),'num')) < $data['num']) {
                            throw new ValidateException('卡牌可合成数量不足');
                        }

                        $goods = [];
                        for ($i = 1; $i <= $data['num']; $i++) {
                            $goods[] = $this->synTypeTwo($target, $uuid, $companyId, $logIds, $syn);
                        }
                        return $goods;
                        break;
                }
            }
            return $goods;
        });
    }

    ## 批量合成【一次提一份】
    public function fastOneSyn(array $data, int $uuid, int $companyId = null)
    {
        $res1 = Cache::store('redis')->setnx('syn_' . $uuid, $uuid);
        Cache::store('redis')->expire('syn_' . $uuid, 1);
        if (!$res1) throw new ValidateException('排队中!');

        $syn = $this->dao->search([], $companyId)->where('id', $data['with_id'])->find();
        if (!$syn) throw new ValidateException('合成信息不存在!');
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);
        switch ($syn['syn_type']) {
            case 1: //普通
                ## 合成目标
                $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])->find();
                if(!$target) throw new ValidateException('合成目标配置错误!');
                $PoolSaleRepository = app()->make(PoolSaleRepository::class);
                $stock_s = $PoolSaleRepository->search([])->where(['id'=>$target['goods_id']])->value('stock');
                if ($stock_s <= 0)
                {
                    throw new ValidateException('合成目标库存不足！');
                }
                break;
            case 2://缤纷
                $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                    ->withAttr('num',function ($v,$data) {
                        return  $data['num'];
                    })
                    ->select();
                if(!$target) throw new ValidateException('合成配置错误!');
                break;
        }


        if ((int)$syn['is_user'] == 1) {
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $synUserInfo = $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->where('syn_id', $syn['id'])
                ->find();
            if ($synUserInfo['id'] <= 0) throw new ValidateException('无法参与本次活动');
        }

        $active = app()->make(ActiveRepository::class)->search([], $companyId)->where(['id' => $data['id']])->find();
        if (!$active) throw new ValidateException('活动不存在!');
        $auth = authSyn($active);
        if ($auth == 2) throw new ValidateException('活动暂未开始!');
        if ($auth == 3) throw new ValidateException('活动已结束!');
        /** @var ActiveSynInfoRepository $activeSynInfoRepository */
        $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);

        // 门槛检测
        $this->thresholdDetection($syn['id'], $uuid, $companyId);
        $targets = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])->find();
        $stock = $this->poolSaleRepository->search([])->where(['id'=>$targets['goods_id']])->value('stock');

        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        ## 合成材料
        $list = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 2], $companyId)->select();
        $userPoolList = [];
        foreach ($list as $key => $v) {
            $temp = $this->usersPoolRepository->search(['pool_id' => $v['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)
                ->limit($v['num'] * $data['num'])
                ->field('id,pool_id')
                ->select()->toArray();
            if (count($temp) < $v['num']) throw new ValidateException('您所拥有的卡牌不足！');
            $userPoolList = array_merge($userPoolList, $temp);

        }

        $userPoolCountList = convert_arr_key($userPoolList, 'pool_id', true);

        // 最少材料量
        $minNum = $syn['num'] > 0 ? $syn['num'] : count($list);
        $synthesizable = $this->calcSynthesizableData($list, $userPoolCountList, $minNum, 2, $data['num']);
        if (!$synthesizable) {
            throw new ValidateException('您所拥有的卡牌不足');
        }
        if ((int)$syn['is_user'] == 1) {
            $activeSynUserRepository = app()->make(ActiveSynUserRepository::class);
            $activeSynUserRepository->getSearch([])
                ->where('status', 1)
                ->where('uuid', $uuid)
                ->limit('1')
                ->where('syn_id', $syn['id'])
                ->update(['status' => 2, 'use_time' => date('Y-m-d H:i:s')]);
        }

        $event['with_id'] = $data['with_id'];
        $event['id'] = $data['id'];
        $event['num'] = $data['num'];
        $event['uuid'] = $uuid;
        $event['company_id'] = $companyId;
        $event['syn'] = $syn->toArray();
        $event['userNo'] = SnowFlake::createOnlyId('syn');
        $isPushed = \think\facade\Queue::push(\app\jobs\ActiveSynJob::class, $event);
        return ['no'=>$event['userNo']];
    }


}