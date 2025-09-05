<?php

namespace app\common\model\active\syn;

use app\common\model\active\ActiveModel;
use app\common\model\BaseModel;
use app\common\model\pool\PoolSaleModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\pool\PoolSaleRepository;

class ActiveSynMaterInfoModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'syn_mater_info';
    }

    public function pool(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }

}
