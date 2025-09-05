<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\ShopOrderListRepository;
use app\common\repositories\users\UsersMarkRepository;
use think\facade\Log;

class PoolShopOrderModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_shop_order';
    }

    public function goods(){
        return  $this->hasOne(PoolSaleModel::class,'id','goods_id');
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function getGoodsAttr($v,$data)
    {
        switch ($data['buy_type']){
            case 1:
                $repository = app()->make(PoolSaleRepository::class);
                $goods = $repository->search([],$data['company_id'])->where('id',$data['goods_id'])->field('id,type,title,file_id,price_tag')
                    ->with(['cover'=>function($query){
                        $query->bind(['picture'=>'show_src']);
                    }])
                    ->find();
                break;
            case 2:
                $repository = app()->make(BoxSaleRepository::class);
                $goods = $repository->search([],$data['company_id'])->where('id',$data['goods_id'])->field('id,title,file_id')
                    ->with(['cover'=>function($query){
                        $query->bind(['picture'=>'show_src']);
                    }])
                    ->find();
                break;
        }
        return $goods;
    }


}
