<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersMarkModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\model\union\UnionBrandModel;
use app\common\model\union\UnionAlbumModel;

class PoolSaleModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_sale';
    }



    public function cover()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }

    public function PoolMode()
    {
        return $this->hasOne(PoolModeModel::class, 'pool_id', 'id');
    }


    public function getCirculateAttr()
    {
        return PoolOrderNoModel::where('pool_id', $this->id)
            ->where('status', 1)
            ->count();
    }

    public function getSellNumAttr()
    {
    //     return UsersPoolModel::where('pool_id', $this->id)
    //         ->where('status', 1)
    //         ->group('uuid')
    //         ->count();
        return bcsub($this->num,$this->stock);
    }

    public function brand()
    {
        return $this->hasOne(UnionBrandModel::class, 'id', 'brand_id');
    }
}
