<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\product\ProductModel;
use app\common\model\system\upload\UploadFileModel;

class MineModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine';
    }

    public function fileInfo()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }

}
