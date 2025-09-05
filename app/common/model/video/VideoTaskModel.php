<?php

namespace app\common\model\video;

use app\common\model\BaseModel;
use app\common\model\system\ConfigModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class VideoTaskModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'video_task';
    }





}
