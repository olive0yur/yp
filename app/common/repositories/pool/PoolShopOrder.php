<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolShopOrderDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolShopOrderDao
 */
class PoolShopOrder extends BaseRepository
{

    public function __construct(PoolShopOrderDao $dao)
    {
        $this->dao = $dao;
    }
    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->order('id desc');
        $query->field('*');
        $count = $query->count();
        $list = $query->with([
            'user'=>function($query){
                $query->field('id,mobile,nickname');
            }])
            ->append(['goods_name'])
            ->page($page, $limit)
        ->select();
        return compact('list', 'count');
    }


    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data,$companyId) {
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            return $this->dao->create($data);
        });
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }


    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getStayPayOrderInfo(int $uuid)
    {
            $orderRepostory = app()->make(PoolShopOrder::class);
            $orderData = $orderRepostory->getSearch([])
                ->where('uuid',$uuid)
                ->where('status',1)
                ->where('is_main',1)
                ->with(['getHash'=>function($query){
                    $query->bind(['hash']);
                }])
                ->find();


        if($orderData){
            return $orderData;
        }else{
            return [];
        }
    }

    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->order('id desc');
        $count = $query->count();
        $query ->withAttr('end_time',function ($v,$data){
             return strtotime('+3 minute',strtotime($data['add_time']));
        })->append(['end_time','goods']);
        $list = $query->page($page, $limit)
            ->select();
        return compact('list', 'count');
    }

    public function getApiDetail(array $where,int $uuid,int $companyId = null)
    {
        $query = $this->dao->search($where,$companyId);
        $query->with(['goods'=>function($query){
            $query->with(['cover'=>function($query){
                $query->bind(['picture'=>'show_src']);
            }]);
        }])->withAttr('end_time',function ($v,$data){
            return strtotime('+15 minute',strtotime($data['add_time']));
        })->append(['end_time']);
        $info = $query->find();
        api_user_log($uuid,3,$companyId,'查看订单详情');
        return $info;
    }

    public function apiRebate(array $where,int $uuid,int $companyId = null)
    {
        $where['uuid'] = $uuid;
        $where['status'] = 2;
        $query = $this->dao->search($where,$companyId);
        $query->with(['goods'=>function($query){
            $query->with(['cover'=>function($query){
                $query->bind(['picture'=>'show_src']);
            }]);
        }])->withAttr('end_time',function ($v,$data){
            return strtotime('+15 minute',strtotime($data['add_time']));
        })->append(['end_time']);
        $info = $query->find();
        if(!$info) throw new ValidateException('网络错误！');
        return Db::transaction(function () use ($info, $uuid)
        {
            $info = $info->toArray();
            $goods = $info['goods'];
            $box = app()->make(UsersBoxRepository::class)->search(['uuid'=>$uuid,'status'=>6])
                ->where(['goods_id'=>$goods['id'],'order_id'=>$info['order_id']])->find();
            $this->dao->update($info['id'],['status'=>4]);
           if($box){
               $res = app()->make(UsersBoxRepository::class)->search([])->where(['id'=>$box['id']])->update(['status'=>66]);
               if($res){
                   app()->make(UsersRepository::class)->batchFoodChange($uuid, 4, $goods['price_tag'], ['remark' => '盲盒回收']);
               }
           }
        });
    }

    public function cannel($where,$uuid,$companyId = null){
          $where['uuid'] = $uuid;
            $info = $this->dao->search([])
                ->where('order_id', $where['order_id'])
                ->find();
          if(!$info) throw new ValidateException('订单不存在');
          if($info['status'] != 1) throw new ValidateException('此订单当前状态不可取消！');

             api_user_log($uuid, 4, $companyId, '取消订单' . $info['order_id']);
             $this->editInfo($info,['status'=>4,'cannel_time'=>date('Y-m-d H:i:s')]);
            return true;
    }

    public function finish($where,$uuid,$companyId = null){
        $where['uuid'] = $uuid;
        $info = $this->dao->search([])
            ->where('order_id', $where['order_id'])
            ->find();
        if(!$info) throw new ValidateException('订单不存在');
        if($info['status'] != 3) throw new ValidateException('此订单当前状态不可收货！');

        api_user_log($uuid, 4, $companyId, '确定订单' . $info['order_id']);
        $this->editInfo($info,['status'=>5,'cannel_time'=>date('Y-m-d H:i:s')]);
        return true;
    }

}