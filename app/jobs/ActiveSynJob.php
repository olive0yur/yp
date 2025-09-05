<?php

namespace app\jobs;

use app\common\repositories\active\syn\ActiveSynLogRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use think\facade\Db;
use think\queue\Job;
use think\facade\Cache;
use think\exception\ValidateException;
use app\common\repositories\fraud\FraudRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\active\syn\ActiveSynInfoRepository;

class ActiveSynJob
{
    public function fire(Job $job, $data)
    {
        try {
            $this->ActiveSyn($data);
        } catch (\Exception $e) {
            exception_log('任务处理失败', $e);
        }
        $job->delete();
    }

    /**
     * 应用合成
     * @param $data
     * @return mixed
     */
    public function ActiveSyn($data)
    {

//        try {
            $uuid = $data['uuid'];
            $companyId = $data['company_id'];
            $syn = $data['syn'];
            return Db::transaction(function () use ($data,$uuid,$companyId,$syn) {
                $fraudRepository = app()->make(FraudRepository::class);
                /** @var UsersPoolRepository $usersPoolRepository */
                $activeSynInfoRepository = app()->make(ActiveSynInfoRepository::class);
                $usersPoolRepository = app()->make(UsersPoolRepository::class);
                ## 合成材料
                $list = $activeSynInfoRepository->search(['syn_id'=>$data['with_id'],'is_target'=>2],$companyId)
                    ->where('is_must',1)
                    ->select();
                $logIds = [];
                foreach ($list as $key => $v){
                    $ids = $usersPoolRepository->search(['pool_id' => $v['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)->column('id');
                    ## 只要有一个不达标 就退出
                    if (count($ids) < $v['num']) throw new ValidateException('您所拥有的卡牌不足！');
                    $updateIds = $usersPoolRepository->search(['pool_id' => $v['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)->whereIn('id',$ids)
                        ->limit($v['num'])->column('id');
                    ## 达到后 就销毁掉
                    $usersPoolRepository->updates($updateIds,['status'=>5]);
                    $logIds = array_merge($logIds,$updateIds);
                }
                array_shift($data['info_id']);
                foreach ($data['info_id'] as $item){
                    $nOmust = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 2], $companyId)
                        ->where(['is_must'=>2,'id'=>$item])->find();
                    if(!$nOmust) throw new ValidateException('请正确选择非必选材料!');

                    $ids = $usersPoolRepository->search(['pool_id' => $nOmust['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)->column('id');
                    ## 只要有一个不达标 就退出
                    if (count($ids) < $nOmust['num']) throw new ValidateException('您所拥有的卡牌不足！');
                    $updateIds = $usersPoolRepository->search(['pool_id' => $nOmust['goods_id'], 'uuid' => $uuid, 'status' => 1], $companyId)->whereIn('id',$ids)->limit($nOmust['num'])->column('id');
                    ## 达到后 就销毁掉
                    $usersPoolRepository->updates($updateIds,['status'=>5]);
                    $logIds = array_merge($logIds,$updateIds);
                }

                if($logIds){
                    $list = $usersPoolRepository->search([],$companyId)->alias('p')
                        ->whereIn('id',$logIds)->select();
                    foreach ($list as $k => $val){
                        app()->make(PoolOrderNoRepository::class)
                            ->search(['no'=>$val['no'],'pool_id'=>$val['pool_id']],$companyId)->update(['status'=>9]);
                    }
                }
                switch ($syn['syn_type']) {
                    case 1: //普通
                        ## 合成目标
                        $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])->find();
                        if(!$target) throw new ValidateException('合成目标配置错误!');
                        $goods = [];
                        for ($i=1;$i<=$data['num'];$i++){
                            $goods[] = $this->synTypeOne($target, $uuid, $companyId,$logIds,$data['userNo']);
                        }
                        break;
                    case 2://缤纷
                        $target = $activeSynInfoRepository->search(['syn_id' => $data['with_id'], 'is_target' => 1])
                            ->withAttr('num',function ($v,$data) use ($fraudRepository){
                                return  $data['num'] - $fraudRepository->search(['fraud_type'=>3,'pid'=>$data['id']])->sum('num');
                            })
                            ->select();
                        if(!$target) throw new ValidateException('合成配置错误!');

                        $goods = [];
                        for ($i=1;$i<=$data['num'];$i++){
                            $goods[] =  $this->synTypeTwo($target, $uuid, $companyId,$logIds,$syn,$data['userNo']);
                        }
                        break;
                }
            });
            return true;
//        } catch (\Exception $e) {
//            exception_log('任务处理失败', $e);
//        }
    }


    ## 普通合成
    public function synTypeOne($target,$uuid,$companyId,$logIds,$userNo){
        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        switch ($target['target_type']){
            case 1:
                ## 卡牌
                $goods = app()->make(PoolSaleRepository::class)->search([],$companyId)
                    ->where(['id'=>$target['goods_id']])->field('id,file_id,title,stock')->find();
                if(!$goods) throw new ValidateException('卡牌不存在!');
                if($goods['stock'] < 1)  throw new ValidateException('合成卡牌库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['pool_id'] = $target['goods_id'];
                $data['no'] = $no->getNo($target['goods_id'],$uuid);
                $data['price'] = 0.00;
                $data['type'] = 4;
                $data['status'] = 1;
                $re = $usersPoolRepository->addInfo($companyId,$data);
                if($re){
                    Cache::store('redis')->rPop('goods_num_'.$target['goods_id']);
                    ## 库存 减 1
                    app()->make(PoolSaleRepository::class)->search([],$companyId)->where(['id'=>$target['goods_id']])->dec('stock',1)->update();
                    ## 合成数量 减 1
                    app()->make(ActiveSynInfoRepository::class)->search([],$companyId)->where(['id'=>$target['id']])->dec('num',1)->update();
                    $log['syn_pool_id'] = $re['id'];
                    $log['syn_info_id'] = $target['id'];
                    $log['goodsIds'] = implode(',',$logIds);
                    $log['uuid'] = $uuid;
                    $log['userNo'] = $userNo;
                    $re = app()->make(ActiveSynLogRepository::class)->addInfo($companyId,$log);
                }
                break;
            case 2:
                ## 肓盒
                break;
            case 3:
                ## 失败
                $log['syn_info_id'] = $target['id'];
                $log['goodsIds'] = implode(',',$logIds);
                $log['uuid'] = $uuid;
                $log['userNo'] = $userNo;
                app()->make(ActiveSynLogRepository::class)->addInfo($companyId,$log);
                app()->make(ActiveSynInfoRepository::class)->search([],$companyId)->where(['id'=>$target['id']])->dec('num',1)->update();
                break;
        }
    }


    ## 缤纷合成
    public function synTypeTwo($target,$uuid,$companyId,$logIds,$syn,$userNo){

        $fraudRepository = app()->make(FraudRepository::class);
        ## 指定合成用户
        $fraud = $fraudRepository->search(['fraud_type'=>3,'box_id'=>$syn['id'],'uuid'=>$uuid])->whereExp('num','> 0')->find();
        if($fraud){
            $syn_id = $fraud['pid'];
            $is_fraud = 1;
        }else{
            ## 不指定，按机率合成
            foreach ($target as $k => $v) {
                if($v['num'] <= 0){
                    unset($target[$k]);
                    continue;
                }
                if($v['probability'] > 0){
                    $leng = strlen(substr(strrchr($target[$k]['probability'], "."), 1));
                    $item[$v['id']] = $v['probability'] * pow(10, $leng);
                }
            }
            if(!isset($item)) throw new ValidateException('网络错误!');
            if(!$item) throw new ValidateException('网络错误!');
            $syn_id = getRand($item);
            $is_fraud = 2;
        }

        $synInfo = app()->make( ActiveSynInfoRepository::class)->search([],$companyId)->where(['id'=>$syn_id])->find();

        if(!$synInfo) throw new ValidateException('合成卡牌不存在');
        switch ($synInfo['target_type']){
            case 1:
                $goods = app()->make(PoolSaleRepository::class)->search([],$companyId)
                    ->where(['id'=>$synInfo['goods_id']])->field('id,file_id,title,stock')->find();
                if(!$goods) throw new ValidateException('卡牌不存在!');
                if($goods['stock'] < 1)  throw new ValidateException('合成卡牌库存不足');
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $uuid;
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['pool_id'] = $synInfo['goods_id'];
                $data['no'] = $no->getNo($synInfo['goods_id'],$uuid);
                $data['price'] = 0.00;
                $data['type'] = 4;
                $re = app()->make(UsersPoolRepository::class)->addInfo($companyId,$data);
                if($re){
                    Cache::store('redis')->rPop('goods_num_'.$synInfo['goods_id']);
                    app()->make(PoolSaleRepository::class)->search([],$companyId)->where(['id'=>$synInfo['goods_id']])->dec('stock',1)->update();
                    app()->make(ActiveSynInfoRepository::class)->search([],$companyId)->where(['id'=>$synInfo['id']])->dec('num',1)->update();
                    $log['syn_pool_id'] = $re['id'];
                    $log['syn_info_id'] = $synInfo['id'];
                    $log['goodsIds'] = implode(',',$logIds);
                    $log['uuid'] = $uuid;
                    $log['userNo'] = $userNo;
                    $r = app()->make(ActiveSynLogRepository::class)->addInfo($companyId,$log);
                    if($is_fraud == 1){
                        $fraudRepository->editInfo($fraud,['num'=>$fraud['num']-1]);
                    }
                    return ['buy_type'=>1,'title'=>$goods['title'],'cover'=>$goods['picture']];
                }
            case 2:
                break;
            case 3:
                $log['syn_info_id'] = $synInfo['id'];
                $log['goodsIds'] = implode(',',$logIds);
                $log['uuid'] = $uuid;
                $log['userNo'] = $userNo;
                app()->make(ActiveSynLogRepository::class)->addInfo($companyId,$log);
                app()->make(ActiveSynInfoRepository::class)->search([],$companyId)->where(['id'=>$synInfo['id']])->dec('num',1)->update();
                return ['buy_type'=>3,'title'=>$synInfo['title'],'cover'=>$synInfo['picture']];
        }
    }


    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}