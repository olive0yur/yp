<?php

namespace app\common\model\sign;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersMarkModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\model\union\UnionBrandModel;
use app\common\model\union\UnionAlbumModel;

class SignBoxModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'sign_box';
    }
}
