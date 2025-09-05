<?php

namespace app\common\model\active\syn;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;

class ActiveSynModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_syn';
    }

    public function cover(){
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }

    public function getGoodsAttr(){
        switch ($this->is_type){
            case 2:
                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                return $poolSaleRepository->getSearch(['id'=>$this->goods_id])->field('id,title,file_id')
                    ->with(['cover'=>function($query){
                        $query->field('id,show_src,width,height');
                    }])
                    ->find();
                break;
            case 3:
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
}
