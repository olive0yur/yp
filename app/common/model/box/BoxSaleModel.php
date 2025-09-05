<?php

namespace app\common\model\box;

use app\common\model\BaseModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\union\UnionAlbumModel;
use app\common\model\union\UnionBrandModel;
use app\common\model\users\UsersModel;

class BoxSaleModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'box_sale';
    }

    public function cover(){
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }


    public function isFollow(){
        return $this->hasMany(PoolFollowModel::class,'goods_id','id')->where(['buy_type'=>2]);
    }

    public function author()
    {
        return $this->hasOne(UsersModel::class, 'id', 'author_id');
    }
    public function album()
    {
        return $this->hasOne(UnionAlbumModel::class, 'id', 'album_id')->where(['is_type' => 2]);
    }

    public function brand()
    {
        return $this->hasOne(UnionBrandModel::class, 'id', 'brand_id')->where(['is_type' => 2]);
    }


}
