<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\users\UsersRepository;

class UsersBoxModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_box';
    }


    public function box(){
        return $this->hasOne(BoxSaleModel::class,'id','box_id');
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function getGoodsAttr($v,$data){
        switch ($data['open_type']){
            case 1:
               return app()->make(PoolSaleRepository::class)->search([],$data['company_id'])->where('id',$data['goods_id'])
                    ->field('id,title,file_id,num,price,price_tag')
                    ->with(['cover'=>function($query){
                        $query->bind(['picture'=>'show_src']);
                    }])
                    ->find();
            case 2:
                return app()->make(PoolSaleRepository::class)->search([],$data['company_id'])->where('id',$data['goods_id'])
                    ->field('id,title,file_id,num,price,price_tag')
                    ->with(['cover'=>function($query){
                        $query->bind(['picture'=>'show_src']);
                    }])
                    ->find();
            case 3:
                $goods = app()->make(BoxSaleGoodsListRepository::class)->search([])
                    ->where('id',$data['goods_id'])->find();
                return [
                    'title'=>web_config($data['company_id'], 'site')['tokens'].'X'.$data['goods_id'],
                    'picture'=> (new UploadFileModel)->where('id',$goods['file_id'])->value('show_src')
                ];
        }
    }


    public function isFollow(){
        return $this->hasMany(PoolFollowModel::class,'goods_id','box_id');
    }
}
