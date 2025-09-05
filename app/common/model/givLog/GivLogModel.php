<?php

namespace app\common\model\givLog;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;

class GivLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'giv_log';
    }

    public function getGoodsAttr($v,$data){
        switch ($data['buy_type']){
            case 3:
                $dragon = web_config($data['company_id'],'program.colors','');
                $data['title'] = $dragon['name'];
                $data['cover']['show_src'] = $dragon['img'];
                return $data;
                break;
            default:
                $goods = app()->make(PoolSaleRepository::class);
                return $goods->getWhere(['id'=>$data['goods_id']],'id,title,file_id',['cover'=>function($query){
                    $query->field('id,show_src,width,height');
                }]);
                break;
        }

    }

    public function sendUser(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function getUser(){
        return $this->hasOne(UsersModel::class,'id','to_uuid');
    }


}
