<?php

namespace app\common\model\turntable;

use app\common\model\BaseModel;
use app\common\repositories\pool\PoolSaleRepository;

class TurntableGoodsModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'turntable_goods';
    }


    public function getGoodsAttr($v,$data){
        if($data['pool_id']){
           /** @var PoolSaleRepository $poolSaleRepository */
           $poolSaleRepository = app()->make(PoolSaleRepository::class);
           $goods = $poolSaleRepository->search([])->where('id',$data['pool_id'])->field('id,title')->find();
          return $goods;
        }
        return [];
    }
}
