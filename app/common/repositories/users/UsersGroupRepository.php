<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\mark\UsersMarkSellRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;

class UsersGroupRepository extends BaseRepository
{

    public function __construct(UsersDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 查询
     * 
     */
    public function getList(array $where, $page, $limit, int $companyId = 0)
    {
        $poolShopOrder = app()->make(PoolShopOrder::class);
        $query = $this->dao->search($where,$companyId)->whereExp('cert_id','> 0');
        $query->field('id,mobile,nickname');
        $count = $query->count();
        $query->withAttr('buy',function ($v,$data)use($poolShopOrder,$where,$companyId){
            $entity =$poolShopOrder->search(['uuid'=>$data['id'],'status'=>2],$companyId);
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->where('pay_time','>=',$start_at);
                $entity->where('pay_time','<=',$end_at);
            }
           return $entity->sum('money');
        })->withAttr('total_buy',function ($v,$data)use($poolShopOrder,$where,$companyId){
            $entity =$poolShopOrder->search(['uuid'=>$data['id'],'status'=>2,'goods_id'=>$where['pool_id']],$companyId);
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->where('pay_time','>=',$start_at);
                $entity->where('pay_time','<=',$end_at);
            }
            return $entity->sum('money');
        })->withAttr('sale',function ($v,$data)use($poolShopOrder,$where,$companyId){
            $entity =$poolShopOrder->search(['sale_uuid'=>$data['id'],'status'=>2],$companyId);
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->whereBetweenTime('pay_time',$start_at,$end_at);
            }
            return $entity->sum('money');
        })->withAttr('total_sale',function ($v,$data)use($poolShopOrder,$where,$companyId){
            $entity =$poolShopOrder->search(['sale_uuid'=>$data['id'],'status'=>2,'goods_id'=>$where['pool_id']],$companyId);
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->whereBetweenTime('pay_time',$start_at,$end_at);
            }
            return $entity->sum('money');
        })->withAttr('hold',function ($v,$data)use($where,$companyId){
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            $entity =$usersPoolRepository->search(['uuid'=>$data['id'],'status'=>1],$companyId);
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->whereBetweenTime('add_time',$start_at,$end_at);
            }
            return $entity->sum('price');
        })->withAttr('market_price',function ($v,$data)use($where,$companyId){
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            $PoolSaleRepository = app()->make(PoolSaleRepository::class);
            $entity =$usersPoolRepository->search(['uuid'=>$data['id']],$companyId);
            $entity->field('*,count(id) as num');
            $entity->whereIn('status',[1,4])->group('pool_id');
            if(!empty($where['add_time'])){
                $time = explode(' - ',$where['add_time']);
                $start_at = trim($time[0]);
                $end_at = trim($time[1]);
                $entity->whereBetweenTime('add_time',$start_at,$end_at);
            }
            $list = $entity->select();
            $totalPrice = 0;
            foreach ($list as $k => $v){
                $userMarkRepository = app()->make(UsersMarkSellRepository::class);
                $priceFlag = $userMarkRepository->search(['pool_id'=>$v['pool_id'],'status'=>2])->min('price');
                if (!$priceFlag)
                {
                    $priceFlag = $PoolSaleRepository->search([])->where(['id'=>$v['pool_id']])->value('price');
                }
                $totalPrice += $priceFlag * $v['num'];
            }
            return $totalPrice;

        })->append(['buy','sale','hold','market_price','total_buy','total_sale']);
        $list = $query->page($page, $limit)
            ->select();
        if(!empty($where['price_type']) && !empty($where['min']) && !empty($where['max'])){
            foreach ($list as $key => $value){
                switch ($where['price_type']){
                    case 1:
                        if( !($value['buy'] >= $where['min'] && $value['buy'] <= $where['max'])){
                            unset($list[$key]);
                        }
                        break;
                    case 2:
                        if( !($value['sale'] >= $where['min'] && $value['sale'] <= $where['max'])){
                            unset($list[$key]);
                        }
                        break;
                    case 3:
                        if( !($value['hold'] >= $where['min'] && $value['hold'] <= $where['max'])){
                            unset($list[$key]);
                        }
                        break;
                }
            }

        }
        return compact('list', 'count');
    }

    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }


    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    /**
     * 获取所有标签
     *
     * @param int|null $companyId
     * @param $status
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll($companyId = 0,$status = '')
    {
        $query = $this->dao->getSearch([
            'is_show' => $status,
            'company_id' => $companyId
        ]);
        $list = $query->select();
        return $list;
    }

    public function getCascaderData($companyId = 0,$status = '')
    {
        $list = $this->getAll($companyId,$status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'name', 0, 'pid', 0, 1);
    }

    /**
     * 删除
     */
    public function delete(array $ids)
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
}