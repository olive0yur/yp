<?php

namespace app\common\model\active;

use app\common\model\BaseModel;
use app\common\model\forum\ForumVoteModel;
use app\common\model\forum\VoteLogModel;
use app\common\model\system\upload\UploadFileModel;

class ActiveModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active';
    }

    public function cover(){
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }

    public function vote(){
        return $this->hasMany(ForumVoteModel::class,'forum_id','id')->withCount('vote');
    }

}
