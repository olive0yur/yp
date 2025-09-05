<?php

namespace app\common\model\active\syn;

use app\common\model\active\ActiveModel;
use app\common\model\BaseModel;
use app\common\model\pool\PoolSaleModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\pool\PoolSaleRepository;

class ActiveSynInfoModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_syn_info';
    }

    public function getGoodsAttr(){
        switch ($this->target_type){
            case 1:
                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                return $poolSaleRepository->search(['id'=>$this->goods_id])->field('id,title,file_id')
                    ->with(['cover'=>function($query){
                        $query->field('id,show_src,width,height');
                    }])
                    ->find();
                break;
            case 2:
                /** @var BoxSaleRepository $boxSaleRepository */
                $boxSaleRepository = app()->make(BoxSaleRepository::class);
                return $boxSaleRepository->getSearch(['id'=>$this->goods_id])->field('id,title,file_id')
                    ->with(['cover'=>function($query){
                        $query->field('id,show_src,width,height');
                    }])
                    ->find();
                break;
        }
    }

    public function cover(){
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }

    public function goods(){
        return $this->hasOne(PoolSaleModel::class,'id','goods_id');
    }
    public function active(){
        return $this->hasOne(ActiveModel::class,'with_id','syn_id');
    }


}
