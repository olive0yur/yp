<?php

namespace app\common\model\system;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class PaymentModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'payment';
    }

    public function cover()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }

    public function getContentAttr($value)
    {
        return json_decode($value, true);
    }


}
