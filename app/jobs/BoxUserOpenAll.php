<?php

namespace app\jobs;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\users\UsersAddressRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\queue\Job;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;

class BoxUserOpenAll
{
    public function fire(Job $job, $data)
    {
        try
        {
            for ($i = 1; $i <= $data['num']; $i++)
            {
                $this->openBox($data);
            }
        } catch (\Exception $e){
            exception_log('任务处理失败', $e);
        }
        $job->delete();
    }

    /**
     * 用户付款完成后，执行发放藏品
     * @param $order
     * @return mixed
     */
    public function openBox($event)
    {
        $companyId = $event['company_id'];
        $user = app()->make(UsersRepository::class)->search([], $companyId)->where(['id' => $event['uuid']])->find();
        if (!$user) throw new ValidateException('网络错误!');
        $goodsRepository = app()->make(BoxSaleGoodsListRepository::class);
        return Db::transaction(function () use ($user, $companyId, $goodsRepository, $event)
        {
            $info = app()->make(UsersBoxRepository::class)
                ->search(['uuid' => $event['uuid'], 'box_id' => $event['box_id'], 'status' => 1], $companyId)
                ->order('id asc')->find();
            if (!$info) return false;

            $list = $goodsRepository->search(['box_id' => $info['box_id']], $companyId)->select();
            $item = [];
            foreach ($list as $key => $value){
                if ($value['num'] > 0)
                {
                    $item[$value['id']] = $value['probability'];
                }
            }
            if (!$item) throw new ValidateException('当前开盒人数过多，请稍后重试');
            $prize_id = getRand($item);


            $prize = $goodsRepository->search([], $companyId)->where(['id' => $prize_id])->find();
            if (!$prize) throw new ValidateException('奖品不存在!');

            $no = '';
            $is_agent = app()->make(AgentRepository::class)->search(['uuid' => $event['uuid']])->find();
            $usersRepository = app()->make(UsersRepository::class);
            $userInfo = $usersRepository->search([], $companyId)->find($event['uuid']);
            if ($prize['goods_type'] == 1)
            {
                $open_type = 1;
                $usersPoolRepository = app()->make(UsersPoolRepository::class);
                $no = app()->make(PoolOrderNoRepository::class);
                $data['uuid'] = $user['id'];
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['pool_id'] = $prize['goods_id'];
                $data['no'] = $no->getNo($prize['goods_id'], $user['id']);
                $data['price'] = 0.00;
                $data['type'] = 6;
                $result = $usersPoolRepository->addInfo($companyId, $data);
                if ($result)
                {
                    $no = $data['no'];
                    $poolSaleRepository = app()->make(PoolSaleRepository::class);
                    $poolSaleRepository->search([], $companyId)->where(['id' => $prize['goods_id']])->field('id,stock')->dec('stock', 1)->update();

                    /** @var MineUserRepository $mineUserRepository */
                    $mineUserRepository = app()->make(MineUserRepository::class);
                    /** @var MineRepository $mineRepository */
                    $mineRepository = app()->make(MineRepository::class);
                    $mine = $mineRepository->search(['level' => 1, 'status' => 1], $companyId)->find();
                    if ($mine){
                        //发放start
                        $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
                        if ($re){
                            if ($companyId != 21){
                                if (!$is_agent){
                                    $mineUserRepository->incField($re['id'], 'dispatch_count', 1);
                                }
                            }
                        }
                        if (!$re){
                            event('user.mine', $userInfo);
                            sleep(2);
                            $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
                            if ($re){
                                if ($companyId != 21){
                                    if (!$is_agent){
                                        $mineUserRepository->incField($re['id'], 'dispatch_count', 1);
                                    }
                                }
                            }
                        }
                        //发放end
                    }
                    $log['pool_id'] = $data['pool_id'];
                    $log['no'] = $data['no'];
                    $log['uuid'] = $user['id'];
                    $log['type'] = 7;
                    $res = app()->make(PoolTransferLogRepository::class)->addInfo($companyId, $log);
                }
            }
            if ($prize['goods_type'] == 2){
                $open_type = 2;
                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                $poolInfo = $poolSaleRepository->search([])->find($prize['goods_id']);
                $dorder['uuid'] = $event['uuid'];
                $dorder['order_id'] = SnowFlake::createOnlyId();
                $dorder['buy_type'] = 1;
                $dorder['is_mark'] = 1;
                $dorder['goods_id'] = $prize['goods_id'];
                $dorder['num'] = 1;
                $dorder['status'] = 2;
                $dorder['price'] = $poolInfo['price'];
                $dorder['money'] = $poolInfo['price'];
                $dorder['address_id'] = app()->make(UsersAddressRepository::class)->search(['uuid'=>$event['uuid']])->where('type',1)->value('id');

                /** @var PoolShopOrder $poolShopOrder */
                $poolShopOrder = app()->make(PoolShopOrder::class);
                $poolShopOrder->addInfo($companyId, $dorder);
            }

            if ($prize['goods_type'] == 3){
                $open_type = 3;
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchFoodChange($event['uuid'],4,$prize['goods_id'],['remark'=>'开盒']);
                $prize['goods_id'] = $prize['id'];
            }
            app()->make(UsersBoxRepository::class)->search([], $companyId)->where('id', $info['id'])
                ->update(['status' => 6, 'open_type' => $open_type, 'goods_id' => $prize['goods_id'], 'open_time' => date('Y-m-d H:i:s'), 'open_no' => $event['open_no'], 'no' => $no]);
            $goodsRepository->search([], $companyId)->where(['id' => $prize['id']])->dec('num', 1)->update();
            return true;
        });
    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}